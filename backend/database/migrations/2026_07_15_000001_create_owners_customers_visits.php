<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('owners')) {
            Schema::create('owners', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('mobile', 20)->nullable();
                $table->string('national_id', 20)->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['office_id', 'mobile']);
            });
        }

        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('mobile', 20)->nullable();
                $table->string('national_id', 20)->nullable();
                $table->string('priority', 20)->default('normal');
                $table->unsignedBigInteger('budget_min')->nullable();
                $table->unsignedBigInteger('budget_max')->nullable();
                $table->string('preferred_type', 30)->nullable();
                $table->string('preferred_city')->nullable();
                $table->string('preferred_district')->nullable();
                $table->unsignedSmallInteger('min_area')->nullable();
                $table->unsignedSmallInteger('max_area')->nullable();
                $table->unsignedTinyInteger('min_rooms')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['office_id', 'priority']);
            });
        }

        if (! Schema::hasTable('property_visits')) {
            Schema::create('property_visits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamp('visit_at');
                $table->unsignedSmallInteger('duration_minutes')->default(30);
                $table->string('status', 20)->default('scheduled');
                $table->text('notes')->nullable();
                $table->boolean('sms_reminder_sent')->default(false);
                $table->timestamps();
                $table->index(['office_id', 'visit_at']);
                $table->index(['property_id', 'visit_at']);
            });
        }

        if (Schema::hasTable('properties')) {
            if (! Schema::hasColumn('properties', 'owner_id')) {
                Schema::table('properties', function (Blueprint $table) {
                    $table->foreignId('owner_id')->nullable()->after('assigned_to')->constrained()->nullOnDelete();
                });
            }
            if (! Schema::hasColumn('properties', 'qr_token')) {
                Schema::table('properties', function (Blueprint $table) {
                    $table->string('qr_token', 32)->nullable()->unique()->after('code');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('properties')) {
            if (Schema::hasColumn('properties', 'owner_id')) {
                Schema::table('properties', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('owner_id');
                });
            }
            if (Schema::hasColumn('properties', 'qr_token')) {
                Schema::table('properties', function (Blueprint $table) {
                    $table->dropColumn('qr_token');
                });
            }
        }
        Schema::dropIfExists('property_visits');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('owners');
    }
};
