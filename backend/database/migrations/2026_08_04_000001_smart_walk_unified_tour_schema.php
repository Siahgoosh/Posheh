<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('virtual_tours') && ! Schema::hasColumn('virtual_tours', 'tour_type')) {
            Schema::table('virtual_tours', function (Blueprint $table) {
                $table->string('tour_type', 32)->default('panorama_360')->after('description');
                $table->index('tour_type');
            });
        }

        if (Schema::hasTable('virtual_tour_scenes')) {
            Schema::table('virtual_tour_scenes', function (Blueprint $table) {
                if (! Schema::hasColumn('virtual_tour_scenes', 'scene_type')) {
                    $table->string('scene_type', 32)->default('equirectangular')->after('name');
                }
                if (! Schema::hasColumn('virtual_tour_scenes', 'image_variants')) {
                    $table->json('image_variants')->nullable()->after('thumbnail_path');
                }
                if (! Schema::hasColumn('virtual_tour_scenes', 'metadata')) {
                    $table->json('metadata')->nullable()->after('scene_settings');
                }
            });
        }

        if (Schema::hasTable('virtual_tour_hotspots')) {
            Schema::table('virtual_tour_hotspots', function (Blueprint $table) {
                if (! Schema::hasColumn('virtual_tour_hotspots', 'position_x')) {
                    $table->decimal('position_x', 10, 4)->nullable()->after('pitch');
                }
                if (! Schema::hasColumn('virtual_tour_hotspots', 'position_y')) {
                    $table->decimal('position_y', 10, 4)->nullable()->after('position_x');
                }
                if (! Schema::hasColumn('virtual_tour_hotspots', 'position_z')) {
                    $table->decimal('position_z', 10, 4)->nullable()->after('position_y');
                }
            });

        }
    }

    public function down(): void
    {
        if (Schema::hasTable('virtual_tour_hotspots')) {
            Schema::table('virtual_tour_hotspots', function (Blueprint $table) {
                $columns = ['position_x', 'position_y', 'position_z'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('virtual_tour_hotspots', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('virtual_tour_scenes')) {
            Schema::table('virtual_tour_scenes', function (Blueprint $table) {
                $columns = ['scene_type', 'image_variants', 'metadata'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('virtual_tour_scenes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('virtual_tours') && Schema::hasColumn('virtual_tours', 'tour_type')) {
            Schema::table('virtual_tours', function (Blueprint $table) {
                $table->dropIndex(['tour_type']);
                $table->dropColumn('tour_type');
            });
        }
    }
};
