<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('content_planner_templates')) {
            Schema::create('content_planner_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('content_type', 40)->default('post');
                $table->json('platforms')->nullable();
                $table->string('goal', 40)->nullable();
                $table->text('hook')->nullable();
                $table->longText('body')->nullable();
                $table->text('cta')->nullable();
                $table->longText('caption')->nullable();
                $table->json('hashtags')->nullable();
                $table->text('visual_idea')->nullable();
                $table->string('overlay_text', 255)->nullable();
                $table->boolean('is_shared')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('social_contents')) {
            Schema::create('social_contents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->string('topic')->nullable();
                $table->string('content_type', 40)->default('post');
                $table->json('platforms');
                $table->string('goal', 40)->nullable();
                $table->text('hook')->nullable();
                $table->longText('body')->nullable();
                $table->text('cta')->nullable();
                $table->longText('caption')->nullable();
                $table->json('hashtags')->nullable();
                $table->text('visual_idea')->nullable();
                $table->string('overlay_text', 255)->nullable();
                $table->string('location', 255)->nullable();
                $table->text('notes')->nullable();
                $table->timestampTz('scheduled_at_utc')->nullable()->index();
                $table->string('timezone', 64)->default('Asia/Tehran');
                $table->string('status', 40)->default('draft')->index();
                $table->boolean('reminder_enabled')->default(true);
                $table->unsignedInteger('reminder_offset_minutes')->default(0);
                $table->unsignedInteger('reminder_custom_minutes')->nullable();
                $table->timestampTz('published_at_utc')->nullable();
                $table->timestampTz('cancelled_at_utc')->nullable();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['office_id', 'scheduled_at_utc']);
                $table->index(['office_id', 'status']);
                $table->index(['user_id', 'scheduled_at_utc']);
                $table->index(['status', 'scheduled_at_utc']);
            });
        }

        if (! Schema::hasTable('social_content_media')) {
            Schema::create('social_content_media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('content_id')->constrained('social_contents')->cascadeOnDelete();
                $table->string('disk', 40)->default('public');
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->string('mime_type', 80)->nullable();
                $table->string('media_type', 20)->default('image');
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('content_reminders')) {
            Schema::create('content_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('content_id')->constrained('social_contents')->cascadeOnDelete();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('reminder_type', 40)->default('publish');
                $table->string('channel', 20)->default('sms');
                $table->unsignedInteger('offset_minutes')->default(0);
                $table->timestampTz('scheduled_at_utc')->index();
                $table->timestampTz('sent_at_utc')->nullable();
                $table->string('status', 30)->default('pending')->index();
                $table->string('failure_reason', 100)->nullable();
                $table->text('provider_response')->nullable();
                $table->unsignedTinyInteger('attempt_count')->default(0);
                $table->string('idempotency_key', 80)->unique();
                $table->timestampTz('locked_at')->nullable();
                $table->string('locked_by', 64)->nullable();
                $table->timestamps();

                $table->index(['status', 'scheduled_at_utc']);
                $table->index(['office_id', 'status']);
                $table->index(['content_id', 'status']);
            });
        }

        if (! Schema::hasTable('content_planner_audits')) {
            Schema::create('content_planner_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('content_id')->nullable()->constrained('social_contents')->nullOnDelete();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 80);
                $table->json('before')->nullable();
                $table->json('after')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['office_id', 'created_at']);
                $table->index(['content_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('content_planner_user_settings')) {
            Schema::create('content_planner_user_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->boolean('sms_reminder_enabled')->default(true);
                $table->boolean('in_app_notification_enabled')->default(true);
                $table->string('sms_mobile', 20)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_planner_user_settings');
        Schema::dropIfExists('content_planner_audits');
        Schema::dropIfExists('content_reminders');
        Schema::dropIfExists('social_content_media');
        Schema::dropIfExists('social_contents');
        Schema::dropIfExists('content_planner_templates');
    }
};
