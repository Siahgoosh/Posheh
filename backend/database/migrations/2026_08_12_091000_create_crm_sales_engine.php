<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM Phase 2 — Sales Engine (additive only).
 * Extends Phase 1; does not drop existing data or merge Communication CRM.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_need_profiles')) {
            Schema::create('crm_need_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('crm_deal_id')->nullable()->constrained('crm_deals')->nullOnDelete();
                $table->string('transaction_type', 40)->nullable(); // sale|rent|mortgage|pre_sale|exchange|land|commercial|investment
                $table->string('property_type', 40)->nullable();
                $table->string('purpose', 40)->nullable();
                $table->json('preferred_locations')->nullable();
                $table->json('excluded_locations')->nullable();
                $table->unsignedBigInteger('budget_min')->nullable();
                $table->unsignedBigInteger('budget_max')->nullable();
                $table->unsignedInteger('min_area')->nullable();
                $table->unsignedInteger('max_area')->nullable();
                $table->unsignedTinyInteger('bedrooms')->nullable();
                $table->unsignedSmallInteger('max_building_age')->nullable();
                $table->string('floor_preference', 40)->nullable();
                $table->boolean('parking_required')->nullable();
                $table->boolean('elevator_preferred')->nullable();
                $table->boolean('storage_preferred')->nullable();
                $table->boolean('balcony_preferred')->nullable();
                $table->string('document_type', 40)->nullable();
                $table->string('occupancy', 40)->nullable();
                $table->string('payment_ability', 40)->nullable();
                $table->unsignedBigInteger('down_payment')->nullable();
                $table->unsignedBigInteger('monthly_payment')->nullable();
                $table->unsignedBigInteger('deposit')->nullable();
                $table->unsignedBigInteger('monthly_rent')->nullable();
                $table->string('purchase_timeline', 40)->nullable(); // urgent|this_week|this_month|1_3_months|3_plus|unknown
                $table->string('urgency', 20)->nullable();
                $table->json('preferred_features')->nullable();
                $table->json('excluded_features')->nullable();
                $table->json('priority_weights')->nullable(); // field => must|important|nice|ignore
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['customer_id']);
                $table->index(['office_id', 'transaction_type']);
            });
        }

        if (! Schema::hasTable('crm_matching_weights')) {
            Schema::create('crm_matching_weights', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('key', 40);
                $table->string('label');
                $table->unsignedTinyInteger('weight')->default(10); // percent share
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['office_id', 'key']);
            });
        }

        if (! Schema::hasTable('crm_property_presentations')) {
            Schema::create('crm_property_presentations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignId('crm_deal_id')->nullable()->constrained('crm_deals')->nullOnDelete();
                $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
                $table->string('channel', 30)->default('manual'); // whatsapp|telegram|sms|email|manual
                $table->unsignedTinyInteger('match_score')->nullable();
                $table->timestamp('sent_at')->useCurrent();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['office_id', 'customer_id', 'sent_at']);
                $table->index(['office_id', 'property_id']);
            });
        }

        if (! Schema::hasTable('crm_property_feedback')) {
            Schema::create('crm_property_feedback', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignId('crm_deal_id')->nullable()->constrained('crm_deals')->nullOnDelete();
                $table->foreignId('property_visit_id')->nullable()->constrained('property_visits')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('reaction', 30); // liked|disliked|price_high|area_small|location_bad|needs_renovation|no_parking|no_elevator|other
                $table->unsignedTinyInteger('rating')->nullable(); // 1-5
                $table->string('likelihood', 30)->nullable(); // very_interested|interested|maybe|not_interested
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->index(['office_id', 'customer_id']);
            });
        }

        if (! Schema::hasTable('crm_negotiations')) {
            Schema::create('crm_negotiations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('crm_deal_id')->nullable()->constrained('crm_deals')->nullOnDelete();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
                $table->string('buyer_name')->nullable();
                $table->string('seller_name')->nullable();
                $table->unsignedBigInteger('initial_price')->nullable();
                $table->unsignedBigInteger('current_price')->nullable();
                $table->unsignedBigInteger('target_price')->nullable();
                $table->unsignedBigInteger('minimum_acceptable')->nullable();
                $table->string('status', 30)->default('open'); // open|stalled|agreed|cancelled
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['office_id', 'status']);
            });
        }

        if (! Schema::hasTable('crm_offers')) {
            Schema::create('crm_offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('negotiation_id')->nullable()->constrained('crm_negotiations')->nullOnDelete();
                $table->foreignId('crm_deal_id')->nullable()->constrained('crm_deals')->nullOnDelete();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('side', 20)->default('buyer'); // buyer|seller
                $table->unsignedBigInteger('amount');
                $table->string('payment_terms')->nullable();
                $table->unsignedBigInteger('deposit')->nullable();
                $table->string('installments')->nullable();
                $table->timestamp('deadline_at')->nullable();
                $table->string('status', 30)->default('draft'); // draft|submitted|accepted|rejected|countered|expired|withdrawn
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['office_id', 'status']);
                $table->index(['negotiation_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('crm_automation_rules')) {
            Schema::create('crm_automation_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('trigger', 60); // lead_created|score_changed|property_created|visit_completed|offer_created|no_activity|task_overdue
                $table->json('conditions')->nullable();
                $table->json('actions'); // [{type, params}]
                $table->unsignedInteger('delay_minutes')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(false);
                $table->timestamps();
                $table->index(['office_id', 'trigger', 'is_active']);
            });
        }

        if (! Schema::hasTable('crm_automation_logs')) {
            Schema::create('crm_automation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('automation_rule_id')->nullable()->constrained('crm_automation_rules')->nullOnDelete();
                $table->string('trigger', 60);
                $table->string('subject_type', 120)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('reason')->nullable();
                $table->json('actions_taken')->nullable();
                $table->timestamps();
                $table->index(['office_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('crm_deal_checklist_items')) {
            Schema::create('crm_deal_checklist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('crm_deal_id')->constrained('crm_deals')->cascadeOnDelete();
                $table->string('key', 60);
                $table->string('label');
                $table->boolean('is_done')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamp('done_at')->nullable();
                $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['crm_deal_id', 'key']);
            });
        }

        if (! Schema::hasTable('crm_campaigns')) {
            Schema::create('crm_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('source', 50)->nullable();
                $table->unsignedBigInteger('budget')->nullable();
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['office_id', 'is_active']);
            });
        }

        // Extend visits with feedback / deal link already may exist from phase1
        if (Schema::hasTable('property_visits')) {
            Schema::table('property_visits', function (Blueprint $table) {
                if (! Schema::hasColumn('property_visits', 'customer_reaction')) {
                    $table->string('customer_reaction', 30)->nullable();
                }
                if (! Schema::hasColumn('property_visits', 'property_rating')) {
                    $table->unsignedTinyInteger('property_rating')->nullable();
                }
                if (! Schema::hasColumn('property_visits', 'price_opinion')) {
                    $table->string('price_opinion', 30)->nullable();
                }
                if (! Schema::hasColumn('property_visits', 'likelihood_to_buy')) {
                    $table->string('likelihood_to_buy', 30)->nullable();
                }
                if (! Schema::hasColumn('property_visits', 'next_action')) {
                    $table->string('next_action')->nullable();
                }
            });
        }

        if (Schema::hasTable('crm_deals')) {
            Schema::table('crm_deals', function (Blueprint $table) {
                if (! Schema::hasColumn('crm_deals', 'campaign_id')) {
                    $table->foreignId('campaign_id')->nullable()->constrained('crm_campaigns')->nullOnDelete();
                }
                if (! Schema::hasColumn('crm_deals', 'first_contacted_at')) {
                    $table->timestamp('first_contacted_at')->nullable();
                }
                if (! Schema::hasColumn('crm_deals', 'deal_status')) {
                    $table->string('deal_status', 30)->nullable(); // draft|negotiating|agreement|contract_pending|...
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'lifecycle')) {
                    $table->string('lifecycle', 30)->default('lead'); // lead|prospect|qualified|active_buyer|customer|closed|past|referral_source|dormant
                }
                if (! Schema::hasColumn('customers', 'referred_by_customer_id')) {
                    $table->foreignId('referred_by_customer_id')->nullable()->constrained('customers')->nullOnDelete();
                }
                if (! Schema::hasColumn('customers', 'campaign_id')) {
                    $table->foreignId('campaign_id')->nullable()->constrained('crm_campaigns')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('properties')) {
            Schema::table('properties', function (Blueprint $table) {
                if (! Schema::hasColumn('properties', 'listed_at')) {
                    $table->timestamp('listed_at')->nullable();
                }
                if (! Schema::hasColumn('properties', 'minimum_acceptable_price')) {
                    $table->unsignedBigInteger('minimum_acceptable_price')->nullable();
                }
                if (! Schema::hasColumn('properties', 'seller_motivation')) {
                    $table->string('seller_motivation', 40)->nullable();
                }
            });
        }

        if (Schema::hasTable('saved_searches')) {
            Schema::table('saved_searches', function (Blueprint $table) {
                if (! Schema::hasColumn('saved_searches', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                }
                if (! Schema::hasColumn('saved_searches', 'match_threshold')) {
                    $table->unsignedTinyInteger('match_threshold')->default(60);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_deal_checklist_items');
        Schema::dropIfExists('crm_automation_logs');
        Schema::dropIfExists('crm_automation_rules');
        Schema::dropIfExists('crm_offers');
        Schema::dropIfExists('crm_negotiations');
        Schema::dropIfExists('crm_property_feedback');
        Schema::dropIfExists('crm_property_presentations');
        Schema::dropIfExists('crm_matching_weights');
        Schema::dropIfExists('crm_need_profiles');
        Schema::dropIfExists('crm_campaigns');
    }
};
