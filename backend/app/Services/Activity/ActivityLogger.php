<?php

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function log(
        User $user,
        string $type,
        ?Model $subject = null,
        ?string $description = null,
        ?array $properties = null,
    ): Activity {
        return Activity::create([
            'office_id' => $user->office_id,
            'user_id' => $user->id,
            'type' => $type,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
        ]);
    }
}
