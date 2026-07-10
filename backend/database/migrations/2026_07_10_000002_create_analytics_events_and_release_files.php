<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->string('path', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('visitor_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_type', 'created_at']);
            $table->index(['path', 'created_at']);
            $table->index('visitor_hash');
        });

        Schema::table('app_releases', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('download_url');
        });
    }

    public function down(): void
    {
        Schema::table('app_releases', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });

        Schema::dropIfExists('analytics_events');
    }
};
