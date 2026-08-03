<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('virtual_tours')) {
            return;
        }

        if (Schema::hasColumn('virtual_tours', 'visibility')) {
            DB::table('virtual_tours')
                ->where('status', 'published')
                ->where('visibility', 'private')
                ->update(['visibility' => 'public']);
        }

        if (Schema::hasTable('virtual_tour_scenes') && Schema::hasColumn('virtual_tour_scenes', 'status')) {
            $publishedTourIds = DB::table('virtual_tours')
                ->where('status', 'published')
                ->pluck('id');

            if ($publishedTourIds->isNotEmpty()) {
                DB::table('virtual_tour_scenes')
                    ->whereIn('virtual_tour_id', $publishedTourIds)
                    ->where('is_visible', true)
                    ->where('status', 'draft')
                    ->update(['status' => 'published']);
            }
        }
    }

    public function down(): void
    {
        // Data fix — no rollback
    }
};
