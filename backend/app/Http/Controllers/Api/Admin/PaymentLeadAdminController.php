<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\PaymentLeadsExport;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentLeadAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->input('status', 'incomplete');
        $query = Payment::with(['user', 'office', 'discountCode'])
            ->latest();

        if ($status === 'incomplete') {
            $query->whereIn('status', ['pending', 'failed']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('user_phone', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%"))
                    ->orWhereHas('office', fn ($o) => $o->where('name', 'like', "%{$search}%"));
            });
        }

        $payments = $query->paginate(30);

        $stats = [
            'pending' => Payment::where('status', 'pending')->count(),
            'failed' => Payment::where('status', 'failed')->count(),
            'paid' => Payment::where('status', 'paid')->count(),
        ];

        return response()->json([
            'stats' => $stats,
            'data' => $payments,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $status = $request->input('status', 'incomplete');
        $query = Payment::with(['user', 'office', 'discountCode'])->latest();

        if ($status === 'incomplete') {
            $query->whereIn('status', ['pending', 'failed']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $rows = $query->get();

        return Excel::download(
            new PaymentLeadsExport($rows),
            'posheh-payment-leads-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
