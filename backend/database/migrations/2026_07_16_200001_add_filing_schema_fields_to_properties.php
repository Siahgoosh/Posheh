<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('title')->nullable()->after('code');
            $table->string('contact_phone_2', 20)->nullable()->after('owner_mobile');
            $table->json('tags')->nullable()->after('features');
            $table->json('filing_data')->nullable()->after('tags');
            $table->string('document_status', 50)->nullable()->after('filing_data');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['title', 'contact_phone_2', 'tags', 'filing_data', 'document_status']);
        });
    }
};
