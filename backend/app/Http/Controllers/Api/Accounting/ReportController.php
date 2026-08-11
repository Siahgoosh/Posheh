<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Services\Accounting\AccountingReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly AccountingReportService $reports) {}

    public function debtors(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reports->debtorsCreditors($request->user(), 'debtors')]);
    }

    public function creditors(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reports->debtorsCreditors($request->user(), 'creditors')]);
    }

    public function consultants(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reports->consultantsReport($request->user())]);
    }

    public function peopleLedger(Request $request): JsonResponse
    {
        $data = $request->validate([
            'party_type' => ['required', 'string', 'in:user,customer,'.User::class.','.Customer::class],
            'party_id' => ['required', 'integer', 'min:1'],
        ]);

        $type = match ($data['party_type']) {
            'user', User::class => User::class,
            default => Customer::class,
        };

        return response()->json([
            'data' => $this->reports->peopleLedger($request->user(), $type, (int) $data['party_id']),
        ]);
    }

    public function dealFinance(Request $request, int $dealId): JsonResponse
    {
        return response()->json(['data' => $this->reports->dealFinance($request->user(), $dealId)]);
    }

    public function propertyFinance(Request $request, int $propertyId): JsonResponse
    {
        return response()->json(['data' => $this->reports->propertyFinance($request->user(), $propertyId)]);
    }
}
