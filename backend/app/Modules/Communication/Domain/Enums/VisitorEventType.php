<?php

namespace App\Modules\Communication\Domain\Enums;

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
