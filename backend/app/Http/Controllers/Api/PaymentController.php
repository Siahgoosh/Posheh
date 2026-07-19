<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentHistoryService;
use App\Services\Payment\PaymentInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentHistoryService $history,
        private readonly PaymentInvoiceService $invoices,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $office = $request->user()->office;

        return response()->json([
            'data' => $this->history->listForOffice($office),
            'wallet_transactions' => $this->history->walletTransactions($office),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->history->showForOffice($request->user()->office, $id, $this->invoices),
        ]);
    }

    public function invoice(Request $request, int $id): JsonResponse
    {
        $payment = $request->user()->office->payments()->findOrFail($id);

        return response()->json([
            'data' => $this->invoices->build($payment),
        ]);
    }

    public function invoicePdf(Request $request, int $id)
    {
        $payment = $request->user()->office->payments()->findOrFail($id);
        $path = $this->invoices->pdfPath($payment);

        return response()->download(
            Storage::disk('public')->path($path),
            ($payment->invoice_number ?? 'invoice-'.$payment->id).'.pdf',
        );
    }
}
