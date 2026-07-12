<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('properties') && ! Schema::hasColumn('properties', 'property_category')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->string('property_category', 30)->nullable()->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('properties') && Schema::hasColumn('properties', 'property_category')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->dropColumn('property_category');
            });
        }
    }
};
