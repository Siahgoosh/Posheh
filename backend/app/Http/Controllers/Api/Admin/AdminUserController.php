<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::with('office:id,name,slug')
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('mobile', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->latest();

        return response()->json($query->paginate(20));
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['office', 'devices'])->findOrFail($id);

        return response()->json(['data' => $user]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username'],
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,mobile'],
            'password' => ['required', 'string', Password::min(8)],
            'office_id' => ['required', 'integer', 'exists:offices,id'],
            'role' => ['required', Rule::in([UserRole::Consultant->value, UserRole::OfficeManager->value])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => strtolower($data['username']),
            'mobile' => $data['mobile'],
            'password' => $data['password'],
            'office_id' => $data['office_id'],
            'role' => $data['role'],
            'is_active' => true,
            'mobile_verified_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->audit->log('user.created', User::class, $user->id, 'ایجاد کاربر توسط مدیر');

        return response()->json(['data' => $user->load('office'), 'message' => 'کاربر ایجاد شد.'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $actor = $request->user();
        $old = $user->only(['name', 'role', 'is_active', 'email', 'username', 'mobile']);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'mobile' => ['nullable', 'string', 'regex:/^09\d{9}$/', Rule::unique('users', 'mobile')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::min(8)],
            'role' => ['sometimes', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['role'])) {
            $newRole = UserRole::from($data['role']);
            // Only super_admin may assign/change platform roles (including self-promotion).
            if ($newRole->isPlatformStaff() && ! $actor->isSuperAdmin()) {
                return response()->json(['message' => 'فقط مدیر ارشد می‌تواند نقش مدیران پلتفرم را تغییر دهد.'], 403);
            }
            if ($user->isPlatformStaff() && ! $actor->isSuperAdmin()) {
                return response()->json(['message' => 'ویرایش مدیران پلتفرم فقط برای مدیر ارشد مجاز است.'], 403);
            }
            // Nobody can assign super_admin except an existing super_admin, and never via self-service escalation by non-super.
            if ($newRole === UserRole::SuperAdmin && ! $actor->isSuperAdmin()) {
                return response()->json(['message' => 'ارتقای نقش به مدیر ارشد مجاز نیست.'], 403);
            }
        }

        if (! empty($data['password'])) {
            // hashed via User model cast
        } else {
            unset($data['password']);
        }

        if (isset($data['username'])) {
            $data['username'] = strtolower($data['username']);
        }

        $user->update($data);
        $this->audit->log('user.updated', User::class, $user->id, 'ویرایش کاربر', $old, $data);

        return response()->json(['data' => $user->fresh('office')]);
    }

    public function logoutAll(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->tokens()->delete();
        Device::where('user_id', $user->id)->delete();
        $this->audit->log('user.logout_all', User::class, $user->id, 'خروج از همه دستگاه‌ها');

        return response()->json(['message' => 'تمام نشست‌های کاربر پایان یافت.']);
    }

    public function platformStaff(): JsonResponse
    {
        return response()->json([
            'data' => User::whereIn('role', UserRole::platformRoles())
                ->orderBy('name')
                ->get(['id', 'name', 'mobile', 'email', 'role', 'is_active', 'last_login_at']),
        ]);
    }

    public function storePlatformStaff(Request $request): JsonResponse
    {
        if (! $request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'فقط مدیر ارشد می‌تواند مدیر پلتفرم ایجاد کند.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'email' => ['nullable', 'email'],
            'role' => ['required', Rule::in([
                UserRole::PlatformAdmin->value,
                UserRole::PlatformSupport->value,
                UserRole::PlatformFinance->value,
            ])],
        ]);

        $user = User::create([
            ...$data,
            'office_id' => null,
            'is_active' => true,
        ]);

        $this->audit->log('platform_staff.created', User::class, $user->id, 'ایجاد مدیر پلتفرم');

        return response()->json(['data' => $user], 201);
    }
}
