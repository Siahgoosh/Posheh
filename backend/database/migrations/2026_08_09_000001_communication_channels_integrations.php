<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('comm_conversations')) {
            Schema::table('comm_conversations', function (Blueprint $table) {
                if (! Schema::hasColumn('comm_conversations', 'office_id')) {
                    $table->foreignId('office_id')->nullable()->after('id')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('comm_conversations', 'external_chat_id')) {
                    $table->string('external_chat_id', 64)->nullable()->after('channel');
                }
                if (! Schema::hasColumn('comm_conversations', 'external_thread_id')) {
                    $table->string('external_thread_id', 120)->nullable()->after('external_chat_id');
                }
                if (! Schema::hasColumn('comm_conversations', 'ticket_id')) {
                    $table->foreignId('ticket_id')->nullable()->after('lead_id');
                }
            });
        }

        if (Schema::hasTable('comm_leads') && ! Schema::hasColumn('comm_leads', 'office_id')) {
            Schema::table('comm_leads', function (Blueprint $table) {
                $table->foreignId('office_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasTable('comm_tickets')) {
            Schema::create('comm_tickets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('conversation_id')->nullable()->constrained('comm_conversations')->nullOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained('comm_leads')->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('department', 60)->nullable();
                $table->string('priority', 20)->default('normal');
                $table->string('status', 30)->default('open');
                $table->string('subject');
                $table->text('description')->nullable();
                $table->string('email_alias')->nullable()->unique();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->json('sla')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['status', 'priority']);
            });
        }

        if (! Schema::hasTable('comm_attachments')) {
            Schema::create('comm_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->nullable()->constrained('comm_messages')->nullOnDelete();
                $table->foreignId('conversation_id')->nullable()->constrained('comm_conversations')->nullOnDelete();
                $table->string('disk', 20)->default('local');
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->string('message_type', 30)->default('file');
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('comm_telegram_accounts')) {
            Schema::create('comm_telegram_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('visitor_id')->nullable()->constrained('comm_visitors')->nullOnDelete();
                $table->string('account_type', 20)->default('visitor');
                $table->bigInteger('telegram_user_id')->unique();
                $table->bigInteger('telegram_chat_id');
                $table->string('username')->nullable();
                $table->string('first_name')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('comm_telegram_updates')) {
            Schema::create('comm_telegram_updates', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('update_id')->unique();
                $table->json('payload');
                $table->string('status', 20)->default('processed');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('comm_telegram_logs')) {
            Schema::create('comm_telegram_logs', function (Blueprint $table) {
                $table->id();
                $table->string('direction', 10);
                $table->string('method', 40)->nullable();
                $table->json('payload')->nullable();
                $table->json('response')->nullable();
                $table->boolean('ok')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('comm_channel_message_map')) {
            Schema::create('comm_channel_message_map', function (Blueprint $table) {
                $table->id();
                $table->string('channel', 30);
                $table->string('external_message_id', 64);
                $table->foreignId('conversation_id')->constrained('comm_conversations')->cascadeOnDelete();
                $table->foreignId('message_id')->nullable()->constrained('comm_messages')->nullOnDelete();
                $table->string('map_type', 30)->default('message');
                $table->timestamps();
                $table->unique(['channel', 'external_message_id']);
            });
        }

        if (! Schema::hasTable('comm_email_threads')) {
            Schema::create('comm_email_threads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('comm_conversations')->cascadeOnDelete();
                $table->foreignId('ticket_id')->nullable()->constrained('comm_tickets')->nullOnDelete();
                $table->string('alias_email')->unique();
                $table->string('subject')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('comm_email_messages')) {
            Schema::create('comm_email_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('thread_id')->constrained('comm_email_threads')->cascadeOnDelete();
                $table->foreignId('message_id')->nullable()->constrained('comm_messages')->nullOnDelete();
                $table->string('direction', 10);
                $table->string('from_email');
                $table->string('to_email');
                $table->string('subject')->nullable();
                $table->text('body_text')->nullable();
                $table->text('body_html')->nullable();
                $table->string('external_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('comm_knowledge_categories')) {
            Schema::create('comm_knowledge_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('comm_knowledge_articles')) {
            Schema::create('comm_knowledge_articles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->nullable()->constrained('comm_knowledge_categories')->nullOnDelete();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->longText('body')->nullable();
                $table->string('type', 20)->default('article');
                $table->boolean('is_published')->default(false);
                $table->unsignedInteger('helpful_count')->default(0);
                $table->unsignedInteger('views')->default(0);
                $table->unsignedInteger('version')->default(1);
                $table->json('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('comm_automation_rules')) {
            Schema::create('comm_automation_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->json('conditions');
                $table->json('actions');
                $table->unsignedInteger('run_count')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('comm_automation_logs')) {
            Schema::create('comm_automation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rule_id')->constrained('comm_automation_rules')->cascadeOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained('comm_leads')->nullOnDelete();
                $table->string('status', 20);
                $table->json('result')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('comm_ai_suggestions')) {
            Schema::create('comm_ai_suggestions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('comm_conversations')->cascadeOnDelete();
                $table->foreignId('message_id')->nullable()->constrained('comm_messages')->nullOnDelete();
                $table->json('suggestions');
                $table->string('tone', 30)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('comm_ai_summaries')) {
            Schema::create('comm_ai_summaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('comm_conversations')->cascadeOnDelete();
                $table->text('summary');
                $table->string('category', 40)->nullable();
                $table->string('sentiment', 30)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('comm_notifications')) {
            Schema::create('comm_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('channel', 30);
                $table->string('type', 60);
                $table->json('payload');
                $table->string('status', 20)->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('comm_webhook_logs')) {
            Schema::create('comm_webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 30);
                $table->string('event', 80)->nullable();
                $table->json('payload')->nullable();
                $table->boolean('ok')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('comm_message_status')) {
            Schema::create('comm_message_status', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('comm_messages')->cascadeOnDelete();
                $table->string('channel', 30);
                $table->string('status', 30);
                $table->string('external_id', 64)->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('updated_at')->useCurrent();
                $table->unique(['message_id', 'channel']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comm_message_status');
        Schema::dropIfExists('comm_webhook_logs');
        Schema::dropIfExists('comm_notifications');
        Schema::dropIfExists('comm_ai_summaries');
        Schema::dropIfExists('comm_ai_suggestions');
        Schema::dropIfExists('comm_automation_logs');
        Schema::dropIfExists('comm_automation_rules');
        Schema::dropIfExists('comm_knowledge_articles');
        Schema::dropIfExists('comm_knowledge_categories');
        Schema::dropIfExists('comm_email_messages');
        Schema::dropIfExists('comm_email_threads');
        Schema::dropIfExists('comm_channel_message_map');
        Schema::dropIfExists('comm_telegram_logs');
        Schema::dropIfExists('comm_telegram_updates');
        Schema::dropIfExists('comm_telegram_accounts');
        Schema::dropIfExists('comm_attachments');
        Schema::dropIfExists('comm_tickets');
    }
};
