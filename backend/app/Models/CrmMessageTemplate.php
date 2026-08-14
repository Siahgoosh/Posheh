<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmMessageTemplate extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'name', 'channel', 'purpose', 'body', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function render(array $vars): string
    {
        $body = $this->body;
        foreach ($vars as $k => $v) {
            $body = str_replace('{'.$k.'}', (string) $v, $body);
        }
        return $body;
    }
}
