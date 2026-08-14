<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blog_image_provider_configs')) {
            Schema::create('blog_image_provider_configs', function (Blueprint $table) {
                $table->id();
                $table->string('key', 40)->unique(); // mock|openai|local
                $table->string('label');
                $table->string('model', 80)->nullable();
                $table->string('resolution', 40)->default('1792x1024');
                $table->string('quality', 20)->default('standard');
                $table->string('aspect_ratio', 20)->default('16:9');
                $table->unsignedInteger('timeout_sec')->default(90);
                $table->unsignedTinyInteger('max_retries')->default(3);
                $table->unsignedInteger('cost_per_image_toman')->default(0);
                $table->boolean('is_active')->default(false);
                $table->boolean('is_fallback')->default(false);
                $table->json('meta')->nullable(); // never store API keys here in plain if avoidable — use env
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('blog_image_cost_limits')) {
            Schema::create('blog_image_cost_limits', function (Blueprint $table) {
                $table->id();
                $table->string('scope', 30)->unique(); // per_image|per_article|daily|monthly
                $table->unsignedBigInteger('limit_toman')->default(0);
                $table->unsignedInteger('limit_count')->default(0);
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('blog_image_jobs')) {
            Schema::create('blog_image_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('idempotency_key', 80)->unique();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->foreignId('batch_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('queued');
                // queued|processing|generated|failed|retrying|approved|rejected|published|cancelled
                $table->string('priority', 5)->default('P2');
                $table->unsignedSmallInteger('opportunity_score')->nullable();
                $table->string('image_status', 40)->nullable(); // NO_IMAGE|HERO_MISSING|...
                $table->string('image_type', 40)->default('HERO');
                $table->string('provider', 40)->nullable();
                $table->string('model', 80)->nullable();
                $table->json('brief')->nullable();
                $table->text('prompt')->nullable();
                $table->text('negative_prompt')->nullable();
                $table->string('prompt_version', 40)->nullable();
                $table->string('variant', 10)->default('A');
                $table->string('storage_path')->nullable();
                $table->string('public_url')->nullable();
                $table->string('seo_filename')->nullable();
                $table->string('alt_text', 255)->nullable();
                $table->string('caption', 500)->nullable();
                $table->unsignedSmallInteger('width')->nullable();
                $table->unsignedSmallInteger('height')->nullable();
                $table->unsignedInteger('bytes')->nullable();
                $table->string('mime', 40)->nullable();
                $table->string('format', 20)->nullable();
                $table->boolean('is_ai_generated')->default(true);
                $table->boolean('is_illustrative')->default(true);
                $table->boolean('auto_approve')->default(false);
                $table->unsignedInteger('estimated_cost_toman')->default(0);
                $table->unsignedInteger('actual_cost_toman')->default(0);
                $table->unsignedTinyInteger('retry_count')->default(0);
                $table->timestamp('next_retry_at')->nullable();
                $table->text('error')->nullable();
                $table->string('reject_reason', 80)->nullable();
                $table->text('reject_note')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('previous_media_id')->nullable();
                $table->json('quality')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'priority']);
                $table->index(['blog_post_id', 'status']);
            });
        }

        if (! Schema::hasTable('blog_image_batches')) {
            Schema::create('blog_image_batches', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('status', 30)->default('draft'); // draft|confirmed|running|paused|completed|cancelled
                $table->unsignedInteger('batch_size')->default(50);
                $table->unsignedInteger('total')->default(0);
                $table->unsignedInteger('processed')->default(0);
                $table->unsignedInteger('success')->default(0);
                $table->unsignedInteger('failed')->default(0);
                $table->unsignedInteger('rejected')->default(0);
                $table->unsignedInteger('estimated_cost_toman')->default(0);
                $table->unsignedInteger('actual_cost_toman')->default(0);
                $table->string('provider', 40)->nullable();
                $table->string('model', 80)->nullable();
                $table->string('resolution', 40)->nullable();
                $table->boolean('dry_run')->default(false);
                $table->json('filters')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('blog_image_audits')) {
            Schema::create('blog_image_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->unique()->constrained('blog_posts')->cascadeOnDelete();
                $table->string('image_status', 40);
                $table->string('priority', 5)->nullable();
                $table->unsignedSmallInteger('opportunity_score')->default(0);
                $table->boolean('should_generate')->default(false);
                $table->string('skip_reason', 80)->nullable();
                $table->string('recommended_type', 40)->nullable();
                $table->unsignedTinyInteger('existing_image_count')->default(0);
                $table->boolean('has_hero')->default(false);
                $table->string('hero_url')->nullable();
                $table->json('findings')->nullable();
                $table->timestamp('audited_at')->nullable();
                $table->timestamps();
                $table->index(['image_status', 'priority']);
                $table->index(['should_generate', 'opportunity_score']);
            });
        }

        if (Schema::hasTable('blog_posts')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                if (! Schema::hasColumn('blog_posts', 'cover_alt')) {
                    $table->string('cover_alt', 255)->nullable()->after('cover_image');
                }
                if (! Schema::hasColumn('blog_posts', 'cover_is_ai_generated')) {
                    $table->boolean('cover_is_ai_generated')->default(false)->after('cover_alt');
                }
                if (! Schema::hasColumn('blog_posts', 'cover_is_illustrative')) {
                    $table->boolean('cover_is_illustrative')->default(false)->after('cover_is_ai_generated');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_image_audits');
        Schema::dropIfExists('blog_image_jobs');
        Schema::dropIfExists('blog_image_batches');
        Schema::dropIfExists('blog_image_cost_limits');
        Schema::dropIfExists('blog_image_provider_configs');

        if (Schema::hasTable('blog_posts')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                foreach (['cover_alt', 'cover_is_ai_generated', 'cover_is_illustrative'] as $col) {
                    if (Schema::hasColumn('blog_posts', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
