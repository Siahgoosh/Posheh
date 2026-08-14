<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cro_ctas')) {
            Schema::create('cro_ctas', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('title');
                $table->string('description', 500)->nullable();
                $table->string('button_text');
                $table->string('url');
                $table->string('image')->nullable();
                $table->string('type')->default('soft'); // soft|product|listing|contact|register
                $table->string('category')->nullable()->index();
                $table->string('topic')->nullable()->index();
                $table->string('funnel_stage')->nullable()->index(); // TOFU|MOFU|BOFU
                $table->string('intent')->nullable()->index();
                $table->unsignedSmallInteger('priority')->default(100);
                $table->boolean('is_active')->default(true)->index();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cro_cta_rules')) {
            Schema::create('cro_cta_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('cro_cta_id')->constrained('cro_ctas')->cascadeOnDelete();
                $table->string('match_field'); // category|topic|intent|funnel_stage|slug|path
                $table->string('match_operator')->default('eq'); // eq|in|contains
                $table->string('match_value');
                $table->unsignedSmallInteger('priority')->default(100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cro_leads')) {
            Schema::create('cro_leads', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->string('name')->nullable();
                $table->string('mobile', 20)->index();
                $table->string('email')->nullable();
                $table->string('request_type')->nullable()->index(); // BUY|SELL|RENT|MORTGAGE|INVESTMENT|PRE_SALE|COMMERCIAL|LAND|DEMO|SUPPORT|OTHER
                $table->string('property_type')->nullable();
                $table->string('city')->nullable()->index();
                $table->string('location')->nullable();
                $table->string('budget')->nullable();
                $table->text('message')->nullable();
                $table->string('source')->default('BLOG')->index(); // ORGANIC|BLOG|CATEGORY|LANDING_PAGE|ARTICLE|DIRECT|REFERRAL|CONTACT
                $table->string('status')->default('NEW')->index(); // NEW|CONTACTED|QUALIFIED|NEGOTIATION|WON|LOST
                $table->unsignedSmallInteger('lead_score')->default(0)->index();
                $table->boolean('is_duplicate')->default(false);
                $table->foreignId('duplicate_of')->nullable()->constrained('cro_leads')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('blog_post_id')->nullable()->constrained('blog_posts')->nullOnDelete();
                $table->string('article_slug')->nullable()->index();
                $table->string('article_url')->nullable();
                $table->string('category_slug')->nullable();
                $table->string('landing_page')->nullable();
                $table->string('first_touch_path')->nullable();
                $table->string('last_touch_path')->nullable();
                $table->string('conversion_page')->nullable();
                $table->string('utm_source')->nullable();
                $table->string('utm_medium')->nullable();
                $table->string('utm_campaign')->nullable();
                $table->string('utm_content')->nullable();
                $table->string('gclid')->nullable();
                $table->string('keyword')->nullable();
                $table->string('campaign')->nullable();
                $table->boolean('consent')->default(false);
                $table->string('ip_hash', 64)->nullable();
                $table->string('visitor_hash', 64)->nullable()->index();
                $table->string('quality_feedback')->nullable(); // good|bad|wrong_intent|duplicate|converted
                $table->timestamp('contacted_at')->nullable();
                $table->timestamp('qualified_at')->nullable();
                $table->timestamp('won_at')->nullable();
                $table->unsignedInteger('response_seconds')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['mobile', 'created_at']);
            });
        }

        if (! Schema::hasTable('cro_conversion_events')) {
            Schema::create('cro_conversion_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_type')->index(); // cta_view|cta_click|form_start|form_submit|phone_click|whatsapp_click|telegram_click|lead_created|qualified_lead|customer
                $table->string('path')->nullable()->index();
                $table->string('article_slug')->nullable()->index();
                $table->foreignId('cro_cta_id')->nullable()->constrained('cro_ctas')->nullOnDelete();
                $table->foreignId('cro_lead_id')->nullable()->constrained('cro_leads')->nullOnDelete();
                $table->string('visitor_hash', 64)->nullable()->index();
                $table->string('session_id', 64)->nullable()->index();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if (! Schema::hasTable('cro_experiments')) {
            Schema::create('cro_experiments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('hypothesis')->nullable();
                $table->string('target_type')->default('cta'); // cta|headline|form|button
                $table->string('variant_a');
                $table->string('variant_b')->nullable();
                $table->string('active_variant')->default('A');
                $table->string('metric')->default('cta_click');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status')->default('draft'); // draft|running|completed|cancelled
                $table->unsignedInteger('sample_size_a')->default(0);
                $table->unsignedInteger('sample_size_b')->default(0);
                $table->decimal('confidence', 5, 2)->nullable();
                $table->text('result')->nullable();
                $table->text('decision')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cro_page_goals')) {
            Schema::create('cro_page_goals', function (Blueprint $table) {
                $table->id();
                $table->string('path')->unique();
                $table->string('primary_goal')->nullable();
                $table->string('secondary_goal')->nullable();
                $table->string('primary_cta_key')->nullable();
                $table->string('secondary_cta_key')->nullable();
                $table->string('conversion_type')->nullable();
                $table->string('funnel_stage')->nullable();
                $table->unsignedTinyInteger('business_value')->default(50);
                $table->timestamps();
            });
        }

        Schema::table('blog_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('blog_posts', 'funnel_stage')) {
                $table->string('funnel_stage', 16)->nullable()->after('business_intent');
            }
            if (! Schema::hasColumn('blog_posts', 'cro_cta_key')) {
                $table->string('cro_cta_key')->nullable()->after('cta_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            if (Schema::hasColumn('blog_posts', 'cro_cta_key')) {
                $table->dropColumn('cro_cta_key');
            }
            if (Schema::hasColumn('blog_posts', 'funnel_stage')) {
                $table->dropColumn('funnel_stage');
            }
        });
        Schema::dropIfExists('cro_page_goals');
        Schema::dropIfExists('cro_experiments');
        Schema::dropIfExists('cro_conversion_events');
        Schema::dropIfExists('cro_leads');
        Schema::dropIfExists('cro_cta_rules');
        Schema::dropIfExists('cro_ctas');
    }
};
