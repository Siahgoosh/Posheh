<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('virtual_tour_views')) {
            Schema::table('virtual_tour_views', function (Blueprint $table) {
                if (! Schema::hasColumn('virtual_tour_views', 'session_id')) {
                    $table->string('session_id', 64)->nullable()->after('virtual_tour_id');
                }
                if (! Schema::hasColumn('virtual_tour_views', 'device_type')) {
                    $table->string('device_type', 32)->nullable()->after('referrer');
                }
                if (! Schema::hasColumn('virtual_tour_views', 'screen_width')) {
                    $table->unsignedSmallInteger('screen_width')->nullable()->after('device_type');
                }
                if (! Schema::hasColumn('virtual_tour_views', 'screen_height')) {
                    $table->unsignedSmallInteger('screen_height')->nullable()->after('screen_width');
                }
                if (! Schema::hasColumn('virtual_tour_views', 'duration_seconds')) {
                    $table->unsignedInteger('duration_seconds')->nullable()->after('screen_height');
                }
            });
        }

        if (! Schema::hasTable('virtual_tour_analytics_events')) {
            Schema::create('virtual_tour_analytics_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('virtual_tour_id')->constrained()->cascadeOnDelete();
                $table->string('session_id', 64)->index();
                $table->foreignId('scene_id')->nullable()->constrained('virtual_tour_scenes')->nullOnDelete();
                $table->unsignedBigInteger('hotspot_id')->nullable();
                $table->string('event_type', 64)->index();
                $table->decimal('position_x', 10, 4)->nullable();
                $table->decimal('position_y', 10, 4)->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['virtual_tour_id', 'event_type', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_tour_analytics_events');

        if (Schema::hasTable('virtual_tour_views')) {
            Schema::table('virtual_tour_views', function (Blueprint $table) {
                foreach (['session_id', 'device_type', 'screen_width', 'screen_height', 'duration_seconds'] as $col) {
                    if (Schema::hasColumn('virtual_tour_views', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
