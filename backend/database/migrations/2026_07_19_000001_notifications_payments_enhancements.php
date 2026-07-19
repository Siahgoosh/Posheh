<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_message_reads', function (Blueprint $table) {
            $table->timestamp('dismissed_at')->nullable()->after('read_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('invoice_number', 32)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_message_reads', function (Blueprint $table) {
            $table->dropColumn('dismissed_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('invoice_number');
        });
    }
};
