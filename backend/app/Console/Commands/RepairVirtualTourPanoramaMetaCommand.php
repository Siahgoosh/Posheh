<?php

namespace App\Console\Commands;

use App\Models\VirtualTourScene;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RepairVirtualTourPanoramaMetaCommand extends Command
{
    protected $signature = 'virtual-tour:repair-panorama-meta {--dry-run : Show changes without saving}';

    protected $description = 'Fix scene panorama_width/height when thumbnail-sized metadata breaks 360 zoom or Smart Walk layout';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');
        $fixed = 0;
        $cleared = 0;

        VirtualTourScene::query()
            ->whereNotNull('panorama_path')
            ->orderBy('id')
            ->chunkById(100, function ($scenes) use ($disk, $dryRun, &$fixed, &$cleared) {
                foreach ($scenes as $scene) {
                    $w = (int) ($scene->panorama_width ?? 0);
                    $h = (int) ($scene->panorama_height ?? 0);

                    if ($w >= 512 && $h >= 256) {
                        continue;
                    }

                    $path = $scene->panorama_path;
                    if (! $path || ! $disk->exists($path)) {
                        if ($w > 0 || $h > 0) {
                            $this->line("Scene {$scene->id}: clear invalid meta ({$w}x{$h}), file missing");
                            if (! $dryRun) {
                                $scene->update(['panorama_width' => null, 'panorama_height' => null]);
                            }
                            $cleared++;
                        }
                        continue;
                    }

                    $fullPath = $disk->path($path);
                    $info = @getimagesize($fullPath);
                    if (! $info || $info[0] < 512 || $info[1] < 256) {
                        $this->line("Scene {$scene->id}: clear invalid meta ({$w}x{$h}), image unreadable or too small");
                        if (! $dryRun) {
                            $scene->update(['panorama_width' => null, 'panorama_height' => null]);
                        }
                        $cleared++;
                        continue;
                    }

                    $this->line("Scene {$scene->id}: {$w}x{$h} -> {$info[0]}x{$info[1]}");
                    if (! $dryRun) {
                        $scene->update([
                            'panorama_width' => $info[0],
                            'panorama_height' => $info[1],
                        ]);
                    }
                    $fixed++;
                }
            });

        $this->info("Done. fixed={$fixed}, cleared={$cleared}".($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }
}
