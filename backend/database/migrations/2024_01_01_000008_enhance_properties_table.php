<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('owner_contact_id')->nullable()->after('owner_mobile')->constrained('contacts')->nullOnDelete();
            $table->string('building_type')->nullable()->after('type');
            $table->string('deed_type')->nullable()->after('building_type');
            $table->string('direction')->nullable()->after('deed_type');
            $table->decimal('land_area', 10, 2)->nullable()->after('area');
            $table->unsignedTinyInteger('units_per_floor')->nullable()->after('total_floors');
            $table->string('renovation_status')->nullable()->after('building_age');
            $table->string('heating_type')->nullable()->after('has_storage');
            $table->string('cooling_type')->nullable()->after('heating_type');
            $table->boolean('is_negotiable')->default(true)->after('rent');
            $table->decimal('price_per_meter', 12, 0)->nullable()->after('price');
            $table->decimal('commission_percent', 5, 2)->nullable()->after('is_negotiable');
            $table->string('source')->nullable()->after('commission_percent');
            $table->text('internal_notes')->nullable()->after('description');
            $table->string('title')->nullable()->after('code');
            $table->json('amenities')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_contact_id');
            $table->dropColumn([
                'building_type', 'deed_type', 'direction', 'land_area', 'units_per_floor',
                'renovation_status', 'heating_type', 'cooling_type', 'is_negotiable',
                'price_per_meter', 'commission_percent', 'source', 'internal_notes', 'title', 'amenities',
            ]);
        });
    }
};
