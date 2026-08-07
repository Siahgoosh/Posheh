<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\CommKnowledgeArticle;
use App\Models\Communication\CommKnowledgeCategory;
use App\Models\Communication\CommLead;
use App\Models\Communication\CommLeadNote;
use App\Modules\Communication\Application\Services\AiCopilotService;
use App\Modules\Communication\Application\Services\AttachmentService;
use App\Modules\Communication\Application\Services\CommPermissionService;
use App\Modules\Communication\Application\Services\ConversationService;
use App\Modules\Communication\Application\Services\InboxService;
use App\Modules\Communication\Application\Services\LiveVisitorService;
use App\Modules\Communication\Application\Services\MessageService;
use App\Modules\Communication\Application\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCommunicationController extends Controller
{
    public function __construct(
        private readonly InboxService $inbox,
        private readonly LiveVisitorService $liveVisitors,
        private readonly ConversationService $conversations,
        private readonly MessageService $messages,
        private readonly CommPermissionService $permissions,
        private readonly TicketService $tickets,
        private readonly AiCopilotService $ai,
        private readonly AttachmentService $attachments,
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
            'body' => ['required_without:file', 'nullable', 'string', 'max:5000'],
            'is_internal' => ['nullable', 'boolean'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        $conversation = $this->conversations->findByUuid($uuid);
        if (! $conversation) {
            return response()->json(['message' => 'گفتگو یافت نشد.'], 404);
        }

        $body = $data['body'] ?? '';
        if ($request->hasFile('file') && $body === '') {
            $body = '['.$request->file('file')->getClientOriginalName().']';
        }

        $hasFile = $request->hasFile('file');

        $message = $this->messages->sendFromOperator(
            $conversation,
            $request->user()->id,
            $body,
            (bool) ($data['is_internal'] ?? false),
            ! $hasFile,
        );

        if ($hasFile) {
            $this->attachments->storeForMessage($conversation, $message, $request->file('file'));
            if (! ($data['is_internal'] ?? false)) {
                app(\App\Modules\Communication\Application\Services\ChannelDispatcher::class)
                    ->dispatchToVisitor($conversation->fresh(), $message);
            }
        }

        return response()->json(['data' => $message->fresh('commAttachments'), 'message' => 'پیام ارسال شد.']);
    }

    public function updateConversation(Request $request, string $uuid): JsonResponse
    {
        $this->authorizeComm($request, 'comm.inbox.manage');

        $conversation = $this->conversations->findByUuid($uuid);
        if (! $conversation) {
            return response()->json(['message' => 'گفتگو یافت نشد.'], 404);
        }

        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:30'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $conversation->update($data);

        return response()->json(['data' => $conversation->fresh(['assignee']), 'message' => 'گفتگو به‌روز شد.']);
    }

    public function addNote(Request $request, string $uuid): JsonResponse
    {
        $this->authorizeComm($request, 'comm.leads.manage');

        $conversation = $this->conversations->findByUuid($uuid);
        if (! $conversation || ! $conversation->lead_id) {
            return response()->json(['message' => 'سرنخ مرتبط یافت نشد.'], 404);
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $note = CommLeadNote::create([
            'lead_id' => $conversation->lead_id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return response()->json(['data' => $note, 'message' => 'یادداشت ثبت شد.']);
    }

    public function createTicket(Request $request, string $uuid): JsonResponse
    {
        $this->authorizeComm($request, 'comm.tickets.manage');

        $conversation = $this->conversations->findByUuid($uuid);
        if (! $conversation) {
            return response()->json(['message' => 'گفتگو یافت نشد.'], 404);
        }

        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:200'],
            'priority' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:60'],
        ]);

        $ticket = $this->tickets->createFromConversation($conversation, $request->user()->id, $data);

        return response()->json(['data' => $ticket, 'message' => 'تیکت ایجاد شد.']);
    }

    public function closeTicket(Request $request, string $uuid): JsonResponse
    {
        $this->authorizeComm($request, 'comm.tickets.manage');

        $conversation = $this->conversations->findByUuid($uuid);
        if (! $conversation?->ticket) {
            return response()->json(['message' => 'تیکت یافت نشد.'], 404);
        }

        $ticket = $this->tickets->close($conversation->ticket, $request->user()->id);

        return response()->json(['data' => $ticket, 'message' => 'تیکت بسته شد.']);
    }

    public function aiSuggestions(Request $request, string $uuid): JsonResponse
    {
        $this->authorizeComm($request, 'comm.ai.use');

        $conversation = $this->conversations->findByUuid($uuid);
        if (! $conversation) {
            return response()->json(['message' => 'گفتگو یافت نشد.'], 404);
        }

        $suggestions = $this->ai->suggestReplies($conversation);
        $knowledge = $this->ai->knowledgeMatches(
            $conversation->messages()->latest()->value('body') ?? '',
            $conversation->office_id,
        );

        return response()->json(['data' => ['suggestions' => $suggestions, 'knowledge' => $knowledge]]);
    }

    public function aiSummarize(Request $request, string $uuid): JsonResponse
    {
        $this->authorizeComm($request, 'comm.ai.use');

        $conversation = $this->conversations->findByUuid($uuid);
        if (! $conversation) {
            return response()->json(['message' => 'گفتگو یافت نشد.'], 404);
        }

        return response()->json(['data' => $this->ai->summarize($conversation)]);
    }

    public function knowledgeIndex(Request $request): JsonResponse
    {
        $this->authorizeComm($request, 'comm.knowledge.view');

        $articles = CommKnowledgeArticle::query()
            ->when($request->input('q'), fn ($q, $search) => $q->where('title', 'like', '%'.$search.'%'))
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return response()->json(['data' => $articles]);
    }

    public function knowledgeStore(Request $request): JsonResponse
    {
        $this->authorizeComm($request, 'comm.knowledge.manage');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:comm_knowledge_categories,id'],
            'type' => ['nullable', 'string', 'max:20'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $article = CommKnowledgeArticle::create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'body' => $data['body'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'type' => $data['type'] ?? 'article',
            'is_published' => (bool) ($data['is_published'] ?? false),
        ]);

        return response()->json(['data' => $article, 'message' => 'مقاله ایجاد شد.'], 201);
    }

    public function knowledgeCategories(Request $request): JsonResponse
    {
        $this->authorizeComm($request, 'comm.knowledge.view');

        return response()->json(['data' => CommKnowledgeCategory::orderBy('sort_order')->get()]);
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
            'assigned_to' => $c->assigned_to,
            'assignee' => $c->assignee,
            'ticket' => $c->ticket ? [
                'uuid' => $c->ticket->uuid,
                'status' => $c->ticket->status,
                'priority' => $c->ticket->priority,
                'email_alias' => $c->ticket->email_alias,
                'subject' => $c->ticket->subject,
            ] : null,
            'visitor' => $c->visitor,
            'lead' => $c->lead,
            'messages' => $c->messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_type' => $m->sender_type,
                'sender_id' => $m->sender_id,
                'body' => $m->body,
                'message_type' => $m->message_type,
                'is_internal' => $m->is_internal,
                'created_at' => $m->created_at?->toIso8601String(),
                'read_by_visitor_at' => $m->read_by_visitor_at?->toIso8601String(),
                'read_by_operator_at' => $m->read_by_operator_at?->toIso8601String(),
                'attachments' => $m->commAttachments->map(fn ($a) => [
                    'id' => $a->id,
                    'original_name' => $a->original_name,
                    'message_type' => $a->message_type,
                    'mime_type' => $a->mime_type,
                ]),
            ]),
        ];
    }
}
