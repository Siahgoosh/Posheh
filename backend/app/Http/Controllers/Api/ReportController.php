<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Report\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reports->officeDashboard($request->user())]);
    }
}
