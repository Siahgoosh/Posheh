<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'ticket_number')) {
                $table->string('ticket_number', 24)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('tickets', 'category')) {
                $table->string('category', 50)->nullable()->after('priority');
            }
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_replies', 'is_internal')) {
                $table->boolean('is_internal')->default(false)->after('is_staff');
            }
        });

        if (! Schema::hasTable('team_chat_messages')) {
            Schema::create('team_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('message');
                $table->timestamps();

                $table->index(['office_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_chat_messages');

        Schema::table('ticket_replies', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_replies', 'is_internal')) {
                $table->dropColumn('is_internal');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('tickets', 'ticket_number')) {
                $table->dropColumn('ticket_number');
            }
        });
    }
};
