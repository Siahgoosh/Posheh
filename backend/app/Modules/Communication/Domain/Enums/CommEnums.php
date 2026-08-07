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

enum ConversationStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Waiting = 'waiting';
    case Closed = 'closed';
}

enum MessageSenderType: string
{
    case Visitor = 'visitor';
    case Operator = 'operator';
    case System = 'system';
}

enum VisitorEventType: string
{
    case PageView = 'page_view';
    case Click = 'click';
    case Scroll = 'scroll';
    case MouseMove = 'mouse_move';
    case PricingView = 'pricing_view';
    case DemoView = 'demo_view';
    case Download = 'download';
    case ChatOpen = 'chat_open';
    case ChatMessage = 'chat_message';
    case FormSubmit = 'form_submit';
}

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Demo = 'demo_scheduled';
    case Won = 'won';
    case Lost = 'lost';
}
