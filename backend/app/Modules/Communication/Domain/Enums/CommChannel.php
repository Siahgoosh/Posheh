<?php

namespace App\Modules\Communication\Domain\Enums;

enum CommChannel: string
{
    case Website = 'website';
    case WebApp = 'web_app';
    case Android = 'android';
    case Windows = 'windows';
    case Telegram = 'telegram';
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case ContactForm = 'contact_form';
    case Api = 'api';
}
