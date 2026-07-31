<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('office_health_scores')) {
            return;
        }

        Schema::create('office_health_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('factors')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique('office_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_health_scores');
    }
};
