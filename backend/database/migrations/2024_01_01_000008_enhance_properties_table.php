<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('properties', 'owner_contact_id')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->foreignId('owner_contact_id')->nullable()->after('owner_mobile')->constrained('contacts')->nullOnDelete();
            });
        }

        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'title')) {
                $table->string('title')->nullable()->after('code');
            }
            if (! Schema::hasColumn('properties', 'building_type')) {
                $table->string('building_type')->nullable()->after('type');
            }
            if (! Schema::hasColumn('properties', 'deed_type')) {
                $table->string('deed_type')->nullable()->after('building_type');
            }
            if (! Schema::hasColumn('properties', 'direction')) {
                $table->string('direction')->nullable()->after('deed_type');
            }
            if (! Schema::hasColumn('properties', 'price_per_meter')) {
                $table->decimal('price_per_meter', 12, 0)->nullable()->after('price');
            }
            if (! Schema::hasColumn('properties', 'is_negotiable')) {
                $table->boolean('is_negotiable')->default(true)->after('rent');
            }
            if (! Schema::hasColumn('properties', 'commission_percent')) {
                $table->decimal('commission_percent', 5, 2)->nullable()->after('is_negotiable');
            }
            if (! Schema::hasColumn('properties', 'source')) {
                $table->string('source')->nullable()->after('commission_percent');
            }
            if (! Schema::hasColumn('properties', 'land_area')) {
                $table->decimal('land_area', 10, 2)->nullable()->after('area');
            }
            if (! Schema::hasColumn('properties', 'units_per_floor')) {
                $table->unsignedTinyInteger('units_per_floor')->nullable()->after('total_floors');
            }
            if (! Schema::hasColumn('properties', 'renovation_status')) {
                $table->string('renovation_status')->nullable()->after('building_age');
            }
            if (! Schema::hasColumn('properties', 'heating_type')) {
                $table->string('heating_type')->nullable()->after('has_storage');
            }
            if (! Schema::hasColumn('properties', 'cooling_type')) {
                $table->string('cooling_type')->nullable()->after('heating_type');
            }
            if (! Schema::hasColumn('properties', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('description');
            }
            if (! Schema::hasColumn('properties', 'amenities')) {
                $table->json('amenities')->nullable()->after('features');
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'owner_contact_id')) {
                $table->dropConstrainedForeignId('owner_contact_id');
            }

            $columns = array_filter([
                'building_type', 'deed_type', 'direction', 'land_area', 'units_per_floor',
                'renovation_status', 'heating_type', 'cooling_type', 'is_negotiable',
                'price_per_meter', 'commission_percent', 'source', 'internal_notes', 'title', 'amenities',
            ], fn (string $col) => Schema::hasColumn('properties', $col));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
