<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountingTransactionResource;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->accounting->list($request->user(), $request->input('type'));

        return AccountingTransactionResource::collection($paginator)->response();
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->accounting->summary($request->user())]);
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
            'reference' => ['nullable', 'string'],
        ]);

        $tx = $this->accounting->create($request->user(), $data);

        return response()->json(['data' => new AccountingTransactionResource($tx)], 201);
    }
}
