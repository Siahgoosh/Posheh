<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // income, expense
            $table->string('category', 100)->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->timestamps();
            $table->index(['office_id', 'transaction_date']);
        });

        Schema::create('crm_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('contact_name')->nullable();
            $table->string('contact_mobile', 20)->nullable();
            $table->string('stage', 30)->default('lead'); // lead, contact, visit, negotiation, closed_won, closed_lost
            $table->unsignedBigInteger('value')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('expected_close_at')->nullable();
            $table->timestamps();
            $table->index(['office_id', 'stage']);
        });

        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('type', 50); // sale, rent, mortgage
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('contract_templates')->nullOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->string('party_a_name')->nullable();
            $table->string('party_b_name')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        Schema::create('office_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key_hash', 64)->unique();
            $table->string('key_prefix', 12);
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_staff')->default(false);
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('office_api_keys');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contract_templates');
        Schema::dropIfExists('crm_deals');
        Schema::dropIfExists('accounting_transactions');
    }
};
