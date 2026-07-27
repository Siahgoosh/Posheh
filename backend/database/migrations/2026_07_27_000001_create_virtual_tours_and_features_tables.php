<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('crm_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#6366f1');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();
        });

        Schema::create('crm_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('crm_stages')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile', 15)->nullable();
            $table->unsignedBigInteger('value')->nullable();
            $table->unsignedTinyInteger('lead_score')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['office_id', 'stage_id']);
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained('crm_deals')->nullOnDelete();
            $table->unsignedBigInteger('total_amount');
            $table->string('status')->default('pending');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('commission_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->decimal('percentage', 5, 2);
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });

        Schema::create('property_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('old_price')->nullable();
            $table->unsignedBigInteger('new_price')->nullable();
            $table->unsignedBigInteger('old_rent')->nullable();
            $table->unsignedBigInteger('new_rent')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('tenant_name')->nullable();
            $table->string('tenant_mobile', 15)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('deposit')->nullable();
            $table->unsignedBigInteger('rent')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'end_date']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedTinyInteger('discount_percent');
            $table->string('plan_slug')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('office_health_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(50);
            $table->json('factors')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_health_scores');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('rental_contracts');
        Schema::dropIfExists('property_price_histories');
        Schema::dropIfExists('commission_splits');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('crm_deals');
        Schema::dropIfExists('crm_stages');
        Schema::dropIfExists('virtual_tour_leads');
        Schema::dropIfExists('virtual_tour_views');
        Schema::dropIfExists('virtual_tour_media');
        Schema::dropIfExists('virtual_tour_hotspots');
        Schema::dropIfExists('virtual_tour_scenes');
        Schema::dropIfExists('virtual_tours');
    }
};
