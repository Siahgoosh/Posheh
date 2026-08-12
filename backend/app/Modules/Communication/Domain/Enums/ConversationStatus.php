<?php

namespace App\Modules\Communication\Domain\Enums;

enum ConversationStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Waiting = 'waiting';
    case Closed = 'closed';
}
