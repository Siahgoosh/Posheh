<?php

namespace App\Models\Cro;

use Illuminate\Database\Eloquent\Model;

class CroExperiment extends Model
{
    protected $table = 'cro_experiments';

    protected $fillable = [
        'name', 'hypothesis', 'target_type', 'variant_a', 'variant_b', 'active_variant',
        'metric', 'start_date', 'end_date', 'status', 'sample_size_a', 'sample_size_b',
        'confidence', 'result', 'decision', 'meta',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'meta' => 'array',
        'confidence' => 'float',
    ];
}
