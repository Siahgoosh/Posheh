<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\PlatformUsersExport;
use App\Http\Controllers\Controller;
use App\Services\Admin\MarketingDashboardService;
use App\Services\Admin\PlatformUsersReportService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlatformAnalyticsController extends Controller
{
    public function __construct(
        private readonly PlatformUsersReportService $usersReport,
        private readonly MarketingDashboardService $marketing,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'marketing' => $this->marketing->snapshot(),
                'platform_users' => $this->usersReport->summary(),
            ],
        ]);
    }

    public function exportUsers(): BinaryFileResponse
    {
        $rows = $this->usersReport->enrichedUsers();

        return Excel::download(
            new PlatformUsersExport($rows),
            'posheh-platform-users-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
