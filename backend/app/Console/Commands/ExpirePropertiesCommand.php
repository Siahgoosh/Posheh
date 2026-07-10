<?php

namespace App\Console\Commands;

use App\Enums\PropertyStatus;
use App\Models\Property;
use Illuminate\Console\Command;

class ExpirePropertiesCommand extends Command
{
    protected $signature = 'properties:expire';

    protected $description = 'Mark properties past expires_at as expired';

    public function handle(): int
    {
        $count = Property::query()
            ->where('status', PropertyStatus::Active->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => PropertyStatus::Expired->value]);

        $this->info("Expired {$count} properties.");

        return self::SUCCESS;
    }
}
