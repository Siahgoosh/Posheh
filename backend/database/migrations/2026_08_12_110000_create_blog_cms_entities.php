<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blog_categories')) {
            Schema::create('blog_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('seo_title')->nullable();
                $table->string('meta_description', 500)->nullable();
                $table->string('featured_image')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('blog_categories')->nullOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_indexable')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('blog_tags')) {
            Schema::create('blog_tags', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('seo_title')->nullable();
                $table->string('meta_description', 500)->nullable();
                $table->boolean('is_indexable')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('blog_authors')) {
            Schema::create('blog_authors', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->text('bio')->nullable();
                $table->string('avatar')->nullable();
                $table->string('role')->nullable();
                $table->json('social_links')->nullable();
                $table->boolean('is_indexable')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('blog_post_tag')) {
            Schema::create('blog_post_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
                $table->unique(['blog_post_id', 'blog_tag_id']);
            });
        }

        if (! Schema::hasTable('blog_related_posts')) {
            Schema::create('blog_related_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->foreignId('related_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->string('anchor')->nullable();
                $table->string('source', 20)->default('manual'); // auto|manual
                $table->boolean('approved')->default(true);
                $table->unsignedTinyInteger('score')->nullable();
                $table->timestamps();
                $table->unique(['blog_post_id', 'related_post_id']);
            });
        }

        if (! Schema::hasTable('blog_audit_logs')) {
            Schema::create('blog_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->nullable()->constrained('blog_posts')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');
                $table->json('old_value')->nullable();
                $table->json('new_value')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamps();
                $table->index(['action', 'created_at']);
            });
        }

        if (! Schema::hasTable('blog_broken_links')) {
            Schema::create('blog_broken_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->string('url', 1000);
                $table->string('anchor', 500)->nullable();
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamps();
                $table->index(['is_resolved', 'status_code']);
            });
        }

        if (! Schema::hasTable('blog_gsc_metrics')) {
            Schema::create('blog_gsc_metrics', function (Blueprint $table) {
                $table->id();
                $table->string('page_url')->index();
                $table->string('query')->nullable();
                $table->unsignedInteger('clicks')->default(0);
                $table->unsignedInteger('impressions')->default(0);
                $table->decimal('ctr', 8, 4)->nullable();
                $table->decimal('position', 8, 2)->nullable();
                $table->date('date')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('blog_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('blog_posts', 'blog_category_id')) {
                $table->foreignId('blog_category_id')->nullable()->after('id')->constrained('blog_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('blog_posts', 'blog_author_id')) {
                $table->foreignId('blog_author_id')->nullable()->after('blog_category_id')->constrained('blog_authors')->nullOnDelete();
            }
            if (! Schema::hasColumn('blog_posts', 'og_title')) {
                $table->string('og_title')->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('blog_posts', 'og_description')) {
                $table->string('og_description', 500)->nullable()->after('og_title');
            }
            if (! Schema::hasColumn('blog_posts', 'og_image')) {
                $table->string('og_image', 500)->nullable()->after('og_description');
            }
            if (! Schema::hasColumn('blog_posts', 'content_updated_at')) {
                $table->timestamp('content_updated_at')->nullable()->after('updated_at');
            }
            if (! Schema::hasColumn('blog_posts', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_published');
            }
            if (! Schema::hasColumn('blog_posts', 'is_editors_pick')) {
                $table->boolean('is_editors_pick')->default(false)->after('is_featured');
            }
            if (! Schema::hasColumn('blog_posts', 'view_score')) {
                $table->unsignedInteger('view_score')->default(0)->after('views');
            }
            if (! Schema::hasColumn('blog_posts', 'preview_token')) {
                $table->string('preview_token', 64)->nullable()->unique()->after('slug');
            }
        });

        // Expand review_status vocabulary is string-based already (draft/in_review/approved/scheduled/published/unpublished/archived)
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            foreach (['blog_category_id', 'blog_author_id', 'og_title', 'og_description', 'og_image', 'content_updated_at', 'is_featured', 'is_editors_pick', 'view_score', 'preview_token'] as $col) {
                if (Schema::hasColumn('blog_posts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('blog_gsc_metrics');
        Schema::dropIfExists('blog_broken_links');
        Schema::dropIfExists('blog_audit_logs');
        Schema::dropIfExists('blog_related_posts');
        Schema::dropIfExists('blog_post_tag');
        Schema::dropIfExists('blog_authors');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');
    }
};
