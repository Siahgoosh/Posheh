<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 100);
                $table->string('target_type', 100)->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->text('description')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->index(['target_type', 'target_id']);
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('impersonation_sessions')) {
            Schema::create('impersonation_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('type', 20)->default('percent');
                $table->unsignedInteger('value');
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->json('plan_slugs')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('impersonation_sessions');
        Schema::dropIfExists('audit_logs');
    }
};
