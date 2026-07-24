<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogService
{
    public function log(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $actorId = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'actor_id' => $actorId ?? auth()->id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? (string) $request->userAgent() : null,
        ]);
    }
}
