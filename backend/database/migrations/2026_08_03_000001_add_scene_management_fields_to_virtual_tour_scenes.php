<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_tour_scenes', function (Blueprint $table) {
            if (! Schema::hasColumn('virtual_tour_scenes', 'status')) {
                $table->string('status')->default('draft')->after('name');
            }
            if (! Schema::hasColumn('virtual_tour_scenes', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('status');
            }
            if (! Schema::hasColumn('virtual_tour_scenes', 'is_visible')) {
                $table->boolean('is_visible')->default(true)->after('is_default');
            }
            if (! Schema::hasColumn('virtual_tour_scenes', 'panorama_width')) {
                $table->unsignedInteger('panorama_width')->nullable()->after('panorama_path');
            }
            if (! Schema::hasColumn('virtual_tour_scenes', 'panorama_height')) {
                $table->unsignedInteger('panorama_height')->nullable()->after('panorama_width');
            }
            if (! Schema::hasColumn('virtual_tour_scenes', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('panorama_height');
            }
        });
    }

    public function down(): void
    {
        Schema::table('virtual_tour_scenes', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'is_default',
                'is_visible',
                'panorama_width',
                'panorama_height',
                'file_size',
            ]);
        });
    }
};
