<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminAuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $hasActorId = Schema::hasColumn('audit_logs', 'actor_id');
        $hasAction = Schema::hasColumn('audit_logs', 'action');

        $query = AuditLog::query()
            ->when($hasActorId, fn ($q) => $q->with('actor:id,name,mobile'))
            ->when($request->filled('action') && $hasAction, fn ($q) => $q->where('action', 'like', '%'.$request->string('action').'%'))
            ->when($request->filled('action') && ! $hasAction && Schema::hasColumn('audit_logs', 'event'), fn ($q) => $q->where('event', 'like', '%'.$request->string('action').'%'))
            ->when($request->filled('actor_id') && $hasActorId, fn ($q) => $q->where('actor_id', $request->integer('actor_id')))
            ->when($request->filled('actor_id') && ! $hasActorId && Schema::hasColumn('audit_logs', 'user_id'), fn ($q) => $q->where('user_id', $request->integer('actor_id')))
            ->latest();

        return response()->json($query->paginate(30));
    }
}
