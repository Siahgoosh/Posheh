<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTour;
use App\Models\VirtualTourVersion;
use Illuminate\Support\Facades\DB;

class TourVersionService
{
    public function __construct(
        private readonly TourViewerSerializer $serializer,
        private readonly TourActivityLogger $logger,
        private readonly TourImportExportService $importExport,
    ) {}

    public function createSnapshot(User $user, VirtualTour $tour, ?string $label = null): VirtualTourVersion
    {
        $tour->load(['scenes.hotspots', 'media', 'property']);
        $snapshot = $this->importExport->buildExportPayload($tour);
        $encoded = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
        $versionNumber = ($tour->versions()->max('version_number') ?? 0) + 1;

        $version = $tour->versions()->create([
            'created_by' => $user->id,
            'version_number' => $versionNumber,
            'label' => $label ?? "نسخه {$versionNumber}",
            'snapshot' => $snapshot,
            'size_bytes' => strlen($encoded ?: ''),
            'created_at' => now(),
        ]);

        $tour->update(['version' => $versionNumber]);
        $this->pruneOldVersions($tour);
        $this->logger->log($tour, 'version.created', $user, null, ['version' => $versionNumber]);

        return $version;
    }

    public function list(VirtualTour $tour)
    {
        return $tour->versions()
            ->with('creator:id,name')
            ->orderByDesc('version_number')
            ->get();
    }

    public function restore(User $user, VirtualTour $tour, int $versionId): VirtualTour
    {
        $version = $tour->versions()->findOrFail($versionId);

        return DB::transaction(function () use ($user, $tour, $version) {
            $this->createSnapshot($user, $tour, 'پیش از بازیابی');
            $restored = $this->importExport->importFromSnapshot($user, $tour, $version->snapshot);
            $this->logger->log($tour, 'version.restored', $user, null, ['version_id' => $version->id]);

            return $restored;
        });
    }

    private function pruneOldVersions(VirtualTour $tour): void
    {
        $retention = config('virtual-tour.version_retention', 20);
        $ids = $tour->versions()->orderByDesc('version_number')->skip($retention)->pluck('id');
        if ($ids->isNotEmpty()) {
            VirtualTourVersion::whereIn('id', $ids)->delete();
        }
    }
}
