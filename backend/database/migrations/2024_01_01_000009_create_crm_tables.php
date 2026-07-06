<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('contacts', 'email')) {
                $table->string('email')->nullable()->after('mobile');
            }
            if (! Schema::hasColumn('contacts', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('contacts', 'source')) {
                $table->string('source')->nullable()->after('type');
            }
            if (! Schema::hasColumn('contacts', 'tags')) {
                $table->json('tags')->nullable()->after('source');
            }
            if (! Schema::hasColumn('contacts', 'budget_min')) {
                $table->unsignedBigInteger('budget_min')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('contacts', 'budget_max')) {
                $table->unsignedBigInteger('budget_max')->nullable()->after('budget_min');
            }
            if (! Schema::hasColumn('contacts', 'preferred_areas')) {
                $table->json('preferred_areas')->nullable()->after('budget_max');
            }
            if (! Schema::hasColumn('contacts', 'property_interest')) {
                $table->string('property_interest')->nullable()->after('preferred_areas');
            }
            if (! Schema::hasColumn('contacts', 'rooms_min')) {
                $table->unsignedSmallInteger('rooms_min')->nullable()->after('property_interest');
            }
            if (! Schema::hasColumn('contacts', 'area_min')) {
                $table->unsignedSmallInteger('area_min')->nullable()->after('rooms_min');
            }
        });

        if (! Schema::hasTable('pipelines')) {
            Schema::create('pipelines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pipeline_stages')) {
            Schema::create('pipeline_stages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('color')->default('#6366f1');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->unsignedTinyInteger('probability')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('deals')) {
            Schema::create('deals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('pipeline_stage_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->unsignedBigInteger('value')->default(0);
                $table->string('status')->default('open');
                $table->unsignedTinyInteger('probability')->nullable();
                $table->timestamp('expected_close_at')->nullable();
                $table->timestamp('won_at')->nullable();
                $table->timestamp('lost_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['office_id', 'status']);
            });
        }

        if (! Schema::hasTable('contact_activities')) {
            Schema::create('contact_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('deal_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type');
                $table->string('subject')->nullable();
                $table->text('body')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['contact_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_activities');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');

        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'assigned_to')) {
                $table->dropConstrainedForeignId('assigned_to');
            }

            $columns = array_filter([
                'email', 'source', 'tags', 'budget_min', 'budget_max',
                'preferred_areas', 'property_interest', 'rooms_min', 'area_min',
            ], fn (string $col) => Schema::hasColumn('contacts', $col));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
