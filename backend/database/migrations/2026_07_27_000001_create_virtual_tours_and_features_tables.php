<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('virtual_tours')) {
            return;
        }

        Schema::create('virtual_tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'status']);
        });

        Schema::create('virtual_tour_scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_tour_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('panorama_path');
            $table->string('thumbnail_path')->nullable();
            $table->decimal('default_yaw', 10, 4)->default(0);
            $table->decimal('default_pitch', 10, 4)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->decimal('floor_plan_x', 8, 4)->nullable();
            $table->decimal('floor_plan_y', 8, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('virtual_tour_hotspots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')->constrained('virtual_tour_scenes')->cascadeOnDelete();
            $table->string('type')->default('scene');
            $table->foreignId('target_scene_id')->nullable()->constrained('virtual_tour_scenes')->nullOnDelete();
            $table->decimal('yaw', 10, 4);
            $table->decimal('pitch', 10, 4);
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('link_url')->nullable();
            $table->string('icon')->default('arrow');
            $table->timestamps();
        });

        Schema::create('virtual_tour_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_tour_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('image');
            $table->string('path');
            $table->string('title')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('virtual_tour_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_tour_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('viewed_at');
        });

        Schema::create('virtual_tour_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_tour_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile', 15);
            $table->text('message')->nullable();
            $table->string('source')->default('tour_form');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_tour_leads');
        Schema::dropIfExists('virtual_tour_views');
        Schema::dropIfExists('virtual_tour_media');
        Schema::dropIfExists('virtual_tour_hotspots');
        Schema::dropIfExists('virtual_tour_scenes');
        Schema::dropIfExists('virtual_tours');
    }
};
