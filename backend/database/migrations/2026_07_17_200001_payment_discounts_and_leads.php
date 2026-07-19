<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percent');
            $table->unsignedInteger('value');
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('office_id')->constrained()->nullOnDelete();
            $table->string('user_phone', 20)->nullable()->after('user_id');
            $table->foreignId('discount_code_id')->nullable()->after('user_phone')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('original_amount')->nullable()->after('amount');
            $table->unsignedBigInteger('discount_amount')->default(0)->after('original_amount');
            $table->index(['status', 'created_at']);
            $table->index('user_phone');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['discount_code_id']);
            $table->dropColumn(['user_id', 'user_phone', 'discount_code_id', 'original_amount', 'discount_amount']);
        });

        Schema::dropIfExists('discount_codes');
    }
};
