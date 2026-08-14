<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_posts')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                if (! Schema::hasColumn('blog_posts', 'ops_status')) {
                    $table->string('ops_status', 40)->nullable()->after('review_status');
                }
                if (! Schema::hasColumn('blog_posts', 'ops_priority')) {
                    $table->string('ops_priority', 5)->nullable()->after('ops_status'); // P0-P3
                }
                if (! Schema::hasColumn('blog_posts', 'freshness_class')) {
                    $table->string('freshness_class', 20)->nullable()->after('ops_priority'); // fresh|stable|aging|outdated
                }
                if (! Schema::hasColumn('blog_posts', 'orphan_flag')) {
                    $table->boolean('orphan_flag')->default(false)->after('freshness_class');
                }
                if (! Schema::hasColumn('blog_posts', 'auto_publish_allowed')) {
                    $table->boolean('auto_publish_allowed')->default(false)->after('orphan_flag');
                }
                if (! Schema::hasColumn('blog_posts', 'style_profile_id')) {
                    $table->unsignedBigInteger('style_profile_id')->nullable()->after('auto_publish_allowed');
                }
            });
        }

        if (! Schema::hasTable('content_style_profiles')) {
            Schema::create('content_style_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('tone', 80)->nullable();
                $table->string('sentence_length', 40)->nullable();
                $table->string('vocabulary', 80)->nullable();
                $table->string('formality', 40)->nullable();
                $table->string('cta_style', 80)->nullable();
                $table->text('persian_terminology')->nullable();
                $table->json('sample_slugs')->nullable();
                $table->json('anti_patterns')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('content_ai_task_configs')) {
            Schema::create('content_ai_task_configs', function (Blueprint $table) {
                $table->id();
                $table->string('task_key', 60)->unique();
                $table->string('label');
                $table->string('provider', 40)->default('mock');
                $table->string('model', 80)->nullable();
                $table->decimal('temperature', 3, 2)->default(0.30);
                $table->unsignedInteger('max_tokens')->default(2000);
                $table->text('system_prompt')->nullable();
                $table->unsignedInteger('timeout_sec')->default(60);
                $table->unsignedTinyInteger('retry_max')->default(3);
                $table->unsignedInteger('cost_limit_per_job')->default(0); // toman; 0 = unlimited within global
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('content_ai_cost_limits')) {
            Schema::create('content_ai_cost_limits', function (Blueprint $table) {
                $table->id();
                $table->string('scope', 30); // daily|monthly|per_article|per_user
                $table->unsignedBigInteger('limit_toman')->default(0);
                $table->unsignedInteger('limit_tokens')->default(0);
                $table->boolean('is_active')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->unique(['scope']);
            });
        }

        if (! Schema::hasTable('content_ai_jobs')) {
            Schema::create('content_ai_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('idempotency_key', 80)->unique();
                $table->string('type', 60); // research|draft|seo_audit|fact_check|internal_linking|image_suggestion|refresh|repurpose|brief|outline
                $table->foreignId('blog_post_id')->nullable()->constrained('blog_posts')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('queued'); // queued|running|completed|failed|retrying|cancelled
                $table->string('provider', 40)->nullable();
                $table->string('model', 80)->nullable();
                $table->decimal('temperature', 3, 2)->nullable();
                $table->unsignedInteger('max_tokens')->nullable();
                $table->json('input_payload')->nullable();
                $table->json('output_payload')->nullable();
                $table->unsignedInteger('prompt_tokens')->default(0);
                $table->unsignedInteger('completion_tokens')->default(0);
                $table->unsignedInteger('total_tokens')->default(0);
                $table->unsignedInteger('estimated_cost_toman')->default(0);
                $table->unsignedInteger('actual_cost_toman')->default(0);
                $table->decimal('confidence', 4, 3)->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->unsignedTinyInteger('retry_count')->default(0);
                $table->timestamp('next_retry_at')->nullable();
                $table->text('error')->nullable();
                $table->string('prompt_version', 60)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'type']);
                $table->index(['blog_post_id', 'type']);
                $table->index(['created_at']);
            });
        }

        if (! Schema::hasTable('content_claims')) {
            Schema::create('content_claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->foreignId('job_id')->nullable()->constrained('content_ai_jobs')->nullOnDelete();
                $table->text('claim');
                $table->string('source')->nullable();
                $table->string('source_type', 40)->nullable(); // official|government|primary|trusted|secondary|unknown
                $table->date('source_date')->nullable();
                $table->unsignedTinyInteger('confidence')->nullable();
                $table->string('risk_level', 20)->default('low'); // low|medium|high|critical
                $table->string('status', 30)->default('pending'); // pending|approved|rejected|needs_source|expired
                $table->boolean('requires_human')->default(false);
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_note')->nullable();
                $table->timestamps();
                $table->index(['blog_post_id', 'status']);
                $table->index(['risk_level', 'status']);
            });
        }

        if (! Schema::hasTable('content_review_comments')) {
            Schema::create('content_review_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('section', 80)->nullable();
                $table->string('block_ref', 120)->nullable();
                $table->text('body');
                $table->string('status', 20)->default('open'); // open|resolved
                $table->timestamps();
                $table->index(['blog_post_id', 'status']);
            });
        }

        if (! Schema::hasTable('content_approval_logs')) {
            Schema::create('content_approval_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 40); // approve|reject|request_changes|publish|schedule|archive|merge|restore
                $table->unsignedBigInteger('version_id')->nullable();
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['blog_post_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('content_repurpose_assets')) {
            Schema::create('content_repurpose_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->foreignId('job_id')->nullable()->constrained('content_ai_jobs')->nullOnDelete();
                $table->string('channel', 40); // telegram|instagram|whatsapp|newsletter|linkedin|faq|snippet|reel|carousel
                $table->text('content');
                $table->string('status', 20)->default('draft'); // draft|approved|published|rejected
                $table->json('quality_notes')->nullable();
                $table->timestamps();
                $table->index(['blog_post_id', 'channel']);
            });
        }

        if (! Schema::hasTable('content_ops_reports')) {
            Schema::create('content_ops_reports', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20); // weekly|monthly
                $table->date('period_start');
                $table->date('period_end');
                $table->json('payload');
                $table->timestamps();
                $table->unique(['type', 'period_start', 'period_end']);
            });
        }

        if (! Schema::hasTable('content_image_briefs')) {
            Schema::create('content_image_briefs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->string('purpose', 40); // hero|content|diagram|screenshot|infographic
                $table->string('placement', 80)->nullable();
                $table->string('subject')->nullable();
                $table->string('aspect_ratio', 20)->nullable();
                $table->string('alt_text', 255)->nullable();
                $table->string('caption', 500)->nullable();
                $table->text('prompt')->nullable();
                $table->string('status', 20)->default('suggested'); // suggested|approved|rejected|used
                $table->boolean('auto_publish_image')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('content_link_suggestions')) {
            Schema::create('content_link_suggestions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->string('target_slug');
                $table->string('anchor')->nullable();
                $table->unsignedTinyInteger('relevance')->default(50);
                $table->unsignedTinyInteger('context_score')->default(50);
                $table->unsignedTinyInteger('business_value')->default(50);
                $table->unsignedTinyInteger('destination_quality')->default(50);
                $table->unsignedTinyInteger('total_score')->default(50);
                $table->boolean('auto_insert_eligible')->default(false);
                $table->string('status', 20)->default('suggested'); // suggested|accepted|rejected|inserted
                $table->timestamps();
                $table->unique(['blog_post_id', 'target_slug']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_link_suggestions');
        Schema::dropIfExists('content_image_briefs');
        Schema::dropIfExists('content_ops_reports');
        Schema::dropIfExists('content_repurpose_assets');
        Schema::dropIfExists('content_approval_logs');
        Schema::dropIfExists('content_review_comments');
        Schema::dropIfExists('content_claims');
        Schema::dropIfExists('content_ai_jobs');
        Schema::dropIfExists('content_ai_cost_limits');
        Schema::dropIfExists('content_ai_task_configs');
        Schema::dropIfExists('content_style_profiles');

        if (Schema::hasTable('blog_posts')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                foreach (['ops_status', 'ops_priority', 'freshness_class', 'orphan_flag', 'auto_publish_allowed', 'style_profile_id'] as $col) {
                    if (Schema::hasColumn('blog_posts', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
