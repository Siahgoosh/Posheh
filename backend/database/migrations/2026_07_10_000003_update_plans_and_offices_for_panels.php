<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('panel_type', 20)->default('office')->after('slug');
            $table->unsignedSmallInteger('trial_days')->default(3)->after('sort_order');
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('slug')->constrained('subscription_plans')->nullOnDelete();
            $table->string('panel_type', 20)->nullable()->after('subscription_plan_id');
            $table->boolean('is_verified')->default(false)->after('is_active');
            $table->boolean('show_on_website')->default(false)->after('is_verified');
            $table->string('telegram_bot_token')->nullable()->after('show_on_website');
            $table->json('whatsapp_config')->nullable()->after('telegram_bot_token');
            $table->text('description')->nullable()->after('whatsapp_config');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_plan_id');
            $table->dropColumn([
                'panel_type',
                'is_verified',
                'show_on_website',
                'telegram_bot_token',
                'whatsapp_config',
                'description',
            ]);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['panel_type', 'trial_days']);
        });
    }
};
