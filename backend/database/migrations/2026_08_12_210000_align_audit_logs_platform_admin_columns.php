<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Production may already have the legacy audit_logs schema
 * (user_id, event, auditable_*) from 2024_01_01_000005.
 * The platform-admin migration only creates the table when missing,
 * so actor_id/action/target_* never get added — and admin wallet/plan
 * actions then 500 on AuditLog::create().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 100);
                $table->string('target_type', 100)->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->text('description')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->index(['target_type', 'target_id']);
                $table->index('created_at');
            });

            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'actor_id')) {
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('audit_logs', 'action')) {
                $table->string('action', 100)->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'target_type')) {
                $table->string('target_type', 100)->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'target_id')) {
                $table->unsignedBigInteger('target_id')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'old_values')) {
                $table->json('old_values')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'new_values')) {
                $table->json('new_values')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }
        });

        // Legacy NOT NULL columns block platform-admin inserts.
        $this->makeLegacyColumnsNullable();

        if (Schema::hasColumn('audit_logs', 'action') && Schema::hasColumn('audit_logs', 'event')) {
            DB::table('audit_logs')
                ->whereNull('action')
                ->whereNotNull('event')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('audit_logs')->where('id', $row->id)->update([
                            'action' => (string) $row->event,
                            'actor_id' => $row->actor_id ?? ($row->user_id ?? null),
                            'target_type' => $row->target_type ?? ($row->auditable_type ?? null),
                            'target_id' => $row->target_id ?? ($row->auditable_id ?? null),
                        ]);
                    }
                });
        }

        // Ensure action is never null for rows that still lack it (keeps admin index filters sane).
        if (Schema::hasColumn('audit_logs', 'action')) {
            DB::table('audit_logs')->whereNull('action')->update(['action' => 'legacy.unknown']);
        }
    }

    public function down(): void
    {
        // Non-destructive: keep aligned columns.
    }

    private function makeLegacyColumnsNullable(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $map = [
            'event' => "MODIFY `event` VARCHAR(255) NULL",
            'auditable_type' => "MODIFY `auditable_type` VARCHAR(255) NULL",
            'auditable_id' => "MODIFY `auditable_id` BIGINT UNSIGNED NULL",
            'user_id' => "MODIFY `user_id` BIGINT UNSIGNED NULL",
        ];

        foreach ($map as $column => $fragment) {
            if (! Schema::hasColumn('audit_logs', $column)) {
                continue;
            }
            try {
                DB::statement("ALTER TABLE `audit_logs` {$fragment}");
            } catch (\Throwable) {
                // Column may already be nullable or type differs slightly.
            }
        }
    }
};
