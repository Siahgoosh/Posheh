<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Services\Admin\EmailCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEmailMarketingController extends Controller
{
    public function __construct(private readonly EmailCampaignService $service) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->list(),
            'meta' => ['segments' => EmailCampaignService::SEGMENTS],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body_html' => ['required', 'string'],
            'body_text' => ['nullable', 'string'],
            'segment' => ['nullable', 'string', 'in:all_managers,trial_offices,paid_offices,all_users'],
        ]);

        $campaign = $this->service->create($validated, $request->user()?->id);

        return response()->json(['data' => $campaign, 'message' => 'کمپین ایجاد شد.'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);
        $validated = $request->validate([
            'subject' => ['sometimes', 'string', 'max:200'],
            'body_html' => ['sometimes', 'string'],
            'body_text' => ['nullable', 'string'],
            'segment' => ['nullable', 'string', 'in:all_managers,trial_offices,paid_offices,all_users'],
        ]);

        $campaign = $this->service->update($campaign, $validated);

        return response()->json(['data' => $campaign, 'message' => 'کمپین به‌روز شد.']);
    }

    public function send(int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);
        $campaign = $this->service->send($campaign);

        return response()->json([
            'data' => $campaign,
            'message' => "ارسال انجام شد: {$campaign->sent_count} موفق، {$campaign->failed_count} ناموفق.",
        ]);
    }
}
