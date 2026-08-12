<?php

namespace App\Modules\Communication\Domain\Enums;

enum MessageSenderType: string
{
    case Visitor = 'visitor';
    case Operator = 'operator';
    case System = 'system';
}
