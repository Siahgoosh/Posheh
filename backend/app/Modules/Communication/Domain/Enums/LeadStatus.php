<?php

namespace App\Modules\Communication\Domain\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Demo = 'demo_scheduled';
    case Won = 'won';
    case Lost = 'lost';
}
