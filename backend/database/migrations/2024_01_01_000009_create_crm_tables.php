<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('email')->nullable()->after('mobile');
            $table->foreignId('assigned_to')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->string('source')->nullable()->after('type');
            $table->json('tags')->nullable()->after('source');
            $table->unsignedBigInteger('budget_min')->nullable()->after('notes');
            $table->unsignedBigInteger('budget_max')->nullable()->after('budget_min');
            $table->json('preferred_areas')->nullable()->after('budget_max');
            $table->string('property_interest')->nullable()->after('preferred_areas');
            $table->unsignedSmallInteger('rooms_min')->nullable()->after('property_interest');
            $table->unsignedSmallInteger('area_min')->nullable()->after('rooms_min');
        });

        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#6366f1');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->timestamps();
        });

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

    public function down(): void
    {
        Schema::dropIfExists('contact_activities');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn([
                'email', 'source', 'tags', 'budget_min', 'budget_max',
                'preferred_areas', 'property_interest', 'rooms_min', 'area_min',
            ]);
        });
    }
};
