<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM Phase 3 — Intelligence, AI architecture, Communication, Custom fields (additive).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_pipeline_probabilities')) {
            Schema::create('crm_pipeline_probabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('stage_key', 40);
                $table->unsignedTinyInteger('probability')->default(10); // 0-100
                $table->timestamps();
                $table->unique(['office_id', 'stage_key']);
            });
        }

        if (! Schema::hasTable('crm_agent_score_weights')) {
            Schema::create('crm_agent_score_weights', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('key', 40);
                $table->string('label');
                $table->unsignedTinyInteger('weight')->default(10);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['office_id', 'key']);
            });
        }

        if (! Schema::hasTable('crm_property_health_rules')) {
            Schema::create('crm_property_health_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('status', 30); // high_demand|healthy|low_interest|stale|critical
                $table->string('label');
                $table->unsignedInteger('min_demand_score')->default(0);
                $table->unsignedInteger('max_days_on_market')->nullable();
                $table->unsignedInteger('min_offers')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['office_id', 'status']);
            });
        }

        if (! Schema::hasTable('crm_custom_fields')) {
            Schema::create('crm_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('entity', 40); // lead|customer|property|deal
                $table->string('key', 60);
                $table->string('label');
                $table->string('type', 30); // text|number|select|boolean|date|textarea
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['office_id', 'entity', 'key']);
            });
        }

        if (! Schema::hasTable('crm_custom_field_values')) {
            Schema::create('crm_custom_field_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('custom_field_id')->constrained('crm_custom_fields')->cascadeOnDelete();
                $table->string('entity_type', 40);
                $table->unsignedBigInteger('entity_id');
                $table->text('value')->nullable();
                $table->timestamps();
                $table->unique(['custom_field_id', 'entity_type', 'entity_id'], 'crm_cf_value_unique');
                $table->index(['office_id', 'entity_type', 'entity_id']);
            });
        }

        if (! Schema::hasTable('crm_message_templates')) {
            Schema::create('crm_message_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('channel', 30)->default('whatsapp'); // whatsapp|telegram|sms|email|manual
                $table->string('purpose', 40)->nullable(); // intro|followup|viewing|negotiation|after_visit
                $table->text('body');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['office_id', 'channel', 'is_active']);
            });
        }

        if (! Schema::hasTable('crm_notification_preferences')) {
            Schema::create('crm_notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('category', 40); // sales|crm|tasks|followups|viewings|offers|deals|finance|system
                $table->boolean('in_app')->default(true);
                $table->boolean('sms')->default(false);
                $table->boolean('telegram')->default(false);
                $table->boolean('email')->default(false);
                $table->boolean('push')->default(false);
                $table->string('digest', 20)->default('instant'); // instant|daily|weekly
                $table->timestamps();
                $table->unique(['user_id', 'category']);
            });
        }

        if (! Schema::hasTable('crm_notifications')) {
            Schema::create('crm_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('category', 40)->default('crm');
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('link')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'read_at', 'created_at']);
                $table->index(['office_id', 'category']);
            });
        }

        if (! Schema::hasTable('crm_integrations')) {
            Schema::create('crm_integrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('provider', 40); // sms|whatsapp|telegram|email
                $table->string('name');
                $table->boolean('is_enabled')->default(false);
                $table->string('status', 30)->default('disconnected'); // connected|disconnected|error
                $table->json('credentials')->nullable(); // encrypted at app layer when set
                $table->json('settings')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();
                $table->unique(['office_id', 'provider', 'name']);
            });
        }

        if (! Schema::hasTable('crm_integration_logs')) {
            Schema::create('crm_integration_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('integration_id')->nullable()->constrained('crm_integrations')->nullOnDelete();
                $table->string('provider', 40);
                $table->string('direction', 20)->default('outbound'); // outbound|inbound
                $table->string('status', 30); // ok|failed|queued
                $table->string('subject_type', 120)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('payload_meta')->nullable(); // no secrets / no PII dumps
                $table->text('error')->nullable();
                $table->timestamps();
                $table->index(['office_id', 'provider', 'created_at']);
            });
        }

        if (! Schema::hasTable('ai_providers')) {
            Schema::create('ai_providers', function (Blueprint $table) {
                $table->id();
                $table->string('key', 40)->unique();
                $table->string('name');
                $table->boolean('is_active')->default(false);
                $table->json('config')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_prompt_templates')) {
            Schema::create('ai_prompt_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete(); // null = system
                $table->string('key', 60);
                $table->string('name');
                $table->unsignedInteger('version')->default(1);
                $table->text('template');
                $table->json('variables')->nullable();
                $table->string('provider', 40)->nullable();
                $table->string('model', 80)->nullable();
                $table->decimal('temperature', 3, 2)->default(0.30);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['office_id', 'key', 'version']);
            });
        }

        if (! Schema::hasTable('ai_usage_logs')) {
            Schema::create('ai_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('feature', 60);
                $table->string('provider', 40)->nullable();
                $table->string('model', 80)->nullable();
                $table->unsignedInteger('prompt_tokens')->default(0);
                $table->unsignedInteger('completion_tokens')->default(0);
                $table->unsignedInteger('total_tokens')->default(0);
                $table->unsignedInteger('cost_toman')->default(0);
                $table->string('status', 30)->default('ok'); // ok|denied|error|mock
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['office_id', 'feature', 'created_at']);
            });
        }

        if (! Schema::hasTable('crm_saved_views')) {
            Schema::create('crm_saved_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('entity', 40); // leads|customers|properties|deals
                $table->json('filters');
                $table->boolean('is_shared')->default(false);
                $table->timestamps();
                $table->index(['office_id', 'user_id', 'entity']);
            });
        }

        if (! Schema::hasTable('crm_onboarding_checklist')) {
            Schema::create('crm_onboarding_checklist', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('key', 60);
                $table->string('label');
                $table->boolean('is_done')->default(false);
                $table->timestamp('done_at')->nullable();
                $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['office_id', 'key']);
            });
        }

        // Property intelligence columns
        if (Schema::hasTable('properties')) {
            Schema::table('properties', function (Blueprint $table) {
                if (! Schema::hasColumn('properties', 'demand_score')) {
                    $table->unsignedTinyInteger('demand_score')->nullable()->after('seller_motivation');
                }
                if (! Schema::hasColumn('properties', 'health_status')) {
                    $table->string('health_status', 30)->nullable()->after('demand_score');
                }
                if (! Schema::hasColumn('properties', 'previous_price')) {
                    $table->unsignedBigInteger('previous_price')->nullable()->after('price');
                }
                if (! Schema::hasColumn('properties', 'price_reduced_at')) {
                    $table->timestamp('price_reduced_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('crm_deals') && ! Schema::hasColumn('crm_deals', 'probability')) {
            // already may exist from earlier schema — guarded
        }

        if (Schema::hasTable('offices')) {
            Schema::table('offices', function (Blueprint $table) {
                if (! Schema::hasColumn('offices', 'crm_onboarded_at')) {
                    $table->timestamp('crm_onboarded_at')->nullable();
                }
                if (! Schema::hasColumn('offices', 'brand_color')) {
                    $table->string('brand_color', 20)->nullable();
                }
                if (! Schema::hasColumn('offices', 'timezone')) {
                    $table->string('timezone', 60)->nullable()->default('Asia/Tehran');
                }
                if (! Schema::hasColumn('offices', 'currency')) {
                    $table->string('currency', 10)->nullable()->default('IRT');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_onboarding_checklist');
        Schema::dropIfExists('crm_saved_views');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_prompt_templates');
        Schema::dropIfExists('ai_providers');
        Schema::dropIfExists('crm_integration_logs');
        Schema::dropIfExists('crm_integrations');
        Schema::dropIfExists('crm_notifications');
        Schema::dropIfExists('crm_notification_preferences');
        Schema::dropIfExists('crm_message_templates');
        Schema::dropIfExists('crm_custom_field_values');
        Schema::dropIfExists('crm_custom_fields');
        Schema::dropIfExists('crm_property_health_rules');
        Schema::dropIfExists('crm_agent_score_weights');
        Schema::dropIfExists('crm_pipeline_probabilities');
    }
};
