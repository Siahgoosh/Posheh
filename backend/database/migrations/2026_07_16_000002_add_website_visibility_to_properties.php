<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'show_on_website')) {
                $table->boolean('show_on_website')->default(false)->after('status');
            }
            if (! Schema::hasColumn('properties', 'website_approved')) {
                $table->boolean('website_approved')->default(false)->after('show_on_website');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            foreach (['show_on_website', 'website_approved'] as $col) {
                if (Schema::hasColumn('properties', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
