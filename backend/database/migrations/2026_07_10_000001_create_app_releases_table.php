<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20); // android, windows, pwa
            $table->string('version', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('download_url');
            $table->string('file_size')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};
