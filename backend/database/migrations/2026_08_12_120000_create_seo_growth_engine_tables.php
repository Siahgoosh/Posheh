<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_gsc_metrics')) {
            Schema::table('blog_gsc_metrics', function (Blueprint $table) {
                if (! Schema::hasColumn('blog_gsc_metrics', 'query_raw')) {
                    $table->string('query_raw')->nullable()->after('query');
                }
                if (! Schema::hasColumn('blog_gsc_metrics', 'query_normalized')) {
                    $table->string('query_normalized')->nullable()->index()->after('query_raw');
                }
                if (! Schema::hasColumn('blog_gsc_metrics', 'device')) {
                    $table->string('device', 32)->nullable()->after('position');
                }
                if (! Schema::hasColumn('blog_gsc_metrics', 'country')) {
                    $table->string('country', 8)->nullable()->after('device');
                }
                if (! Schema::hasColumn('blog_gsc_metrics', 'search_appearance')) {
                    $table->string('search_appearance', 64)->nullable()->after('country');
                }
            });
        }

        if (! Schema::hasTable('seo_search_queries')) {
            Schema::create('seo_search_queries', function (Blueprint $table) {
                $table->id();
                $table->string('query_raw');
                $table->string('query_normalized')->index();
                $table->string('intent')->nullable();
                $table->string('topic')->nullable()->index();
                $table->string('entity')->nullable();
                $table->string('funnel_stage')->nullable();
                $table->unsignedTinyInteger('business_value')->default(50);
                $table->string('current_page_url')->nullable();
                $table->string('potential_page_url')->nullable();
                $table->string('cluster_key')->nullable()->index();
                $table->unsignedInteger('clicks_28d')->default(0);
                $table->unsignedInteger('impressions_28d')->default(0);
                $table->decimal('ctr_28d', 8, 4)->nullable();
                $table->decimal('position_28d', 8, 2)->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
                $table->unique('query_normalized');
            });
        }

        if (! Schema::hasTable('seo_page_daily')) {
            Schema::create('seo_page_daily', function (Blueprint $table) {
                $table->id();
                $table->date('date')->index();
                $table->string('page_url')->index();
                $table->string('slug')->nullable()->index();
                $table->unsignedInteger('clicks')->default(0);
                $table->unsignedInteger('impressions')->default(0);
                $table->decimal('ctr', 8, 4)->nullable();
                $table->decimal('position', 8, 2)->nullable();
                $table->timestamps();
                $table->unique(['date', 'page_url']);
            });
        }

        if (! Schema::hasTable('seo_content_health')) {
            Schema::create('seo_content_health', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->nullable()->constrained('blog_posts')->nullOnDelete();
                $table->string('slug')->index();
                $table->string('lifecycle')->default('CREATED');
                $table->string('portfolio')->default('MAINTAIN');
                $table->string('funnel_stage')->nullable();
                $table->string('health')->default('unknown'); // healthy|needs_update|critical|unknown
                $table->unsignedSmallInteger('quality_score')->nullable();
                $table->unsignedSmallInteger('topic_score')->nullable();
                $table->unsignedInteger('clicks_28d')->default(0);
                $table->unsignedInteger('impressions_28d')->default(0);
                $table->decimal('ctr_28d', 8, 4)->nullable();
                $table->decimal('position_28d', 8, 2)->nullable();
                $table->decimal('clicks_prev_28d', 12, 2)->nullable();
                $table->decimal('decay_pct', 8, 2)->nullable();
                $table->string('decay_reason')->nullable();
                $table->boolean('orphan_risk')->default(false);
                $table->json('meta')->nullable();
                $table->timestamp('analyzed_at')->nullable();
                $table->timestamps();
                $table->unique('slug');
            });
        }

        if (! Schema::hasTable('seo_opportunities')) {
            Schema::create('seo_opportunities', function (Blueprint $table) {
                $table->id();
                $table->string('type')->index(); // quick_win|striking_distance|content_gap|decay|cannibalization|ctr|link|pillar|zero_result
                $table->string('title');
                $table->string('page_url')->nullable()->index();
                $table->string('slug')->nullable()->index();
                $table->string('query')->nullable();
                $table->string('topic')->nullable();
                $table->text('evidence')->nullable();
                $table->text('recommendation')->nullable();
                $table->string('expected_benefit')->nullable();
                $table->string('risk')->default('low');
                $table->string('effort')->default('medium');
                $table->unsignedTinyInteger('impact')->default(50);
                $table->unsignedTinyInteger('confidence')->default(50);
                $table->decimal('priority_score', 10, 4)->default(0)->index();
                $table->string('status')->default('NEW')->index();
                $table->json('payload')->nullable();
                $table->timestamp('detected_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seo_recommendations')) {
            Schema::create('seo_recommendations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seo_opportunity_id')->nullable()->constrained('seo_opportunities')->nullOnDelete();
                $table->string('action_type')->index(); // title_opt|meta_opt|expand|faq|internal_link|rewrite|merge|redirect|noindex|refresh
                $table->string('automation_level')->default('ASSISTED'); // MANUAL|ASSISTED|AUTOMATIC
                $table->string('priority')->default('MEDIUM'); // URGENT|HIGH|MEDIUM|LOW
                $table->text('problem');
                $table->text('evidence');
                $table->text('recommendation');
                $table->string('expected_benefit')->nullable();
                $table->string('risk')->default('low');
                $table->string('effort')->default('medium');
                $table->decimal('priority_score', 10, 4)->default(0)->index();
                $table->string('status')->default('NEW')->index(); // NEW|REVIEWED|APPROVED|REJECTED|EXECUTED|ROLLED_BACK
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamp('rolled_back_at')->nullable();
                $table->json('before_snapshot')->nullable();
                $table->json('after_snapshot')->nullable();
                $table->text('result_notes')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seo_content_experiments')) {
            Schema::create('seo_content_experiments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->nullable()->constrained('blog_posts')->nullOnDelete();
                $table->string('slug')->nullable()->index();
                $table->string('experiment_type')->default('title');
                $table->string('hypothesis')->nullable();
                $table->string('variant_a');
                $table->string('variant_b')->nullable();
                $table->string('active_variant')->default('A');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status')->default('draft'); // draft|running|completed|rolled_back
                $table->json('metrics')->nullable();
                $table->text('decision')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seo_internal_searches')) {
            Schema::create('seo_internal_searches', function (Blueprint $table) {
                $table->id();
                $table->string('query_raw');
                $table->string('query_normalized')->index();
                $table->unsignedInteger('results_count')->default(0);
                $table->boolean('zero_result')->default(false)->index();
                $table->string('clicked_slug')->nullable();
                $table->string('ip_hash', 64)->nullable();
                $table->timestamp('searched_at')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seo_topic_scores')) {
            Schema::create('seo_topic_scores', function (Blueprint $table) {
                $table->id();
                $table->string('topic')->unique();
                $table->unsignedInteger('article_count')->default(0);
                $table->unsignedInteger('clicks_28d')->default(0);
                $table->unsignedInteger('impressions_28d')->default(0);
                $table->decimal('avg_position', 8, 2)->nullable();
                $table->boolean('has_pillar')->default(false);
                $table->unsignedInteger('supporting_count')->default(0);
                $table->unsignedTinyInteger('coverage_score')->default(0);
                $table->unsignedTinyInteger('quality_score')->default(0);
                $table->unsignedTinyInteger('authority_score')->default(0);
                $table->decimal('topic_score', 8, 2)->default(0);
                $table->json('gaps')->nullable();
                $table->timestamp('analyzed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seo_alerts')) {
            Schema::create('seo_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('severity')->default('medium')->index(); // critical|high|medium|low
                $table->string('kind')->index();
                $table->string('title');
                $table->text('detail')->nullable();
                $table->string('page_url')->nullable();
                $table->boolean('is_resolved')->default(false)->index();
                $table->timestamp('resolved_at')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seo_report_snapshots')) {
            Schema::create('seo_report_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('period_type'); // weekly|monthly
                $table->date('period_start');
                $table->date('period_end');
                $table->json('payload');
                $table->timestamps();
                $table->index(['period_type', 'period_start']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_report_snapshots');
        Schema::dropIfExists('seo_alerts');
        Schema::dropIfExists('seo_topic_scores');
        Schema::dropIfExists('seo_internal_searches');
        Schema::dropIfExists('seo_content_experiments');
        Schema::dropIfExists('seo_recommendations');
        Schema::dropIfExists('seo_opportunities');
        Schema::dropIfExists('seo_content_health');
        Schema::dropIfExists('seo_page_daily');
        Schema::dropIfExists('seo_search_queries');
    }
};
