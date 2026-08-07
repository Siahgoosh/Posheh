<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommPermission extends Model
{
    protected $table = 'comm_permissions';

    protected $fillable = ['slug', 'name', 'group', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'comm_role_permissions',
            'permission_id',
            'role',
            'id',
            'role'
        );
    }
}
