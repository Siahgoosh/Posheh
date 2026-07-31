<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('custom_domain')->nullable()->after('subdomain');
            $table->string('custom_domain_status')->default('none')->after('custom_domain');
            $table->string('domain_dns_token', 64)->nullable()->after('custom_domain_status');
        });

        Schema::create('domain_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('domain_name');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('price')->default(0);
            $table->text('admin_notes')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_orders');

        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn(['custom_domain', 'custom_domain_status', 'domain_dns_token']);
        });
    }
};
