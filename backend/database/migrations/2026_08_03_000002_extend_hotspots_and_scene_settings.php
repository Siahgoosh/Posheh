<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_tour_hotspots', function (Blueprint $table) {
            if (! Schema::hasColumn('virtual_tour_hotspots', 'label')) {
                $table->string('label')->nullable()->after('title');
            }
            if (! Schema::hasColumn('virtual_tour_hotspots', 'tooltip')) {
                $table->string('tooltip')->nullable()->after('label');
            }
            if (! Schema::hasColumn('virtual_tour_hotspots', 'style')) {
                $table->json('style')->nullable()->after('icon');
            }
            if (! Schema::hasColumn('virtual_tour_hotspots', 'action')) {
                $table->json('action')->nullable()->after('style');
            }
            if (! Schema::hasColumn('virtual_tour_hotspots', 'popup')) {
                $table->json('popup')->nullable()->after('action');
            }
            if (! Schema::hasColumn('virtual_tour_hotspots', 'sort_order')) {
                $table->unsignedSmallInteger('sort_order')->default(0)->after('popup');
            }
        });

        Schema::table('virtual_tour_scenes', function (Blueprint $table) {
            if (! Schema::hasColumn('virtual_tour_scenes', 'default_fov')) {
                $table->unsignedSmallInteger('default_fov')->nullable()->after('default_pitch');
            }
            if (! Schema::hasColumn('virtual_tour_scenes', 'background_music')) {
                $table->string('background_music')->nullable()->after('default_fov');
            }
            if (! Schema::hasColumn('virtual_tour_scenes', 'ambient_sound')) {
                $table->string('ambient_sound')->nullable()->after('background_music');
            }
            if (! Schema::hasColumn('virtual_tour_scenes', 'transition_effect')) {
                $table->string('transition_effect')->default('fade')->after('ambient_sound');
            }
            if (! Schema::hasColumn('virtual_tour_scenes', 'scene_settings')) {
                $table->json('scene_settings')->nullable()->after('transition_effect');
            }
        });
    }

    public function down(): void
    {
        Schema::table('virtual_tour_hotspots', function (Blueprint $table) {
            $table->dropColumn(['label', 'tooltip', 'style', 'action', 'popup', 'sort_order']);
        });

        Schema::table('virtual_tour_scenes', function (Blueprint $table) {
            $table->dropColumn([
                'default_fov',
                'background_music',
                'ambient_sound',
                'transition_effect',
                'scene_settings',
            ]);
        });
    }
};
