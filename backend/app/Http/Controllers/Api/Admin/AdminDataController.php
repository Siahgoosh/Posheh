<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingTransaction;
use App\Models\Commission;
use App\Models\Contract;
use App\Models\CrmDeal;
use App\Models\Customer;
use App\Models\Device;
use App\Models\ImpersonationSession;
use App\Models\OfficeVisitRequest;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDataController extends Controller
{
    public function customers(Request $request): JsonResponse
    {
        $query = Customer::with(['office:id,name,slug', 'assignee:id,name'])
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('mobile', 'like', $term));
            })
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function owners(Request $request): JsonResponse
    {
        $query = Owner::with(['office:id,name,slug'])
            ->withCount('properties')
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('mobile', 'like', $term));
            })
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function properties(Request $request): JsonResponse
    {
        $query = Property::with(['office:id,name,slug', 'owner:id,name,mobile'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('code', 'like', $term));
            })
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function crmDeals(Request $request): JsonResponse
    {
        $query = CrmDeal::with(['office:id,name', 'assignee:id,name'])
            ->when($request->filled('stage'), fn ($q) => $q->where('stage', $request->string('stage')))
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function propertyVisits(Request $request): JsonResponse
    {
        $query = PropertyVisit::with(['office:id,name', 'property:id,title,code', 'customer:id,name'])
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->latest('visit_at');

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function visitRequests(Request $request): JsonResponse
    {
        $query = OfficeVisitRequest::with(['office:id,name', 'property:id,title'])
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function contracts(Request $request): JsonResponse
    {
        $query = Contract::with(['office:id,name', 'property:id,title'])
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function commissions(Request $request): JsonResponse
    {
        $query = Commission::with(['office:id,name', 'user:id,name'])
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function accounting(Request $request): JsonResponse
    {
        $query = AccountingTransaction::with(['office:id,name'])
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function devices(Request $request): JsonResponse
    {
        $query = Device::with(['user:id,name,mobile,role', 'user.office:id,name'])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->latest('last_active_at');

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function impersonationSessions(Request $request): JsonResponse
    {
        $query = ImpersonationSession::with(['admin:id,name,mobile', 'targetUser:id,name,mobile'])
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }
}
