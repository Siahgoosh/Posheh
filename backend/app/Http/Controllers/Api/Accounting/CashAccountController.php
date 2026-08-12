<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingCashAccount;
use App\Models\AccountingPosTerminal;
use App\Services\Accounting\AccountingBootstrapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CashAccountController extends Controller
{
    public function __construct(private readonly AccountingBootstrapService $bootstrap) {}

    public function index(Request $request): JsonResponse
    {
        $this->bootstrap->ensureForUser($request->user());
        $items = AccountingCashAccount::with('ledgerAccount')
            ->where('office_id', $request->user()->office_id)
            ->where('is_active', true)
            ->orderBy('kind')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['auth' => ['فقط مدیر می‌تواند صندوق/بانک تعریف کند.']]);
        }
        $this->bootstrap->ensureForUser($user);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', 'in:cashbox,bank'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_holder' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'card_number' => ['nullable', 'string', 'max:32'],
            'iban' => ['nullable', 'string', 'max:34'],
            'opening_balance' => ['nullable', 'integer'],
            'opening_date' => ['nullable', 'date'],
            'ledger_account_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = AccountingCashAccount::create([
            ...$data,
            'office_id' => $user->office_id,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'is_active' => true,
        ]);

        if (empty($data['ledger_account_id'])) {
            $code = ($data['kind'] ?? '') === 'bank' ? '1102' : '1101';
            $ledgerId = \App\Models\AccountingAccount::where('office_id', $user->office_id)
                ->where('code', $code)
                ->value('id');
            if ($ledgerId) {
                $item->update(['ledger_account_id' => $ledgerId]);
            }
        }

        return response()->json(['data' => $item->fresh()], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            abort(403);
        }
        $item = AccountingCashAccount::where('office_id', $user->office_id)->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_holder' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'card_number' => ['nullable', 'string', 'max:32'],
            'iban' => ['nullable', 'string', 'max:34'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'ledger_account_id' => ['nullable', 'integer'],
        ]);
        $item->update($data);

        return response()->json(['data' => $item->fresh()]);
    }

    public function posIndex(Request $request): JsonResponse
    {
        $this->bootstrap->ensureForUser($request->user());
        $items = AccountingPosTerminal::where('office_id', $request->user()->office_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function posStore(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            abort(403);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'terminal_id' => ['nullable', 'string', 'max:64'],
            'merchant_id' => ['nullable', 'string', 'max:64'],
            'cash_account_id' => ['nullable', 'integer'],
        ]);
        $item = AccountingPosTerminal::create([
            ...$data,
            'office_id' => $user->office_id,
            'is_active' => true,
        ]);

        return response()->json(['data' => $item], 201);
    }
}
