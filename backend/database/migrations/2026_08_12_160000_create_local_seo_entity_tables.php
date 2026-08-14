<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_business_profiles')) {
            Schema::create('seo_business_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('business_name');
                $table->string('legal_name')->nullable();
                $table->string('brand')->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('email')->nullable();
                $table->string('support_email')->nullable();
                $table->string('website')->nullable();
                $table->string('address_line')->nullable();
                $table->string('city')->nullable();
                $table->string('region')->nullable();
                $table->string('country', 80)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->json('working_hours')->nullable();
                $table->text('description')->nullable();
                $table->json('services')->nullable();
                $table->json('social_profiles')->nullable();
                $table->string('logo_url')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->boolean('coords_verified')->default(false);
                $table->boolean('nap_complete')->default(false);
                $table->string('status', 20)->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seo_entities')) {
            Schema::create('seo_entities', function (Blueprint $table) {
                $table->id();
                $table->string('type', 40); // BUSINESS, BRAND, PERSON, LOCATION, CITY, NEIGHBORHOOD, SERVICE, PROPERTY, ARTICLE, CATEGORY, TOPIC, PRODUCT
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('status', 20)->default('draft'); // draft|approved|published|archived
                $table->string('meta_title')->nullable();
                $table->string('meta_description', 500)->nullable();
                $table->string('canonical_url', 500)->nullable();
                $table->string('robots_directive', 60)->nullable();
                $table->string('og_title')->nullable();
                $table->string('og_description', 500)->nullable();
                $table->string('og_image', 500)->nullable();
                $table->string('schema_type', 60)->nullable();
                $table->boolean('is_indexable')->default(false);
                $table->json('payload')->nullable(); // type-specific fields
                $table->unsignedBigInteger('external_id')->nullable(); // blog_post_id / property_id etc.
                $table->string('external_type', 40)->nullable();
                $table->timestamps();
                $table->index(['type', 'status']);
                $table->index(['is_indexable', 'status']);
                $table->index(['external_type', 'external_id']);
            });
        }

        if (! Schema::hasTable('seo_entity_relationships')) {
            Schema::create('seo_entity_relationships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_entity_id')->constrained('seo_entities')->cascadeOnDelete();
                $table->foreignId('to_entity_id')->constrained('seo_entities')->cascadeOnDelete();
                $table->string('relation_type', 40); // OWNS, OPERATES, SERVES, LOCATED_IN, PART_OF, RELATED_TO, AUTHORED_BY, ABOUT, OFFERS, HAS_PROPERTY, HAS_ARTICLE, NEAR, WORKS_FOR
                $table->unsignedSmallInteger('weight')->default(50);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->unique(['from_entity_id', 'to_entity_id', 'relation_type'], 'seo_entity_rel_unique');
                $table->index(['relation_type']);
            });
        }

        if (! Schema::hasTable('seo_locations')) {
            Schema::create('seo_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('entity_id')->nullable()->constrained('seo_entities')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type', 30); // country|province|city|district|neighborhood|street
                $table->foreignId('parent_id')->nullable()->constrained('seo_locations')->nullOnDelete();
                $table->text('description')->nullable();
                $table->text('unique_value')->nullable(); // required for publish
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->boolean('coords_verified')->default(false);
                $table->string('status', 20)->default('draft');
                $table->boolean('is_indexable')->default(false);
                $table->unsignedTinyInteger('quality_score')->nullable(); // internal
                $table->json('quality_gate')->nullable();
                $table->string('portfolio', 20)->nullable(); // STAR|GROW|MAINTAIN|FIX|RETIRE
                $table->string('meta_title')->nullable();
                $table->string('meta_description', 500)->nullable();
                $table->string('robots_directive', 60)->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('content_updated_at')->nullable();
                $table->timestamps();
                $table->index(['type', 'status']);
                $table->index(['parent_id', 'status']);
                $table->index(['is_indexable', 'status']);
            });
        }

        if (! Schema::hasTable('seo_topics')) {
            Schema::create('seo_topics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('entity_id')->nullable()->constrained('seo_entities')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->foreignId('parent_id')->nullable()->constrained('seo_topics')->nullOnDelete();
                $table->text('description')->nullable();
                $table->string('search_intent', 40)->nullable();
                $table->unsignedTinyInteger('business_value')->default(50);
                $table->string('coverage', 20)->default('missing'); // covered|partial|missing
                $table->string('pillar_slug')->nullable();
                $table->json('related_topic_ids')->nullable();
                $table->json('related_location_ids')->nullable();
                $table->json('gaps')->nullable();
                $table->unsignedTinyInteger('authority_score')->nullable(); // internal, not Google
                $table->string('status', 20)->default('active');
                $table->timestamps();
                $table->index(['parent_id', 'status']);
                $table->index(['coverage', 'business_value']);
            });
        }

        if (! Schema::hasTable('seo_local_knowledge')) {
            Schema::create('seo_local_knowledge', function (Blueprint $table) {
                $table->id();
                $table->foreignId('location_id')->nullable()->constrained('seo_locations')->nullOnDelete();
                $table->foreignId('topic_id')->nullable()->constrained('seo_topics')->nullOnDelete();
                $table->string('fact_type', 40)->default('note'); // note|market|faq|trend
                $table->text('fact');
                $table->string('source')->nullable();
                $table->date('fact_date')->nullable();
                $table->unsignedTinyInteger('confidence')->default(50);
                $table->string('status', 20)->default('draft'); // draft|approved|expired
                $table->string('author')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->index(['location_id', 'status']);
                $table->index(['status', 'expires_at']);
            });
        }

        if (! Schema::hasTable('seo_local_opportunities')) {
            Schema::create('seo_local_opportunities', function (Blueprint $table) {
                $table->id();
                $table->string('type', 40);
                $table->string('title');
                $table->text('reason')->nullable();
                $table->string('priority', 20)->default('medium');
                $table->unsignedTinyInteger('rank')->default(99);
                $table->json('payload')->nullable();
                $table->string('status', 20)->default('open'); // open|accepted|rejected|done
                $table->timestamp('suggested_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'rank']);
            });
        }

        Schema::table('blog_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('blog_posts', 'primary_entity_id')) {
                $table->unsignedBigInteger('primary_entity_id')->nullable()->after('blog_author_id');
            }
            if (! Schema::hasColumn('blog_posts', 'seo_location_id')) {
                $table->unsignedBigInteger('seo_location_id')->nullable()->after('primary_entity_id');
            }
            if (! Schema::hasColumn('blog_posts', 'seo_topic_id')) {
                $table->unsignedBigInteger('seo_topic_id')->nullable()->after('seo_location_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            foreach (['primary_entity_id', 'seo_location_id', 'seo_topic_id'] as $col) {
                if (Schema::hasColumn('blog_posts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('seo_local_opportunities');
        Schema::dropIfExists('seo_local_knowledge');
        Schema::dropIfExists('seo_topics');
        Schema::dropIfExists('seo_locations');
        Schema::dropIfExists('seo_entity_relationships');
        Schema::dropIfExists('seo_entities');
        Schema::dropIfExists('seo_business_profiles');
    }
};
