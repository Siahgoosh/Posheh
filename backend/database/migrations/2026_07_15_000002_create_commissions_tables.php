<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('commission_settings')) {
            Schema::create('commission_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('sale_rate_percent')->default(30);
                $table->unsignedTinyInteger('rent_rate_percent')->default(50);
                $table->timestamps();
                $table->unique('office_id');
            });
        }

        if (! Schema::hasTable('commissions')) {
            Schema::create('commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('crm_deal_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->unsignedBigInteger('base_amount');
                $table->unsignedTinyInteger('rate_percent');
                $table->unsignedBigInteger('commission_amount');
                $table->string('status', 20)->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->index(['office_id', 'status']);
                $table->index(['user_id', 'status']);
            });
        }

        if (Schema::hasTable('crm_deals') && ! Schema::hasColumn('crm_deals', 'offer_amount')) {
            Schema::table('crm_deals', function (Blueprint $table) {
                $table->unsignedBigInteger('offer_amount')->nullable()->after('value');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_deals') && Schema::hasColumn('crm_deals', 'offer_amount')) {
            Schema::table('crm_deals', function (Blueprint $table) {
                $table->dropColumn('offer_amount');
            });
        }
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('commission_settings');
    }
};
