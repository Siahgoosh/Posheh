<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AuditLogService
{
    /** @var array<string, bool>|null */
    private static ?array $columns = null;

    public function log(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $actorId = null,
    ): ?AuditLog {
        try {
            $request = request();
            $actor = $actorId ?? auth()->id();
            $payload = $this->buildPayload(
                action: $action,
                targetType: $targetType,
                targetId: $targetId,
                description: $description,
                oldValues: $oldValues,
                newValues: $newValues,
                actorId: is_numeric($actor) ? (int) $actor : null,
                request: $request instanceof Request ? $request : null,
            );

            if ($payload === []) {
                Log::warning('audit_log.skipped_empty_schema', ['action' => $action]);

                return null;
            }

            return AuditLog::create($payload);
        } catch (Throwable $e) {
            // Never fail a business action because audit logging broke.
            Log::warning('audit_log.write_failed', [
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(
        string $action,
        ?string $targetType,
        ?int $targetId,
        ?string $description,
        ?array $oldValues,
        ?array $newValues,
        ?int $actorId,
        ?Request $request,
    ): array {
        $payload = [];

        if ($this->hasColumn('actor_id')) {
            $payload['actor_id'] = $actorId;
        }
        if ($this->hasColumn('action')) {
            $payload['action'] = $action;
        }
        if ($this->hasColumn('target_type')) {
            $payload['target_type'] = $targetType;
        }
        if ($this->hasColumn('target_id')) {
            $payload['target_id'] = $targetId;
        }
        if ($this->hasColumn('description')) {
            $payload['description'] = $description;
        }
        if ($this->hasColumn('old_values')) {
            $payload['old_values'] = $oldValues;
        }
        if ($this->hasColumn('new_values')) {
            $payload['new_values'] = $newValues;
        }
        if ($this->hasColumn('ip_address')) {
            $payload['ip_address'] = $request?->ip();
        }
        if ($this->hasColumn('user_agent')) {
            $payload['user_agent'] = $request ? (string) $request->userAgent() : null;
        }

        // Dual-write legacy columns when the old activities-era schema is still present.
        if ($this->hasColumn('user_id')) {
            $payload['user_id'] = $actorId;
        }
        if ($this->hasColumn('event')) {
            $payload['event'] = $action;
        }
        if ($this->hasColumn('auditable_type')) {
            $payload['auditable_type'] = $targetType ?? 'platform';
        }
        if ($this->hasColumn('auditable_id')) {
            $payload['auditable_id'] = $targetId ?? 0;
        }

        return $payload;
    }

    private function hasColumn(string $column): bool
    {
        if (self::$columns === null) {
            self::$columns = [];
            try {
                if (! Schema::hasTable('audit_logs')) {
                    return false;
                }
                foreach (Schema::getColumnListing('audit_logs') as $name) {
                    self::$columns[$name] = true;
                }
            } catch (Throwable) {
                self::$columns = [];
            }
        }

        return self::$columns[$column] ?? false;
    }
}
