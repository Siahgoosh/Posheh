<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Office\OfficeSiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeSiteController extends Controller
{
    public function __construct(private readonly OfficeSiteService $sites) {}

    public function show(string $subdomain): JsonResponse
    {
        return response()->json(['data' => $this->sites->publicSite($subdomain)]);
    }

    public function visitRequest(Request $request, string $subdomain): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'property_id' => ['nullable', 'integer'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->sites->submitVisitRequest($subdomain, $data);

        return response()->json(['message' => 'درخواست بازدید ثبت شد. به‌زودی با شما تماس می‌گیریم.']);
    }
}
