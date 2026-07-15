<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_deals')) {
            Schema::table('crm_deals', function (Blueprint $table) {
                if (! Schema::hasColumn('crm_deals', 'lead_score')) {
                    $table->unsignedTinyInteger('lead_score')->default(50)->after('stage');
                }
                if (! Schema::hasColumn('crm_deals', 'priority')) {
                    $table->string('priority', 20)->default('medium')->after('lead_score');
                }
                if (! Schema::hasColumn('crm_deals', 'source')) {
                    $table->string('source', 50)->nullable()->after('priority');
                }
                if (! Schema::hasColumn('crm_deals', 'follow_up_at')) {
                    $table->timestamp('follow_up_at')->nullable()->after('expected_close_at');
                }
            });
        }

        if (! Schema::hasTable('crm_activities')) {
            Schema::create('crm_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('crm_deal_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 30);
                $table->text('body')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['crm_deal_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('property_shares')) {
            Schema::create('property_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('channel', 20);
                $table->string('recipient_mobile', 20)->nullable();
                $table->timestamps();
                $table->index(['property_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('property_shares');
        Schema::dropIfExists('crm_activities');

        if (Schema::hasTable('crm_deals')) {
            Schema::table('crm_deals', function (Blueprint $table) {
                foreach (['follow_up_at', 'source', 'priority', 'lead_score'] as $col) {
                    if (Schema::hasColumn('crm_deals', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
