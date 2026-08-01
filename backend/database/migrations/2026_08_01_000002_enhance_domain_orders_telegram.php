<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_orders', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->after('price')->constrained('payments')->nullOnDelete();
            $table->boolean('is_available')->nullable()->after('domain_name');
            $table->string('availability_note')->nullable()->after('is_available');
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->string('telegram_admin_chat_id')->nullable()->after('telegram_bot_token');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('telegram_admin_chat_id');
        });

        Schema::table('domain_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_id');
            $table->dropColumn(['is_available', 'availability_note']);
        });
    }
};
