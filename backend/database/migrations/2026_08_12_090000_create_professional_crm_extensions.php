<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Professional Real Estate CRM — additive extensions only.
 * Does NOT drop/rename existing columns or destroy data.
 * Does NOT merge Communication (comm_*) platform CRM into office CRM.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_pipeline_stages')) {
            Schema::create('crm_pipeline_stages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('key', 40);
                $table->string('label');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->string('color', 30)->nullable();
                $table->boolean('is_won')->default(false);
                $table->boolean('is_lost')->default(false);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(true);
                $table->timestamps();
                $table->unique(['office_id', 'key']);
                $table->index(['office_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('crm_sources')) {
            Schema::create('crm_sources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('key', 40);
                $table->string('label');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['office_id', 'key']);
            });
        }

        if (! Schema::hasTable('crm_tags')) {
            Schema::create('crm_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('color', 30)->nullable();
                $table->timestamps();
                $table->unique(['office_id', 'name']);
            });
        }

        if (! Schema::hasTable('crm_taggables')) {
            Schema::create('crm_taggables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('crm_tag_id')->constrained('crm_tags')->cascadeOnDelete();
                $table->morphs('taggable');
                $table->timestamps();
                $table->unique(['crm_tag_id', 'taggable_type', 'taggable_id'], 'crm_taggables_unique');
            });
        }

        if (! Schema::hasTable('crm_follow_ups')) {
            Schema::create('crm_follow_ups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('crm_deal_id')->nullable()->constrained('crm_deals')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamp('due_at');
                $table->string('status', 20)->default('pending'); // pending|done|cancelled|overdue
                $table->string('priority', 20)->default('normal');
                $table->string('outcome', 40)->nullable();
                $table->text('notes')->nullable();
                $table->text('next_action')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['office_id', 'due_at', 'status']);
                $table->index(['assigned_to', 'due_at']);
            });
        }

        if (! Schema::hasTable('crm_score_rules')) {
            Schema::create('crm_score_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('key', 60);
                $table->string('label');
                $table->smallInteger('points'); // can be negative
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['office_id', 'key']);
            });
        }

        if (! Schema::hasTable('crm_lost_reasons')) {
            Schema::create('crm_lost_reasons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('key', 40);
                $table->string('label');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['office_id', 'key']);
            });
        }

        // ---- Extend crm_deals (nullable only) ----
        if (Schema::hasTable('crm_deals')) {
            Schema::table('crm_deals', function (Blueprint $table) {
                if (! Schema::hasColumn('crm_deals', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->after('property_id')->constrained('customers')->nullOnDelete();
                }
                if (! Schema::hasColumn('crm_deals', 'lost_reason')) {
                    $table->string('lost_reason', 60)->nullable()->after('notes');
                }
                if (! Schema::hasColumn('crm_deals', 'lost_reason_note')) {
                    $table->string('lost_reason_note')->nullable()->after('lost_reason');
                }
                if (! Schema::hasColumn('crm_deals', 'last_contacted_at')) {
                    $table->timestamp('last_contacted_at')->nullable()->after('follow_up_at');
                }
                if (! Schema::hasColumn('crm_deals', 'next_action')) {
                    $table->string('next_action')->nullable()->after('last_contacted_at');
                }
                if (! Schema::hasColumn('crm_deals', 'probability')) {
                    $table->unsignedTinyInteger('probability')->nullable()->after('lead_score');
                }
                if (! Schema::hasColumn('crm_deals', 'source_id')) {
                    $table->foreignId('source_id')->nullable()->after('source')->constrained('crm_sources')->nullOnDelete();
                }
                if (! Schema::hasColumn('crm_deals', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
                }
            });

            Schema::table('crm_deals', function (Blueprint $table) {
                try {
                    $table->index(['office_id', 'customer_id'], 'crm_deals_office_customer_idx');
                } catch (\Throwable) {
                }
                try {
                    $table->index(['office_id', 'follow_up_at'], 'crm_deals_office_followup_idx');
                } catch (\Throwable) {
                }
                try {
                    $table->index(['office_id', 'lead_score'], 'crm_deals_office_score_idx');
                } catch (\Throwable) {
                }
            });
        }

        // ---- Extend customers ----
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'first_name')) {
                    $table->string('first_name')->nullable()->after('name');
                }
                if (! Schema::hasColumn('customers', 'last_name')) {
                    $table->string('last_name')->nullable()->after('first_name');
                }
                if (! Schema::hasColumn('customers', 'mobile_secondary')) {
                    $table->string('mobile_secondary', 20)->nullable()->after('mobile');
                }
                if (! Schema::hasColumn('customers', 'phone')) {
                    $table->string('phone', 20)->nullable()->after('mobile_secondary');
                }
                if (! Schema::hasColumn('customers', 'city')) {
                    $table->string('city', 100)->nullable()->after('preferred_city');
                }
                if (! Schema::hasColumn('customers', 'roles')) {
                    $table->json('roles')->nullable()->after('priority'); // buyer,seller,tenant,landlord,...
                }
                if (! Schema::hasColumn('customers', 'source')) {
                    $table->string('source', 50)->nullable()->after('roles');
                }
                if (! Schema::hasColumn('customers', 'source_id')) {
                    $table->foreignId('source_id')->nullable()->after('source')->constrained('crm_sources')->nullOnDelete();
                }
                if (! Schema::hasColumn('customers', 'status')) {
                    $table->string('status', 30)->default('active')->after('source_id');
                }
                if (! Schema::hasColumn('customers', 'lead_score')) {
                    $table->unsignedTinyInteger('lead_score')->default(0)->after('status');
                }
                if (! Schema::hasColumn('customers', 'last_contacted_at')) {
                    $table->timestamp('last_contacted_at')->nullable();
                }
                if (! Schema::hasColumn('customers', 'next_follow_up_at')) {
                    $table->timestamp('next_follow_up_at')->nullable();
                }
            });

            try {
                Schema::table('customers', function (Blueprint $table) {
                    $table->index(['office_id', 'mobile'], 'customers_office_mobile_idx');
                });
            } catch (\Throwable) {
            }
        }

        // ---- Link visits / contracts / tasks to deals ----
        if (Schema::hasTable('property_visits') && ! Schema::hasColumn('property_visits', 'crm_deal_id')) {
            Schema::table('property_visits', function (Blueprint $table) {
                $table->foreignId('crm_deal_id')->nullable()->after('customer_id')->constrained('crm_deals')->nullOnDelete();
            });
        }

        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) {
                if (! Schema::hasColumn('contracts', 'crm_deal_id')) {
                    $table->foreignId('crm_deal_id')->nullable()->after('property_id')->constrained('crm_deals')->nullOnDelete();
                }
                if (! Schema::hasColumn('contracts', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (! Schema::hasColumn('tasks', 'crm_deal_id')) {
                    $table->foreignId('crm_deal_id')->nullable()->after('property_id')->constrained('crm_deals')->nullOnDelete();
                }
                if (! Schema::hasColumn('tasks', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->after('crm_deal_id')->constrained('customers')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        // Only drop NEW tables. Do not drop extended columns to protect production data
        // if down is run accidentally; column drops are opt-in via a separate cleanup migration.
        Schema::dropIfExists('crm_follow_ups');
        Schema::dropIfExists('crm_taggables');
        Schema::dropIfExists('crm_tags');
        Schema::dropIfExists('crm_score_rules');
        Schema::dropIfExists('crm_lost_reasons');
        Schema::dropIfExists('crm_sources');
        Schema::dropIfExists('crm_pipeline_stages');
    }
};
