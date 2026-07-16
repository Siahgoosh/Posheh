<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('office_visit_requests')) {
            return;
        }

        Schema::table('office_visit_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('office_visit_requests', 'email')) {
                $table->string('email')->nullable()->after('mobile');
            }
            if (! Schema::hasColumn('office_visit_requests', 'preferred_date')) {
                $table->string('preferred_date', 60)->nullable()->after('email');
            }
            if (! Schema::hasColumn('office_visit_requests', 'preferred_time')) {
                $table->string('preferred_time', 20)->nullable()->after('preferred_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('office_visit_requests')) {
            return;
        }

        Schema::table('office_visit_requests', function (Blueprint $table) {
            foreach (['email', 'preferred_date', 'preferred_time'] as $col) {
                if (Schema::hasColumn('office_visit_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
