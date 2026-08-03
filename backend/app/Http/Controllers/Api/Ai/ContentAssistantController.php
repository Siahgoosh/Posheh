<?php

namespace App\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\Ai\ContentAssistantService;
use App\Services\Ai\OfficeContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentAssistantController extends Controller
{
    public function __construct(
        private readonly ContentAssistantService $assistant,
        private readonly OfficeContextBuilder $contextBuilder,
    ) {}

    public function types(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['id' => 'daily_plan', 'label' => 'برنامه روزانه', 'icon' => 'sun', 'group' => 'روزانه'],
                ['id' => 'reels_script', 'label' => 'سناریوی ریلز', 'icon' => 'video', 'group' => 'محتوا'],
                ['id' => 'story_script', 'label' => 'سناریوی استوری', 'icon' => 'layers', 'group' => 'محتوا'],
                ['id' => 'caption', 'label' => 'کپشن‌نویس', 'icon' => 'pen', 'group' => 'محتوا'],
                ['id' => 'hashtags', 'label' => 'هشتگ', 'icon' => 'hash', 'group' => 'محتوا'],
                ['id' => 'cover_text', 'label' => 'متن کاور ریلز', 'icon' => 'image', 'group' => 'محتوا'],
                ['id' => 'video_script', 'label' => 'اسکریپت ویدئو', 'icon' => 'film', 'group' => 'محتوا'],
                ['id' => 'content_ideas', 'label' => '۳۰ ایده محتوا', 'icon' => 'lightbulb', 'group' => 'برنامه‌ریزی'],
                ['id' => 'content_calendar', 'label' => 'تقویم ۳۰ روزه', 'icon' => 'calendar', 'group' => 'برنامه‌ریزی'],
                ['id' => 'publish_time', 'label' => 'زمان انتشار', 'icon' => 'clock', 'group' => 'برنامه‌ریزی'],
                ['id' => 'ad_text', 'label' => 'متن آگهی', 'icon' => 'file', 'group' => 'فروش'],
                ['id' => 'whatsapp_message', 'label' => 'پیام واتساپ', 'icon' => 'message', 'group' => 'فروش'],
                ['id' => 'promote_property', 'label' => 'پیشنهاد تبلیغ فایل', 'icon' => 'target', 'group' => 'فروش'],
                ['id' => 'market_analysis', 'label' => 'تحلیل بازار', 'icon' => 'chart', 'group' => 'تحلیل'],
                ['id' => 'stale_property_analysis', 'label' => 'تحلیل فایل راکد', 'icon' => 'alert', 'group' => 'تحلیل'],
                ['id' => 'campaign_suggestion', 'label' => 'پیشنهاد کمپین', 'icon' => 'rocket', 'group' => 'تحلیل'],
                ['id' => 'seasonal_campaign', 'label' => 'کمپین فصلی', 'icon' => 'gift', 'group' => 'تحلیل'],
            ],
        ]);
    }

    public function context(Request $request): JsonResponse
    {
        $office = $request->user()->office;
        abort_unless($office, 403);

        return response()->json(['data' => $this->contextBuilder->build($office)]);
    }

    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'tone' => ['sometimes', 'string', 'in:friendly,formal,luxury,investment,educational'],
            'property_id' => ['nullable', 'integer'],
            'channel' => ['nullable', 'string'],
            'message_type' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'in:30,60,90'],
            'regenerate' => ['sometimes', 'boolean'],
        ]);

        $generation = $this->assistant->generate($request->user(), $data['type'], $data);

        return response()->json([
            'data' => [
                'id' => $generation->id,
                'type' => $generation->type,
                'output' => $generation->output,
                'meta' => $generation->meta,
                'provider' => $generation->provider,
                'created_at' => $generation->created_at?->toIso8601String(),
            ],
            'message' => 'محتوا تولید شد.',
        ]);
    }

    public function dailyBriefing(Request $request): JsonResponse
    {
        $generation = $this->assistant->dailyBriefing($request->user());

        return response()->json(['data' => $generation]);
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->assistant->history($request->user())]);
    }

    public function properties(Request $request): JsonResponse
    {
        $officeId = $request->user()->office_id;
        $props = Property::where('office_id', $officeId)
            ->where('status', \App\Enums\PropertyStatus::Active)
            ->withCount('media')
            ->latest()
            ->limit(50)
            ->get(['id', 'code', 'title', 'type', 'city', 'district', 'price', 'area', 'rooms']);

        return response()->json([
            'data' => $props->map(fn (Property $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'label' => trim(sprintf(
                    'کد %s — %s %s%s%s',
                    $p->code,
                    $p->type?->label() ?? 'ملک',
                    $p->district ?: $p->city ?: '',
                    $p->area ? " · {$p->area}م" : '',
                    $p->price ? ' · '.number_format($p->price).' ت' : '',
                )),
            ]),
        ]);
    }
}
