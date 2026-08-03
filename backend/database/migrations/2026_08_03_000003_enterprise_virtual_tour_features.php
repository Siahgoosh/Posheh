<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_tours', function (Blueprint $table) {
            if (! Schema::hasColumn('virtual_tours', 'visibility')) {
                $table->string('visibility')->default('public')->after('status');
            }
            if (! Schema::hasColumn('virtual_tours', 'access_password')) {
                $table->string('access_password')->nullable()->after('visibility');
            }
            if (! Schema::hasColumn('virtual_tours', 'share_token')) {
                $table->string('share_token', 64)->nullable()->unique()->after('access_password');
            }
            if (! Schema::hasColumn('virtual_tours', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('published_at');
            }
            if (! Schema::hasColumn('virtual_tours', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('expires_at');
            }
            if (! Schema::hasColumn('virtual_tours', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('archived_at');
            }
        });

        if (! Schema::hasTable('virtual_tour_versions')) {
            Schema::create('virtual_tour_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('virtual_tour_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('version_number');
                $table->string('label')->nullable();
                $table->json('snapshot');
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->timestamps();
                $table->unique(['virtual_tour_id', 'version_number']);
            });
        }

        if (! Schema::hasTable('virtual_tour_activity_logs')) {
            Schema::create('virtual_tour_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('virtual_tour_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->string('ip', 45)->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_tour_activity_logs');
        Schema::dropIfExists('virtual_tour_versions');
        Schema::table('virtual_tours', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'access_password', 'share_token', 'expires_at', 'archived_at', 'version']);
        });
    }
};
