<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_content_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('tone', 32)->default('friendly');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->json('input')->nullable();
            $table->longText('output');
            $table->json('meta')->nullable();
            $table->string('provider', 32)->default('rules');
            $table->unsignedInteger('tokens_used')->default(0);
            $table->timestamps();

            $table->index(['office_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_content_generations');
    }
};
