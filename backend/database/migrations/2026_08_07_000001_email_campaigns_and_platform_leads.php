<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_campaigns')) {
            Schema::create('email_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('subject');
                $table->text('body_html');
                $table->text('body_text')->nullable();
                $table->string('segment', 40)->default('all_managers');
                $table->string('status', 20)->default('draft');
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('platform_leads')) {
            Schema::create('platform_leads', function (Blueprint $table) {
                $table->id();
                $table->string('source', 40)->default('manual');
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('name')->nullable();
                $table->string('mobile', 20)->nullable();
                $table->string('email')->nullable();
                $table->text('message')->nullable();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->string('stage', 30)->default('new');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamp('follow_up_at')->nullable();
                $table->timestamps();

                $table->index(['stage', 'follow_up_at']);
                $table->index(['source', 'source_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_leads');
        Schema::dropIfExists('email_campaigns');
    }
};
