<?php

namespace App\Http\Controllers\Api\ContentPlanner;

use App\Http\Controllers\Controller;
use App\Services\ContentPlanner\ContentPlannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentPlannerController extends Controller
{
    public function __construct(private readonly ContentPlannerService $service) {}

    public function meta(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->meta()]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->dashboard($request->user())]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $mode = (string) $request->query('mode', 'week');
        $anchor = $request->query('anchor');

        return response()->json([
            'data' => $this->service->calendar($request->user(), $mode, $anchor ? (string) $anchor : null),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->list($request->user(), $request->only([
                'status', 'content_type', 'platform', 'q', 'from', 'to', 'per_page',
            ]))
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $content = $this->service->find($request->user(), $id);

        return response()->json(['data' => $this->service->serialize($content)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        if ($request->hasFile('media')) {
            $data['media_files'] = $request->file('media');
            if (! is_array($data['media_files'])) {
                $data['media_files'] = [$data['media_files']];
            }
        }

        try {
            $content = $this->service->create($request->user(), $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'ذخیره محتوا انجام نشد. لطفاً دوباره تلاش کنید.'], 500);
        }

        return response()->json([
            'data' => $this->service->serialize($content),
            'message' => 'محتوا ذخیره شد.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validated($request, false);
        if ($request->hasFile('media')) {
            $data['media_files'] = $request->file('media');
            if (! is_array($data['media_files'])) {
                $data['media_files'] = [$data['media_files']];
            }
        }
        if ($request->filled('remove_media_ids')) {
            $data['remove_media_ids'] = array_map('intval', (array) $request->input('remove_media_ids'));
        }

        try {
            $content = $this->service->update($request->user(), $id, $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'ذخیره محتوا انجام نشد. لطفاً دوباره تلاش کنید.'], 500);
        }

        return response()->json([
            'data' => $this->service->serialize($content),
            'message' => 'محتوا به‌روزرسانی شد.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->delete($request->user(), $id);

        return response()->json(['message' => 'محتوا حذف شد.']);
    }

    public function duplicate(Request $request, int $id): JsonResponse
    {
        $content = $this->service->duplicate($request->user(), $id);

        return response()->json([
            'data' => $this->service->serialize($content),
            'message' => 'کپی محتوا ایجاد شد.',
        ], 201);
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'jalali_date' => ['nullable', 'string'],
            'time' => ['nullable', 'string'],
            'scheduled_local' => ['nullable', 'string'],
            'reminder_enabled' => ['nullable', 'boolean'],
            'reminder_offset_minutes' => ['nullable', 'integer', 'min:0'],
            'reminder_custom_minutes' => ['nullable', 'integer', 'min:1'],
        ]);
        $content = $this->service->schedule($request->user(), $id, $data);

        return response()->json([
            'data' => $this->service->serialize($content),
            'message' => 'محتوا زمان‌بندی شد.',
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $content = $this->service->cancel($request->user(), $id);

        return response()->json([
            'data' => $this->service->serialize($content),
            'message' => 'زمان‌بندی لغو شد.',
        ]);
    }

    public function markPublished(Request $request, int $id): JsonResponse
    {
        $content = $this->service->markPublished($request->user(), $id);

        return response()->json([
            'data' => $this->service->serialize($content),
            'message' => 'محتوا به‌عنوان منتشرشده علامت خورد.',
        ]);
    }

    public function moveDay(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $content = $this->service->moveDay($request->user(), $id, $data['date']);

        return response()->json([
            'data' => $this->service->serialize($content),
            'message' => 'روز انتشار تغییر کرد.',
        ]);
    }

    public function quickAdd(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.content_type' => ['nullable', 'string'],
            'items.*.platforms' => ['nullable', 'array'],
            'items.*.jalali_date' => ['nullable', 'string'],
            'items.*.scheduled_local' => ['nullable', 'string'],
            'items.*.time' => ['nullable', 'string'],
            'items.*.reminder_enabled' => ['nullable', 'boolean'],
            'items.*.reminder_offset_minutes' => ['nullable', 'integer'],
        ]);
        $created = $this->service->quickAdd($request->user(), $data['items']);

        return response()->json(['data' => $created, 'message' => 'محتواهای سریع ذخیره شدند.'], 201);
    }

    public function templates(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->templates($request->user())]);
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'content_type' => ['nullable', 'string'],
            'platforms' => ['nullable', 'array'],
            'goal' => ['nullable', 'string'],
            'hook' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'cta' => ['nullable', 'string'],
            'caption' => ['nullable', 'string'],
            'hashtags' => ['nullable'],
            'visual_idea' => ['nullable', 'string'],
            'overlay_text' => ['nullable', 'string'],
            'is_shared' => ['nullable', 'boolean'],
        ]);
        $tpl = $this->service->saveTemplate($request->user(), $data);

        return response()->json(['data' => $tpl, 'message' => 'قالب ذخیره شد.'], 201);
    }

    public function settings(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->getSettings($request->user())]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sms_reminder_enabled' => ['nullable', 'boolean'],
            'in_app_notification_enabled' => ['nullable', 'boolean'],
            'sms_mobile' => ['nullable', 'string', 'max:20'],
        ]);

        return response()->json([
            'data' => $this->service->updateSettings($request->user(), $data),
            'message' => 'تنظیمات یادآوری ذخیره شد.',
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $comm = app(\App\Services\Crm\CrmCommunicationService::class);
        $items = $comm->listNotifications($request->user())
            ->filter(fn ($n) => ($n->meta['category'] ?? null) === 'content_planner' || str_contains((string) $n->title, 'انتشار محتوا'))
            ->values();

        return response()->json(['data' => $items]);
    }

    private function validated(Request $request, bool $creating = true): array
    {
        $types = implode(',', array_keys(config('content_planner.content_types')));
        $platforms = implode(',', array_keys(config('content_planner.platforms')));
        $goals = implode(',', array_keys(config('content_planner.goals')));
        $statuses = implode(',', array_keys(config('content_planner.statuses')));

        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
            'content_type' => [$creating ? 'required' : 'sometimes', 'string', 'in:'.$types],
            'platforms' => [$creating ? 'required' : 'sometimes', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:'.$platforms],
            'goal' => ['nullable', 'string', 'in:'.$goals],
            'hook' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'cta' => ['nullable', 'string'],
            'caption' => ['nullable', 'string'],
            'hashtags' => ['nullable'],
            'visual_idea' => ['nullable', 'string'],
            'overlay_text' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:'.$statuses],
            'jalali_date' => ['nullable', 'string'],
            'time' => ['nullable', 'string'],
            'scheduled_local' => ['nullable', 'string'],
            'reminder_enabled' => ['nullable', 'boolean'],
            'reminder_offset_minutes' => ['nullable', 'integer', 'min:0'],
            'reminder_custom_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'template_id' => ['nullable', 'integer'],
            'media.*' => ['nullable', 'file', 'mimes:'.implode(',', config('content_planner.media_mimes')), 'max:'.(int) config('content_planner.media_max_kb')],
        ]);
    }
}
