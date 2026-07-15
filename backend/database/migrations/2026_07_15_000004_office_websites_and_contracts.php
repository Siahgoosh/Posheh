<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            if (! Schema::hasColumn('offices', 'subdomain')) {
                $table->string('subdomain', 63)->nullable()->unique()->after('slug');
            }
            if (! Schema::hasColumn('offices', 'website_status')) {
                $table->string('website_status', 20)->default('none')->after('show_on_website');
            }
            if (! Schema::hasColumn('offices', 'website_description')) {
                $table->text('website_description')->nullable()->after('website_status');
            }
            if (! Schema::hasColumn('offices', 'website_published_at')) {
                $table->timestamp('website_published_at')->nullable()->after('website_description');
            }
            if (! Schema::hasColumn('offices', 'plan_active')) {
                $table->boolean('plan_active')->default(true)->after('subscription_plan_id');
            }
        });

        if (! Schema::hasTable('office_site_posts')) {
            Schema::create('office_site_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->string('slug');
                $table->text('excerpt')->nullable();
                $table->longText('body')->nullable();
                $table->boolean('is_published')->default(true);
                $table->unsignedInteger('views')->default(0);
                $table->timestamps();
                $table->unique(['office_id', 'slug']);
            });
        }

        if (! Schema::hasTable('office_visit_requests')) {
            Schema::create('office_visit_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('mobile', 20);
                $table->text('message')->nullable();
                $table->string('status', 20)->default('new');
                $table->timestamps();
                $table->index(['office_id', 'status']);
            });
        }

        if (Schema::hasTable('contracts') && ! Schema::hasColumn('contracts', 'docx_path')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('docx_path')->nullable()->after('pdf_path');
                $table->json('field_values')->nullable()->after('content');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('office_visit_requests');
        Schema::dropIfExists('office_site_posts');

        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) {
                foreach (['docx_path', 'field_values'] as $col) {
                    if (Schema::hasColumn('contracts', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::table('offices', function (Blueprint $table) {
            foreach (['subdomain', 'website_status', 'website_description', 'website_published_at', 'plan_active'] as $col) {
                if (Schema::hasColumn('offices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
