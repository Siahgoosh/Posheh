<?php

namespace App\Traits;

use App\Models\Office;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOffice
{
    public static function bootBelongsToOffice(): void
    {
        static::creating(function ($model) {
            if (empty($model->office_id) && auth()->check() && auth()->user()->office_id) {
                $model->office_id = auth()->user()->office_id;
            }
        });

        static::addGlobalScope('office', function (Builder $builder) {
            if (auth()->check() && auth()->user()->office_id && ! auth()->user()->isSuperAdmin()) {
                $builder->where($builder->getModel()->getTable().'.office_id', auth()->user()->office_id);
            }
        });
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
