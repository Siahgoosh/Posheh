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

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $old = $user->only(['name', 'role', 'is_active', 'email']);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['sometimes', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active' => ['sometimes', 'boolean'],
        ]);

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
