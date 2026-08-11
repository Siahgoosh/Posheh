<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\ChequeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChequeController extends Controller
{
    public function __construct(private readonly ChequeService $cheques) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->cheques->list($request->user(), $request->only([
            'direction', 'status', 'due_from', 'due_to',
        ]));

        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:in,out'],
            'cheque_number' => ['required', 'string', 'max:64'],
            'sayad_id' => ['nullable', 'string', 'max:64'],
            'amount' => ['required', 'integer', 'min:1'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'branch_name' => ['nullable', 'string', 'max:120'],
            'issuer_name' => ['nullable', 'string', 'max:120'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'party_type' => ['nullable', 'string', 'max:80'],
            'party_id' => ['nullable', 'integer'],
            'property_id' => ['nullable', 'integer'],
            'crm_deal_id' => ['nullable', 'integer'],
            'cash_account_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
        ]);

        $cheque = $this->cheques->create($request->user(), $data);

        return response()->json(['data' => $cheque], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:30'],
        ]);

        $cheque = $this->cheques->updateStatus($request->user(), $id, $data['status']);

        return response()->json(['data' => $cheque]);
    }

    public function alerts(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->cheques->dueAlerts($request->user())]);
    }
}
