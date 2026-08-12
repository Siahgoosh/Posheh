<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogAuditLog;
use App\Models\BlogPost;
use App\Models\BlogPostVersion;
use App\Services\Blog\BlogAiAssistantService;
use App\Services\Blog\BlogAuditLogger;
use App\Services\Blog\BlogContentQualityScorer;
use App\Services\Blog\BlogPublishChecklistService;
use App\Services\Blog\BlogPublishService;
use App\Services\Blog\BlogQualityGate;
use App\Services\Blog\BlogReadingTimeCalculator;
use App\Services\Blog\BlogSeoAnalyzer;
use App\Services\Blog\BlogSitemapService;
use App\Services\Blog\BlogVersioningService;
use App\Services\Blog\PersianTextNormalizer;
use App\Services\ContentOps\EditorialWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BlogAdminController extends Controller
{
    public function __construct(
        private readonly BlogSeoAnalyzer $seoAnalyzer,
        private readonly BlogContentQualityScorer $qualityScorer,
        private readonly BlogQualityGate $qualityGate,
        private readonly BlogVersioningService $versioning,
        private readonly BlogPublishService $publisher,
        private readonly BlogReadingTimeCalculator $readingTime,
        private readonly BlogSitemapService $sitemap,
        private readonly BlogAiAssistantService $ai,
        private readonly BlogPublishChecklistService $checklist,
        private readonly PersianTextNormalizer $normalizer,
        private readonly BlogAuditLogger $audit,
        private readonly EditorialWorkflowService $editorial,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::query()
            ->when($request->input('review_status'), fn ($q, $s) => $q->where('review_status', $s))
            ->when($request->input('category_slug'), fn ($q, $s) => $q->where('category_slug', $s))
            ->when($request->input('author_name'), fn ($q, $s) => $q->where('author_name', 'like', '%'.$s.'%'))
            ->when($request->input('q'), function ($q, $term) {
                $q->where(function ($qq) use ($term) {
                    $qq->where('title', 'like', '%'.$term.'%')
                        ->orWhere('slug', 'like', '%'.$term.'%')
                        ->orWhere('focus_keyword', 'like', '%'.$term.'%');
                });
            })
            ->when($request->filled('is_published'), fn ($q) => $q->where('is_published', $request->boolean('is_published')))
            ->when($request->boolean('rebuild_locked'), fn ($q) => $q->where('rebuild_locked', true))
            ->when($request->input('robots'), fn ($q, $r) => $q->where('robots_directive', 'like', '%'.$r.'%'))
            ->when($request->input('exclude_trash', '1') !== '0', fn ($q) => $q->where('review_status', '!=', BlogPost::REVIEW_TRASH))
            ->orderByDesc('updated_at')
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return response()->json([
            'data' => $posts->getCollection()->map(fn (BlogPost $post) => $this->adminItem($post)),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function health(): JsonResponse
    {
        $base = BlogPost::query();

        return response()->json([
            'data' => [
                'total' => (clone $base)->count(),
                'published' => (clone $base)->where('is_published', true)->count(),
                'draft' => (clone $base)->where('is_published', false)->count(),
                'needs_review' => (clone $base)->whereIn('review_status', ['seo_review', 'content_review', 'in_review'])->count(),
                'rebuild_locked' => (clone $base)->where('rebuild_locked', true)->count(),
                'missing_image' => (clone $base)->where(fn ($q) => $q->whereNull('cover_image')->orWhere('cover_image', ''))->count(),
                'missing_meta' => (clone $base)->where(fn ($q) => $q->whereNull('meta_description')->orWhere('meta_description', ''))->count(),
                'missing_faq' => (clone $base)->where(fn ($q) => $q->whereNull('faq')->orWhere('faq', '[]'))->count(),
                'trash' => (clone $base)->where('review_status', BlogPost::REVIEW_TRASH)->count(),
                'by_review_status' => BlogPost::query()
                    ->selectRaw('review_status, count(*) as total')
                    ->groupBy('review_status')
                    ->pluck('total', 'review_status'),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);

        return response()->json([
            'data' => $this->adminItem($post, includeContent: true),
            'seo' => $this->seoAnalyzer->analyze($post->toArray()),
            'quality' => $this->qualityScorer->score($post->toArray()),
            'gate' => $this->qualityGate->evaluate($post->toArray(), forPublish: false),
            'checklist' => $this->checklist->evaluate($post->toArray()),
            'versions' => $post->versions()->orderByDesc('version')->limit(20)->get(['id', 'version', 'note', 'created_by', 'created_at']),
            'audit' => BlogAuditLog::query()
                ->where('blog_post_id', $post->id)
                ->orderByDesc('id')
                ->limit(30)
                ->get(['id', 'action', 'user_id', 'created_at', 'ip']),
            'ai_actions' => $this->ai->availableActions(),
            'note' => 'امتیازها راهنمای داخلی هستند — Google Score نیستند.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $tagIds = $data['_tag_ids'] ?? null;
        unset($data['_tag_ids']);
        $data['quality_scores'] = ['after' => $this->qualityScorer->score($data)];
        $data['review_status'] = $data['review_status'] ?? BlogPost::REVIEW_DRAFT;
        $post = BlogPost::create($data);
        if (is_array($tagIds)) {
            $post->tags()->sync($tagIds);
        }
        $this->versioning->snapshot($post, 'create', $request->user()?->email);
        $this->audit->log($post, 'article_created', null, ['slug' => $post->slug], $request);
        $this->sitemap->invalidate();

        return response()->json([
            'data' => $this->adminItem($post, includeContent: true),
            'seo' => $this->seoAnalyzer->analyze($post->toArray()),
            'quality' => $this->qualityScorer->score($post->toArray()),
            'gate' => $this->qualityGate->evaluate($post->toArray(), forPublish: (bool) $post->is_published),
            'checklist' => $this->checklist->evaluate($post->toArray()),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);

        if ($conflict = $this->editLockConflict($post, $request)) {
            return $conflict;
        }

        $data = $this->validated($request, $post);
        $slugChanged = isset($data['slug']) && $data['slug'] !== $post->slug;

        if ($slugChanged && $post->is_published && ! $request->boolean('confirm_slug_change')) {
            return response()->json([
                'message' => 'این URL ممکن است ایندکس/ارجاع شده باشد. برای تغییر slug، confirm_slug_change=1 بفرستید تا ۳۰۱ ساخته شود.',
                'code' => 'slug_change_protected',
                'old_slug' => $post->slug,
                'new_slug' => $data['slug'],
            ], 422);
        }

        if ($slugChanged && $request->boolean('confirm_slug_change') && $post->is_published) {
            $from = '/blog/'.$post->slug;
            $to = '/blog/'.$data['slug'];
            if ($from === $to || str_contains($to, $from)) {
                return response()->json(['message' => 'Redirect loop risk detected.', 'code' => 'redirect_loop'], 422);
            }
            \App\Models\BlogRedirect::query()->updateOrCreate(
                ['from_path' => $from],
                [
                    'to_path' => $to,
                    'status_code' => 301,
                    'is_active' => true,
                    'note' => 'slug_change',
                ]
            );
        }

        if (! empty($data['is_published'])) {
            $gate = $this->qualityGate->evaluate(array_merge($post->toArray(), $data), forPublish: true);
            if (! $gate['passed']) {
                return response()->json([
                    'message' => 'انتشار مسدود شد. موارد زیر را برطرف کنید.',
                    'gate' => $gate,
                ], 422);
            }
            $data['review_status'] = BlogPost::REVIEW_PUBLISHED;
            if (($data['robots_directive'] ?? null) === 'noindex,nofollow') {
                $data['robots_directive'] = 'index,follow';
            }
        }

        $this->versioning->snapshot($post, 'before-update', $request->user()?->email);
        $data['quality_scores'] = [
            'before' => $post->quality_scores['after'] ?? $this->qualityScorer->score($post->toArray()),
            'after' => $this->qualityScorer->score(array_merge($post->toArray(), $data)),
        ];
        $tagIds = $data['_tag_ids'] ?? null;
        unset($data['_tag_ids']);
        $old = ['slug' => $post->slug, 'review_status' => $post->review_status];
        $post->update($data);
        if (is_array($tagIds)) {
            $post->tags()->sync($tagIds);
        }
        $this->audit->log($post, 'article_updated', $old, ['slug' => $post->slug, 'review_status' => $post->review_status], $request);
        $this->sitemap->invalidate();

        return response()->json([
            'data' => $this->adminItem($post->fresh(), includeContent: true),
            'seo' => $this->seoAnalyzer->analyze($post->fresh()->toArray()),
            'quality' => $this->qualityScorer->score($post->fresh()->toArray()),
            'gate' => $this->qualityGate->evaluate($post->fresh()->toArray(), forPublish: (bool) $post->fresh()->is_published),
            'checklist' => $this->checklist->evaluate($post->fresh()->toArray()),
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $this->versioning->snapshot($post, 'before-trash', $request->user()?->email);
        $post->update([
            'is_published' => false,
            'review_status' => BlogPost::REVIEW_TRASH,
            'robots_directive' => 'noindex,nofollow',
        ]);
        $this->audit->log($post, 'article_trashed', null, ['id' => $post->id], $request);
        $this->sitemap->invalidate();

        return response()->json(['message' => 'مقاله به سطل زباله منتقل شد (حذف دائم نیست).']);
    }

    public function restoreTrash(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $post->update([
            'review_status' => BlogPost::REVIEW_DRAFT,
            'robots_directive' => 'noindex,nofollow',
        ]);
        $this->audit->log($post, 'article_restored_from_trash', null, null, $request);

        return response()->json(['data' => $this->adminItem($post->fresh(), includeContent: true)]);
    }

    public function forceDestroy(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        if ($post->review_status !== BlogPost::REVIEW_TRASH && ! $request->boolean('confirm')) {
            return response()->json(['message' => 'حذف دائم نیاز به confirm=1 یا وضعیت trash دارد.'], 422);
        }
        $this->audit->log($post, 'article_force_deleted', ['slug' => $post->slug], null, $request);
        $post->delete();
        $this->sitemap->invalidate();

        return response()->json(['message' => 'مقاله برای همیشه حذف شد.']);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $opsGate = $this->editorial->publishGate($post);
        if (! ($opsGate['passed'] ?? false) && ! $request->boolean('force')) {
            return response()->json([
                'message' => 'Content OS publish gate failed — AI cannot override.',
                'ops_gate' => $opsGate,
            ], 422);
        }
        $checklist = $this->checklist->evaluate($post->toArray());
        if (! $checklist['passed'] && ! $request->boolean('force')) {
            return response()->json([
                'message' => 'Publish checklist failed — AI cannot override. Human must fix blockers or send force=1.',
                'checklist' => $checklist,
            ], 422);
        }
        $result = $this->publisher->publish($post, [], $request);
        if (! ($result['ok'] ?? false)) {
            return response()->json(['message' => 'انتشار مسدود شد.', 'gate' => $result['gate'], 'checklist' => $checklist], 422);
        }

        $this->editorial->afterPublish($result['post'], $request->user()?->id);

        return response()->json([
            'data' => $this->adminItem($result['post'], includeContent: true),
            'gate' => $result['gate'],
            'checklist' => $checklist,
            'ops_gate' => $opsGate,
            'automation' => [
                'sitemap_invalidated' => true,
                'note' => 'قرارگیری در Sitemap تضمین Index گوگل نیست.',
            ],
        ]);
    }

    public function unpublish(Request $request, int $id): JsonResponse
    {
        $post = $this->publisher->unpublish(BlogPost::findOrFail($id), $request);

        return response()->json(['data' => $this->adminItem($post, includeContent: true)]);
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);
        $result = $this->publisher->schedule(BlogPost::findOrFail($id), $data['scheduled_at'], $request);
        if (! ($result['ok'] ?? false)) {
            return response()->json(['message' => 'زمان‌بندی مسدود شد.', 'gate' => $result['gate']], 422);
        }

        return response()->json([
            'data' => $this->adminItem($result['post'], includeContent: true),
            'timezone' => $data['timezone'] ?? config('app.timezone'),
        ]);
    }

    public function previewToken(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $token = $this->publisher->ensurePreviewToken($post);

        return response()->json([
            'data' => [
                'token' => $token,
                'url' => '/blog/preview/'.$token,
            ],
        ]);
    }

    public function restoreVersion(Request $request, int $id, int $versionId): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $version = BlogPostVersion::where('blog_post_id', $post->id)->where('id', $versionId)->firstOrFail();
        $this->versioning->snapshot($post, 'before-restore', $request->user()?->email);
        $restored = $this->versioning->restore($post, $version);
        $this->audit->log($post, 'revision_restored', ['version_id' => $versionId], null, $request);
        $this->sitemap->invalidate();

        return response()->json(['data' => $this->adminItem($restored, includeContent: true)]);
    }

    public function compareVersions(int $id, int $versionId): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $version = BlogPostVersion::where('blog_post_id', $post->id)->where('id', $versionId)->firstOrFail();

        return response()->json([
            'data' => [
                'current' => [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'meta_title' => $post->meta_title,
                    'meta_description' => $post->meta_description,
                    'content_length' => mb_strlen(strip_tags((string) $post->content)),
                ],
                'revision' => [
                    'id' => $version->id,
                    'version' => $version->version,
                    'note' => $version->note,
                    'created_by' => $version->created_by,
                    'created_at' => $version->created_at,
                    'snapshot' => $version->snapshot ?? $version->payload ?? null,
                ],
            ],
        ]);
    }

    public function submitReview(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $post->update(['review_status' => BlogPost::REVIEW_IN_REVIEW]);
        $this->audit->log($post, 'submitted_for_review', null, null, $request);

        return response()->json(['data' => $this->adminItem($post->fresh())]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $post->update([
            'review_status' => BlogPost::REVIEW_APPROVED,
            'last_reviewed_at' => now(),
        ]);
        $this->audit->log($post, 'article_approved', null, null, $request);

        return response()->json(['data' => $this->adminItem($post->fresh())]);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $post->update([
            'is_published' => false,
            'review_status' => BlogPost::REVIEW_ARCHIVED,
            'robots_directive' => 'noindex,follow',
        ]);
        $this->audit->log($post, 'article_archived', null, null, $request);
        $this->sitemap->invalidate();

        return response()->json(['data' => $this->adminItem($post->fresh())]);
    }

    public function categories(): JsonResponse
    {
        $cms = \App\Models\BlogCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'parent_id']);

        if ($cms->isNotEmpty()) {
            return response()->json([
                'data' => $cms->map(fn ($c) => [
                    'id' => $c->id,
                    'slug' => $c->slug,
                    'label' => $c->name,
                    'parent_id' => $c->parent_id,
                ]),
            ]);
        }

        return response()->json([
            'data' => collect(\App\Http\Controllers\Api\Blog\BlogController::CATEGORIES)
                ->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label])
                ->values(),
        ]);
    }

    public function analyzeSeo(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['nullable', 'string'],
            'slug' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'string'],
            'category_slug' => ['nullable', 'string'],
            'pillar_slug' => ['nullable', 'string'],
            'faq' => ['nullable', 'array'],
            'related_slugs' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $this->seoAnalyzer->analyze($request->all()),
            'note' => 'Internal SEO Guidance — not a Google Score.',
        ]);
    }

    public function publishChecklist(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->checklist->evaluate($request->all()),
        ]);
    }

    public function aiAssist(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'max:64'],
            'payload' => ['nullable', 'array'],
        ]);

        try {
            $result = $this->ai->assist($data['action'], $data['payload'] ?? []);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'AI Assistant Temporarily Unavailable',
                'error' => 'ai_unavailable',
                'note' => 'ادامه کار دستی ممکن است — AI تصمیم‌گیرنده نهایی نیست و چیزی را Publish نمی‌کند.',
            ], 503);
        }

        return response()->json([
            'data' => $result,
            'note' => 'AI is an assistant only — never auto-publishes. Human approval required.',
            'prompt_version' => $result['prompt_version'] ?? BlogAiAssistantService::PROMPT_VERSION,
        ]);
    }

    public function aiActions(): JsonResponse
    {
        return response()->json([
            'data' => $this->ai->availableActions(),
            'prompt_versions' => [
                'CONTENT_WRITER_V1',
                'CONTENT_EDITOR_V1',
                'SEO_ANALYZER_V1',
            ],
            'policy' => 'AI never auto-publishes titles, meta, facts, or articles without human approval.',
        ]);
    }

    public function autosave(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        if ($conflict = $this->editLockConflict($post, $request)) {
            return $conflict;
        }
        $payload = $request->validate([
            'payload' => ['required', 'array'],
        ]);
        $post->update([
            'autosave_payload' => [
                'saved_at' => now()->toIso8601String(),
                'user_id' => $request->user()?->id,
                'data' => $payload['payload'],
            ],
            'edit_locked_by' => $request->user()?->id,
            'edit_locked_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'ok' => true,
                'saved_at' => now()->toIso8601String(),
                'recoverable' => true,
            ],
        ]);
    }

    public function recoverAutosave(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);

        return response()->json([
            'data' => $post->autosave_payload,
            'note' => 'Recover Draft — apply manually; does not overwrite until you Save.',
        ]);
    }

    public function acquireLock(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $uid = $request->user()?->id;
        $stale = ! $post->edit_locked_at || $post->edit_locked_at->lt(now()->subMinutes(15));
        if ($post->edit_locked_by && $post->edit_locked_by !== $uid && ! $stale && ! $request->boolean('force')) {
            return response()->json([
                'message' => 'Conflict Warning: کاربر دیگری در حال ویرایش است.',
                'locked_by' => $post->edit_locked_by,
                'locked_at' => $post->edit_locked_at?->toIso8601String(),
                'code' => 'edit_lock_conflict',
            ], 409);
        }
        $post->update(['edit_locked_by' => $uid, 'edit_locked_at' => now()]);

        return response()->json(['data' => ['locked' => true, 'by' => $uid]]);
    }

    public function releaseLock(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        if ($post->edit_locked_by === $request->user()?->id || $request->boolean('force')) {
            $post->update(['edit_locked_by' => null, 'edit_locked_at' => null]);
        }

        return response()->json(['data' => ['released' => true]]);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120', 'mimes:jpeg,jpg,png,webp,gif'],
            'alt' => ['nullable', 'string', 'max:200'],
        ]);

        return response()->json([
            'data' => $this->storeUploadedImage($request->file('image'), $request->input('alt', '')),
        ], 201);
    }

    public function uploadCover(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:8192', 'mimes:jpeg,jpg,png,webp,gif'],
        ]);

        $data = $this->storeUploadedImage($request->file('image'), 'cover');

        return response()->json([
            'data' => [
                'url' => $data['url'],
                'path' => $data['path'],
            ],
        ], 201);
    }

    /** @return array{url: string, path: string, html: string} */
    private function storeUploadedImage($file, string $alt): array
    {
        $original = $file->getClientOriginalName();
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $original) ?: 'image';
        $path = $file->storeAs('blog', time().'-'.$safe, 'public');
        $url = Storage::disk('public')->url($path);

        return [
            'url' => $url,
            'path' => $path,
            'html' => $alt !== '' && $alt !== 'cover'
                ? '<img src="'.e($url).'" alt="'.e($alt).'" loading="lazy" />'
                : '<img src="'.e($url).'" alt="" loading="lazy" />',
        ];
    }

    private function editLockConflict(BlogPost $post, Request $request): ?JsonResponse
    {
        $uid = $request->user()?->id;
        if (! $post->edit_locked_by || $post->edit_locked_by === $uid) {
            return null;
        }
        if ($post->edit_locked_at && $post->edit_locked_at->gt(now()->subMinutes(15)) && ! $request->boolean('force_lock')) {
            return response()->json([
                'message' => 'Conflict Warning: آخرین ذخیره ممکن است اطلاعات ویرایشگر دیگر را نابود کند. force_lock=1 برای ادامه.',
                'code' => 'edit_lock_conflict',
                'locked_by' => $post->edit_locked_by,
                'locked_at' => $post->edit_locked_at?->toIso8601String(),
            ], 409);
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?BlogPost $existing = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blog_posts', 'slug')->ignore($existing?->id),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'author_name' => ['nullable', 'string', 'max:100'],
            'reading_time' => ['nullable', 'integer', 'min:1', 'max:120'],
            'word_count' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'category_slug' => ['nullable', 'string', 'max:50'],
            'category_label' => ['nullable', 'string', 'max:100'],
            'pillar_slug' => ['nullable', 'string', 'max:100'],
            'faq' => ['nullable', 'array'],
            'faq.*.question' => ['required_with:faq', 'string', 'max:300'],
            'faq.*.answer' => ['required_with:faq', 'string', 'max:2000'],
            'related_slugs' => ['nullable', 'array'],
            'related_slugs.*' => ['string', 'max:255'],
            'cta_text' => ['nullable', 'string', 'max:200'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'cro_cta_key' => ['nullable', 'string', 'max:80'],
            'focus_keyword' => ['nullable', 'string', 'max:120'],
            'secondary_keywords' => ['nullable', 'array'],
            'secondary_keywords.*' => ['string', 'max:120'],
            'search_intent' => ['nullable', 'string', 'max:40', Rule::in(BlogPost::SEARCH_INTENTS)],
            'business_intent' => ['nullable', 'string', 'max:40'],
            'funnel_stage' => ['nullable', 'string', 'max:40'],
            'content_type' => ['nullable', 'string', 'max:40', Rule::in(BlogPost::CONTENT_TYPES)],
            'schema_type' => ['nullable', 'string', 'max:40'],
            'review_status' => ['nullable', 'string', 'max:40'],
            'rebuild_locked' => ['sometimes', 'boolean'],
            'canonical_url' => ['nullable', 'string', 'max:500'],
            'robots_directive' => ['nullable', 'string', 'max:60'],
            'image_prompt' => ['nullable', 'string', 'max:2000'],
            'scheduled_at' => ['nullable', 'date'],
            'content_brief' => ['nullable', 'array'],
            'sources' => ['nullable', 'array'],
            'sources.*.title' => ['nullable', 'string', 'max:255'],
            'sources.*.url' => ['nullable', 'string', 'max:500'],
            'sources.*.publisher' => ['nullable', 'string', 'max:120'],
            'sources.*.published_date' => ['nullable', 'string', 'max:40'],
            'sources.*.access_date' => ['nullable', 'string', 'max:40'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_editors_pick' => ['sometimes', 'boolean'],
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'blog_author_id' => ['nullable', 'integer', 'exists:blog_authors,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:blog_tags,id'],
            'normalize_persian' => ['sometimes', 'boolean'],
        ]);

        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);
        $doNormalize = (bool) ($data['normalize_persian'] ?? true);
        unset($data['normalize_persian']);

        if (! empty($data['category_slug']) && empty($data['category_label'])) {
            $cmsCat = \App\Models\BlogCategory::query()->where('slug', $data['category_slug'])->first();
            $data['category_label'] = $cmsCat?->name
                ?? \App\Http\Controllers\Api\Blog\BlogController::CATEGORIES[$data['category_slug']]
                ?? null;
        }

        if (empty($data['slug'])) {
            $data['slug'] = BlogPost::makeSlug($data['title']);
        }

        if ($doNormalize) {
            foreach (['title', 'excerpt', 'meta_title', 'meta_description', 'og_title', 'og_description', 'cta_text'] as $field) {
                if (! empty($data[$field]) && is_string($data[$field])) {
                    $data[$field] = $this->normalizer->normalizeEditorial($data[$field]);
                }
            }
            if (! empty($data['content'])) {
                $data['content'] = $this->normalizer->normalizeEditorial($data['content']);
            }
        }

        if (! empty($data['content'])) {
            $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($data['content'])) ?? '');
            $data['word_count'] = $data['word_count'] ?? (str_word_count($plain) ?: max(1, count(preg_split('/\s+/u', $plain) ?: [])));
            if (empty($data['reading_time'])) {
                $data['reading_time'] = $this->readingTime->calculate($data['content']);
            }
            // Only bump SEO lastmod when content actually changes meaningfully
            if (! $existing || ($existing->content !== $data['content'])) {
                $data['content_updated_at'] = now();
            }
        }

        if (($data['is_published'] ?? false) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (array_key_exists('is_published', $data) && ! ($data['is_published'] ?? false)) {
            $data['published_at'] = null;
            $data['review_status'] = $data['review_status'] ?? BlogPost::REVIEW_DRAFT;
        }

        $data['_tag_ids'] = $tagIds;

        return $data;
    }

    private function adminItem(BlogPost $post, bool $includeContent = false): array
    {
        $item = [
            'id' => $post->id,
            'slug' => $post->slug,
            'category_slug' => $post->category_slug,
            'category_label' => $post->category_label,
            'pillar_slug' => $post->pillar_slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'cover_image' => $post->cover_image,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'canonical_url' => $post->canonical_url,
            'robots_directive' => $post->robots_directive,
            'keywords' => $post->keywords,
            'focus_keyword' => $post->focus_keyword,
            'secondary_keywords' => $post->secondary_keywords ?? [],
            'search_intent' => $post->search_intent,
            'business_intent' => $post->business_intent,
            'funnel_stage' => $post->funnel_stage,
            'content_type' => $post->content_type,
            'schema_type' => $post->schema_type,
            'faq' => $post->faq ?? [],
            'related_slugs' => $post->related_slugs ?? [],
            'sources' => $post->sources ?? [],
            'cta_text' => $post->cta_text,
            'cta_url' => $post->cta_url,
            'cro_cta_key' => $post->cro_cta_key,
            'author_name' => $post->author_name,
            'reading_time' => $post->reading_time,
            'word_count' => $post->word_count,
            'views' => $post->views,
            'is_published' => $post->is_published,
            'review_status' => $post->review_status,
            'rebuild_locked' => (bool) $post->rebuild_locked,
            'quality_scores' => $post->quality_scores,
            'content_brief' => $post->content_brief,
            'image_prompt' => $post->image_prompt,
            'is_featured' => (bool) $post->is_featured,
            'is_editors_pick' => (bool) $post->is_editors_pick,
            'blog_category_id' => $post->blog_category_id,
            'blog_author_id' => $post->blog_author_id,
            'tag_ids' => $post->relationLoaded('tags')
                ? $post->tags->pluck('id')
                : $post->tags()->pluck('blog_tags.id'),
            'og_title' => $post->og_title,
            'og_description' => $post->og_description,
            'og_image' => $post->og_image,
            'scheduled_at' => $post->scheduled_at?->toIso8601String(),
            'published_at' => $post->published_at?->toIso8601String(),
            'updated_at' => $post->updated_at?->toIso8601String(),
            'content_updated_at' => $post->content_updated_at?->toIso8601String(),
            'last_reviewed_at' => $post->last_reviewed_at?->toIso8601String(),
            'edit_locked_by' => $post->edit_locked_by,
            'edit_locked_at' => $post->edit_locked_at?->toIso8601String(),
            'has_autosave' => ! empty($post->autosave_payload),
        ];

        if ($includeContent) {
            $item['content'] = $post->content;
            $item['autosave_payload'] = $post->autosave_payload;
        }

        return $item;
    }
}
