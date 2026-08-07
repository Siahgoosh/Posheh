<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\CommLead;
use App\Modules\Communication\Application\Services\CommPermissionService;
use App\Modules\Communication\Application\Services\ConversationService;
use App\Modules\Communication\Application\Services\InboxService;
use App\Modules\Communication\Application\Services\LiveVisitorService;
use App\Modules\Communication\Application\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCommunicationController extends Controller
{
    public function __construct(
        private readonly InboxService $inbox,
        private readonly LiveVisitorService $liveVisitors,
        private readonly ConversationService $conversations,
        private readonly MessageService $messages,
        private readonly CommPermissionService $permissions,
    ) {}

    private function authorizeComm(Request $request, string $permission): void
    {
        $role = $request->user()?->role ?? '';
        if ($role === 'super_admin') {
            return;
        }
        if (! $this->permissions->userCan($role, $permission)) {
            abort(403, 'دسترسی به این بخش مجاز نیست.');
        }
    }

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorizeComm($request, 'comm.dashboard.view');

        return response()->json(['data' => $this->inbox->dashboardStats()]);
    }

    public function inbox(Request $request): JsonResponse
    {
        $this->authorizeComm($request, 'comm.inbox.view');

        $paginator = $this->inbox->conversations($request->input('status'));

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($c) => $this->formatConversationListItem($c)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function showConversation(Request $request, string $uuid): JsonResponse
    {
        $this->authorizeComm($request, 'comm.inbox.view');

        $conversation = $this->inbox->conversationDetail($uuid);
        if (! $conversation) {
            return response()->json(['message' => 'گفتگو یافت نشد.'], 404);
        }

        $this->messages->markReadByOperator($conversation);

        return response()->json(['data' => $this->formatConversationDetail($conversation)]);
    }

    public function reply(Request $request, string $uuid): JsonResponse
    {
        $this->authorizeComm($request, 'comm.messages.send');

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $conversation = $this->conversations->findByUuid($uuid);
        if (! $conversation) {
            return response()->json(['message' => 'گفتگو یافت نشد.'], 404);
        }

        $message = $this->messages->sendFromOperator(
            $conversation,
            $request->user()->id,
            $data['body'],
            (bool) ($data['is_internal'] ?? false),
        );

        return response()->json(['data' => $message, 'message' => 'پیام ارسال شد.']);
    }

    public function liveVisitors(Request $request): JsonResponse
    {
        $this->authorizeComm($request, 'comm.visitors.live');

        $sessions = $this->liveVisitors->online();

        return response()->json([
            'data' => $sessions->map(fn ($s) => [
                'visitor_id' => $s->visitor_id,
                'name' => trim(($s->visitor?->first_name ?? '').' '.($s->visitor?->last_name ?? '')),
                'mobile' => $s->visitor?->mobile,
                'lead_score' => $s->visitor?->lead_score ?? 0,
                'current_page' => $s->current_page,
                'time_on_site_seconds' => $s->time_on_site_seconds,
                'scroll_depth' => $s->scroll_depth,
                'click_count' => $s->click_count,
                'pages_viewed' => $s->pages_viewed,
                'last_activity_at' => $s->last_activity_at?->toIso8601String(),
            ]),
        ]);
    }

    public function updateLead(Request $request, int $id): JsonResponse
    {
        $this->authorizeComm($request, 'comm.leads.manage');

        $lead = CommLead::findOrFail($id);
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:30'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'pipeline_stage_id' => ['nullable', 'integer', 'exists:comm_pipeline_stages,id'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        $lead->update($data);

        return response()->json(['data' => $lead->fresh(['stage', 'assignee']), 'message' => 'سرنخ به‌روز شد.']);
    }

    /** @param \App\Models\Communication\CommConversation $c */
    private function formatConversationListItem($c): array
    {
        $last = $c->messages->first();

        return [
            'uuid' => $c->uuid,
            'status' => $c->status,
            'channel' => $c->channel,
            'subject' => $c->subject,
            'unread_operator' => $c->unread_operator,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'visitor' => $c->visitor ? [
                'name' => trim(($c->visitor->first_name ?? '').' '.($c->visitor->last_name ?? '')),
                'mobile' => $c->visitor->mobile,
                'lead_score' => $c->visitor->lead_score,
            ] : null,
            'lead' => $c->lead ? [
                'id' => $c->lead->id,
                'office_name' => $c->lead->office_name,
                'request_type' => $c->lead->request_type,
                'lead_score' => $c->lead->lead_score,
            ] : null,
            'last_message' => $last ? [
                'body' => \Illuminate\Support\Str::limit($last->body, 120),
                'sender_type' => $last->sender_type,
                'created_at' => $last->created_at?->toIso8601String(),
            ] : null,
        ];
    }

    /** @param \App\Models\Communication\CommConversation $c */
    private function formatConversationDetail($c): array
    {
        return [
            'uuid' => $c->uuid,
            'status' => $c->status,
            'channel' => $c->channel,
            'subject' => $c->subject,
            'visitor' => $c->visitor,
            'lead' => $c->lead,
            'messages' => $c->messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_type' => $m->sender_type,
                'sender_id' => $m->sender_id,
                'body' => $m->body,
                'is_internal' => $m->is_internal,
                'created_at' => $m->created_at?->toIso8601String(),
                'read_by_visitor_at' => $m->read_by_visitor_at?->toIso8601String(),
                'read_by_operator_at' => $m->read_by_operator_at?->toIso8601String(),
            ]),
        ];
    }
}
