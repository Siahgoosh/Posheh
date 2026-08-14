<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comm_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name');
            $table->string('group', 60)->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('comm_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 40);
            $table->foreignId('permission_id')->constrained('comm_permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role', 'permission_id']);
        });

        Schema::create('comm_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('comm_pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('comm_pipelines')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 40);
            $table->string('color', 20)->default('#22d3ee');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();
            $table->unique(['pipeline_id', 'slug']);
        });

        Schema::create('comm_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 20)->default('#a78bfa');
            $table->timestamps();
        });

        Schema::create('comm_visitors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('province', 80)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('timezone', 60)->nullable();
            $table->string('language', 20)->nullable();
            $table->string('browser', 80)->nullable();
            $table->string('os', 80)->nullable();
            $table->string('device', 40)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('screen_resolution', 20)->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->unsignedInteger('visit_count')->default(0);
            $table->unsignedSmallInteger('lead_score')->default(0);
            $table->json('score_breakdown')->nullable();
            $table->timestamp('first_visit_at')->nullable();
            $table->timestamp('last_visit_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['last_visit_at', 'lead_score']);
        });

        Schema::create('comm_visitor_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('comm_visitors')->cascadeOnDelete();
            $table->string('session_key', 64)->index();
            $table->string('current_page', 500)->nullable();
            $table->json('pages_viewed')->nullable();
            $table->unsignedInteger('time_on_site_seconds')->default(0);
            $table->unsignedTinyInteger('scroll_depth')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->unsignedInteger('mouse_movement_count')->default(0);
            $table->boolean('is_online')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['is_online', 'last_activity_at']);
        });

        Schema::create('comm_visitor_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('comm_visitors')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('comm_visitor_sessions')->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('path', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['visitor_id', 'event_type', 'created_at']);
        });

        Schema::create('comm_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->nullable()->constrained('comm_visitors')->nullOnDelete();
            $table->foreignId('pipeline_stage_id')->nullable()->constrained('comm_pipeline_stages')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('mobile', 20);
            $table->boolean('mobile_verified')->default(false);
            $table->string('email')->nullable();
            $table->string('province', 80)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('office_name')->nullable();
            $table->string('role_title')->nullable();
            $table->unsignedSmallInteger('staff_count')->nullable();
            $table->string('activity_type', 80)->nullable();
            $table->string('request_type', 80)->nullable();
            $table->string('budget', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('source_channel', 40)->default('website');
            $table->string('status', 30)->default('new');
            $table->unsignedSmallInteger('lead_score')->default(0);
            $table->json('score_breakdown')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('country', 80)->nullable();
            $table->json('tracking_snapshot')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'lead_score']);
            $table->index('mobile');
        });

        Schema::create('comm_lead_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('comm_leads')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('comm_tags')->cascadeOnDelete();
            $table->unique(['lead_id', 'tag_id']);
        });

        Schema::create('comm_lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('comm_leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('comm_lead_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('comm_leads')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30)->default('task');
            $table->string('title');
            $table->string('status', 30)->default('open');
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });

        Schema::create('comm_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('visitor_id')->nullable()->constrained('comm_visitors')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('comm_leads')->nullOnDelete();
            $table->string('channel', 30)->default('website');
            $table->string('status', 30)->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->unsignedInteger('unread_visitor')->default(0);
            $table->unsignedInteger('unread_operator')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'last_message_at']);
        });

        Schema::create('comm_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('comm_conversations')->cascadeOnDelete();
            $table->string('sender_type', 20);
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('body');
            $table->text('body_html')->nullable();
            $table->string('message_type', 30)->default('text');
            $table->json('attachments')->nullable();
            $table->foreignId('reply_to_id')->nullable()->constrained('comm_messages')->nullOnDelete();
            $table->boolean('is_internal')->default(false);
            $table->timestamp('read_by_visitor_at')->nullable();
            $table->timestamp('read_by_operator_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('comm_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 80);
            $table->string('entity_type', 60)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comm_audit_logs');
        Schema::dropIfExists('comm_messages');
        Schema::dropIfExists('comm_conversations');
        Schema::dropIfExists('comm_lead_tasks');
        Schema::dropIfExists('comm_lead_notes');
        Schema::dropIfExists('comm_lead_tag');
        Schema::dropIfExists('comm_leads');
        Schema::dropIfExists('comm_visitor_events');
        Schema::dropIfExists('comm_visitor_sessions');
        Schema::dropIfExists('comm_visitors');
        Schema::dropIfExists('comm_tags');
        Schema::dropIfExists('comm_pipeline_stages');
        Schema::dropIfExists('comm_pipelines');
        Schema::dropIfExists('comm_role_permissions');
        Schema::dropIfExists('comm_permissions');
    }
};
