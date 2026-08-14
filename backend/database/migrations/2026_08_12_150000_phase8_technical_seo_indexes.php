<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $indexes = [
                'blog_posts_review_status_index' => ['review_status'],
                'blog_posts_category_slug_index' => ['category_slug'],
                'blog_posts_robots_directive_index' => ['robots_directive'],
                'blog_posts_scheduled_at_index' => ['scheduled_at'],
                'blog_posts_content_updated_at_index' => ['content_updated_at'],
                'blog_posts_published_indexability_index' => ['is_published', 'robots_directive'],
            ];
            foreach ($indexes as $name => $cols) {
                try {
                    $table->index($cols, $name);
                } catch (\Throwable) {
                    // Index may already exist
                }
            }
        });

        if (! Schema::hasTable('seo_technical_audits')) {
            Schema::create('seo_technical_audits', function (Blueprint $table) {
                $table->id();
                $table->string('scope', 40)->default('weekly'); // daily|weekly|manual|sitemap
                $table->string('status', 20)->default('ok'); // ok|warn|critical
                $table->json('summary')->nullable();
                $table->json('issues')->nullable();
                $table->json('metrics')->nullable();
                $table->timestamp('ran_at')->nullable();
                $table->timestamps();
                $table->index(['scope', 'ran_at']);
                $table->index(['status', 'ran_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_technical_audits');
        Schema::table('blog_posts', function (Blueprint $table) {
            foreach ([
                'blog_posts_review_status_index',
                'blog_posts_category_slug_index',
                'blog_posts_robots_directive_index',
                'blog_posts_scheduled_at_index',
                'blog_posts_content_updated_at_index',
                'blog_posts_published_indexability_index',
            ] as $name) {
                try {
                    $table->dropIndex($name);
                } catch (\Throwable) {
                }
            }
        });
    }
};
