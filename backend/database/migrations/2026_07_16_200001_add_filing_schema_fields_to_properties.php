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
            if (! Schema::hasColumn('properties', 'title')) {
                $table->string('title')->nullable()->after('code');
            }
            if (! Schema::hasColumn('properties', 'contact_phone_2')) {
                $table->string('contact_phone_2', 20)->nullable()->after('owner_mobile');
            }
            if (! Schema::hasColumn('properties', 'tags')) {
                $table->json('tags')->nullable()->after('features');
            }
            if (! Schema::hasColumn('properties', 'filing_data')) {
                $table->json('filing_data')->nullable()->after('tags');
            }
            if (! Schema::hasColumn('properties', 'document_status')) {
                $table->string('document_status', 50)->nullable()->after('filing_data');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            foreach (['title', 'contact_phone_2', 'tags', 'filing_data', 'document_status'] as $col) {
                if (Schema::hasColumn('properties', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
