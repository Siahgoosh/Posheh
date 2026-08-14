<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('blog_posts', 'focus_keyword')) {
                $table->string('focus_keyword')->nullable()->after('keywords');
            }
            if (! Schema::hasColumn('blog_posts', 'secondary_keywords')) {
                $table->json('secondary_keywords')->nullable()->after('focus_keyword');
            }
            if (! Schema::hasColumn('blog_posts', 'search_intent')) {
                $table->string('search_intent', 40)->nullable()->after('secondary_keywords');
            }
            if (! Schema::hasColumn('blog_posts', 'business_intent')) {
                $table->string('business_intent', 40)->nullable()->after('search_intent');
            }
            if (! Schema::hasColumn('blog_posts', 'review_status')) {
                $table->string('review_status', 40)->default('draft')->after('is_published');
            }
            if (! Schema::hasColumn('blog_posts', 'rebuild_locked')) {
                $table->boolean('rebuild_locked')->default(false)->after('review_status');
            }
            if (! Schema::hasColumn('blog_posts', 'quality_scores')) {
                $table->json('quality_scores')->nullable()->after('rebuild_locked');
            }
            if (! Schema::hasColumn('blog_posts', 'content_brief')) {
                $table->json('content_brief')->nullable()->after('quality_scores');
            }
            if (! Schema::hasColumn('blog_posts', 'image_prompt')) {
                $table->text('image_prompt')->nullable()->after('content_brief');
            }
            if (! Schema::hasColumn('blog_posts', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('published_at');
            }
            if (! Schema::hasColumn('blog_posts', 'canonical_url')) {
                $table->string('canonical_url', 500)->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('blog_posts', 'robots_directive')) {
                $table->string('robots_directive', 60)->nullable()->after('canonical_url');
            }
        });

        if (! Schema::hasTable('blog_post_versions')) {
            Schema::create('blog_post_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->unsignedInteger('version')->default(1);
                $table->string('title');
                $table->longText('content');
                $table->string('excerpt', 500)->nullable();
                $table->string('meta_title')->nullable();
                $table->string('meta_description', 500)->nullable();
                $table->json('snapshot')->nullable();
                $table->string('created_by')->nullable();
                $table->string('note')->nullable();
                $table->timestamps();

                $table->unique(['blog_post_id', 'version']);
            });
        }

        if (! Schema::hasTable('blog_redirects')) {
            Schema::create('blog_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('from_path')->unique();
                $table->string('to_path');
                $table->unsignedSmallInteger('status_code')->default(301);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('hits')->default(0);
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_redirects');
        Schema::dropIfExists('blog_post_versions');

        Schema::table('blog_posts', function (Blueprint $table) {
            foreach ([
                'focus_keyword', 'secondary_keywords', 'search_intent', 'business_intent',
                'review_status', 'rebuild_locked', 'quality_scores', 'content_brief',
                'image_prompt', 'scheduled_at', 'canonical_url', 'robots_directive',
            ] as $col) {
                if (Schema::hasColumn('blog_posts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
