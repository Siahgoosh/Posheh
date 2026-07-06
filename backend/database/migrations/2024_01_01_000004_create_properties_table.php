<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->index();
            $table->string('type');
            $table->string('permission')->default('office');
            $table->string('status')->default('active');
            $table->string('owner_name')->nullable();
            $table->string('owner_mobile', 15)->nullable();
            $table->unsignedBigInteger('price')->nullable();
            $table->unsignedBigInteger('deposit')->nullable();
            $table->unsignedBigInteger('rent')->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->unsignedTinyInteger('rooms')->nullable();
            $table->unsignedSmallInteger('building_age')->nullable();
            $table->smallInteger('floor')->nullable();
            $table->unsignedTinyInteger('total_floors')->nullable();
            $table->boolean('has_parking')->default(false);
            $table->boolean('has_elevator')->default(false);
            $table->boolean('has_storage')->default(false);
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('neighborhood')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['office_id', 'code']);
            $table->index(['office_id', 'type', 'status']);
            $table->index(['office_id', 'permission']);
            $table->index(['office_id', 'expires_at']);
        });

        Schema::create('property_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();
        });

        Schema::create('property_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'property_id']);
        });

        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('filters');
            $table->boolean('notify_on_match')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('property_favorites');
        Schema::dropIfExists('property_media');
        Schema::dropIfExists('properties');
    }
};
