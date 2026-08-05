<?php

namespace App\Http\Controllers\Api\VirtualTour;

use App\Http\Controllers\Controller;
use App\Modules\VirtualTour\Application\Services\TourActivityLogger;
use App\Modules\VirtualTour\Application\Services\TourDashboardService;
use App\Modules\VirtualTour\Application\Services\TourImportExportService;
use App\Modules\VirtualTour\Application\Services\TourLifecycleService;
use App\Modules\VirtualTour\Application\Services\TourManager;
use App\Modules\VirtualTour\Application\Services\TourVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VirtualTourEnterpriseController extends Controller
{
    public function __construct(
        private readonly TourManager $tourManager,
        private readonly TourDashboardService $dashboard,
        private readonly TourLifecycleService $lifecycle,
        private readonly TourImportExportService $importExport,
        private readonly TourVersionService $versionService,
        private readonly TourActivityLogger $activityLogger,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->stats($request->user())]);
    }

    public function list(Request $request): JsonResponse
    {
        $tours = $this->dashboard->list(
            $request->user(),
            $request->query('status'),
            $request->query('search'),
        );

        return response()->json(['data' => $tours]);
    }

    public function duplicate(Request $request, int $id): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);
        $copy = $this->lifecycle->duplicate($request->user(), $tour);

        return response()->json([
            'data' => $this->tourManager->toPayload($copy),
            'message' => 'تور کپی شد.',
        ], 201);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        try {
            $tour = $this->tourManager->findForOffice($request->user(), $id);
            $tour = $this->lifecycle->publish($request->user(), $tour);

            return response()->json(['data' => $this->tourManager->toPayload($tour), 'message' => 'تور منتشر شد.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'خطا در انتشار تور. لطفاً دوباره تلاش کنید.',
            ], 500);
        }
    }

    public function unpublish(Request $request, int $id): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);
        $tour = $this->lifecycle->unpublish($request->user(), $tour);

        return response()->json(['data' => $this->tourManager->toPayload($tour), 'message' => 'تور به پیش‌نویس تغییر کرد.']);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);
        $tour = $this->lifecycle->archive($request->user(), $tour);

        return response()->json(['data' => $this->tourManager->toPayload($tour), 'message' => 'تور بایگانی شد.']);
    }

    public function unarchive(Request $request, int $id): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);
        $tour = $this->lifecycle->restoreFromArchive($request->user(), $tour);

        return response()->json(['data' => $this->tourManager->toPayload($tour), 'message' => 'تور از بایگانی خارج شد.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);
        $this->lifecycle->delete($request->user(), $tour);

        return response()->json(['message' => 'تور حذف شد.']);
    }

    public function updateSharing(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'visibility' => ['sometimes', 'in:public,private'],
            'access_password' => ['nullable', 'string', 'min:4', 'max:64'],
            'expires_at' => ['nullable', 'date'],
            'regenerate_token' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'array'],
        ]);

        $tour = $this->tourManager->findForOffice($request->user(), $id);
        $tour = $this->lifecycle->updateSharing($request->user(), $tour, $data);

        return response()->json(['data' => $this->tourManager->toPayload($tour), 'message' => 'تنظیمات اشتراک‌گذاری به‌روزرسانی شد.']);
    }

    public function exportJson(Request $request, int $id): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);
        $tour->load(['scenes.hotspots', 'media', 'property']);

        return response()->json([
            'data' => json_decode($this->importExport->exportJson($tour), true),
            'filename' => "tour-{$tour->slug}.json",
        ]);
    }

    public function exportZip(Request $request, int $id): BinaryFileResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);
        $path = $this->importExport->exportZip($tour);

        return response()->download($path, "tour-{$tour->slug}.zip")->deleteFileAfterSend();
    }

    public function import(Request $request): JsonResponse
    {
        if ($request->hasFile('file')) {
            $content = file_get_contents($request->file('file')->getRealPath());
            $payload = json_decode($content, true);
        } else {
            $payload = $request->validate(['tour' => ['required', 'array']])['tour'];
            $payload = ['tour' => $payload];
        }

        if (! $payload) {
            return response()->json(['message' => 'فایل JSON نامعتبر است.'], 422);
        }

        $tour = $this->importExport->importJson($request->user(), $payload);

        return response()->json([
            'data' => $this->tourManager->toPayload($tour),
            'message' => 'تور با موفقیت وارد شد.',
        ], 201);
    }

    public function backup(Request $request, int $id): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);
        $version = $this->versionService->createSnapshot($request->user(), $tour, $request->input('label', 'پشتیبان'));

        return response()->json(['data' => $version, 'message' => 'پشتیبان ایجاد شد.'], 201);
    }

    public function versions(Request $request, int $id): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);

        return response()->json(['data' => $this->versionService->list($tour)]);
    }

    public function restoreVersion(Request $request, int $id, int $versionId): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);
        $tour = $this->versionService->restore($request->user(), $tour, $versionId);

        return response()->json([
            'data' => $this->tourManager->toPayload($tour),
            'message' => 'نسخه بازیابی شد.',
        ]);
    }

    public function activity(Request $request, int $id): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);

        return response()->json(['data' => $this->activityLogger->recent($tour)]);
    }
}
