<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\AccountingCashAccount;
use App\Models\AccountingPosTerminal;
use App\Services\Accounting\AccountingBootstrapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function __construct(private readonly AccountingBootstrapService $bootstrap) {}

    public function index(Request $request): JsonResponse
    {
        $this->bootstrap->ensureForUser($request->user());
        $items = AccountingAccount::where('office_id', $request->user()->office_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['auth' => ['فقط مدیر می‌تواند حساب بسازد.']]);
        }
        $this->bootstrap->ensureForUser($user);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'group_key' => ['required', 'in:assets,liabilities,income,expenses,equity'],
            'type' => ['required', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $account = AccountingAccount::create([
            ...$data,
            'office_id' => $user->office_id,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => $data['sort_order'] ?? 500,
        ]);

        return response()->json(['data' => $account], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            abort(403);
        }
        $account = AccountingAccount::where('office_id', $user->office_id)->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);
        if ($account->is_system && array_key_exists('is_active', $data) && ! $data['is_active']) {
            // allow deactivate system accounts
        }
        $account->update($data);

        return response()->json(['data' => $account->fresh()]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            abort(403);
        }
        $account = AccountingAccount::where('office_id', $user->office_id)->findOrFail($id);
        if ($account->is_system) {
            $account->update(['is_active' => false]);

            return response()->json(['message' => 'حساب سیستمی غیرفعال شد (حذف فیزیکی مجاز نیست).']);
        }
        $account->delete();

        return response()->json(['message' => 'حساب حذف شد.']);
    }
}
