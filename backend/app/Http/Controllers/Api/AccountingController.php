<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountingTransactionResource;
use App\Services\Accounting\AccountingBootstrapService;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\ChequeService;
use App\Services\Accounting\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(
        private readonly AccountingService $accounting,
        private readonly AccountingBootstrapService $bootstrap,
        private readonly ChequeService $cheques,
        private readonly SettlementService $settlements,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->accounting->list($request->user(), $request->only([
            'type', 'status', 'from', 'to', 'q', 'per_page',
        ]));

        return AccountingTransactionResource::collection($paginator)->response();
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->accounting->summary($request->user())]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->accounting->dashboard($request->user())]);
    }

    public function profitAndLoss(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return response()->json([
            'data' => $this->accounting->profitAndLoss($request->user(), $data['from'] ?? null, $data['to'] ?? null),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'category' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['required', 'date'],
            'property_id' => ['nullable', 'integer'],
            'crm_deal_id' => ['nullable', 'integer'],
            'commission_id' => ['nullable', 'integer'],
            'consultant_id' => ['nullable', 'integer'],
            'account_id' => ['nullable', 'integer'],
            'cash_account_id' => ['nullable', 'integer'],
            'pos_terminal_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'reference' => ['nullable', 'string', 'max:120'],
            'party_type' => ['nullable', 'string', 'max:80'],
            'party_id' => ['nullable', 'integer'],
        ]);

        $tx = $this->accounting->create($request->user(), $data);

        return response()->json(['data' => new AccountingTransactionResource($tx)], 201);
    }

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_cash_account_id' => ['required', 'integer'],
            'to_cash_account_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
            'transaction_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $this->accounting->transfer($request->user(), $data);

        return response()->json(['data' => $result], 201);
    }

    public function void(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $tx = $this->accounting->void($request->user(), $id, $data['reason'] ?? null);

        return response()->json(['data' => new AccountingTransactionResource($tx)]);
    }

    public function bootstrap(Request $request): JsonResponse
    {
        $this->bootstrap->ensureForUser($request->user());

        return response()->json(['message' => 'حساب‌های پیش‌فرض آماده‌اند.']);
    }

    public function chequeAlerts(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->cheques->dueAlerts($request->user())]);
    }

    public function settleCommission(Request $request, int $commissionId): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1'],
            'cash_account_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'settlement_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
        ]);

        $settlement = $this->settlements->settleCommission($request->user(), $commissionId, $data);

        return response()->json(['data' => $settlement], 201);
    }
}
