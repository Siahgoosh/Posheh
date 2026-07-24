<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $like = '%'.$q.'%';

        return response()->json([
            'data' => [
                'users' => User::where('name', 'like', $like)->orWhere('mobile', 'like', $like)
                    ->limit(5)->get(['id', 'name', 'mobile', 'role']),
                'tenants' => Office::where('name', 'like', $like)->orWhere('slug', 'like', $like)
                    ->limit(5)->get(['id', 'name', 'slug', 'city']),
                'payments' => Payment::where('ref_id', 'like', $like)->orWhere('authority', 'like', $like)
                    ->limit(5)->get(['id', 'office_id', 'amount', 'status', 'gateway', 'paid_at']),
                'tickets' => Ticket::where('subject', 'like', $like)
                    ->limit(5)->get(['id', 'subject', 'status', 'office_id']),
                'properties' => Property::withoutGlobalScopes()
                    ->where('title', 'like', $like)->orWhere('code', 'like', $like)
                    ->limit(5)->get(['id', 'title', 'code', 'office_id']),
            ],
        ]);
    }
}
