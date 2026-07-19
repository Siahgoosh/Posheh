<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            if (! Schema::hasColumn('owners', 'portal_token')) {
                $table->string('portal_token', 64)->nullable()->unique()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            if (Schema::hasColumn('owners', 'portal_token')) {
                $table->dropColumn('portal_token');
            }
        });
    }
};
