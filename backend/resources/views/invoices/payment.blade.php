<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: DejaVu Sans, Tahoma, sans-serif; color: #1e293b; margin: 24px; direction: rtl; text-align: right; font-size: 11pt; }
        .header-table { width: 100%; border-bottom: 3px solid #2563eb; margin-bottom: 20px; }
        .header-table td { border: none; padding: 8px 0; vertical-align: top; }
        .brand { font-size: 20px; font-weight: bold; color: #2563eb; }
        .type { font-size: 12px; color: #64748b; margin-top: 4px; }
        .meta { font-size: 11px; color: #64748b; line-height: 1.7; text-align: left; direction: ltr; }
        table.data { width: 100%; border-collapse: collapse; margin: 16px 0; }
        table.data th, table.data td { border: 1px solid #e2e8f0; padding: 8px; font-size: 11px; text-align: right; }
        table.data th { background: #f8fafc; }
        table.totals { width: 260px; margin-right: 0; margin-left: auto; border-collapse: collapse; }
        table.totals td { border: none; padding: 5px 0; font-size: 11px; }
        .total-row td { font-weight: bold; font-size: 13px; color: #2563eb; border-top: 2px solid #2563eb !important; padding-top: 8px; }
        .status { font-size: 10px; padding: 3px 10px; border: 1px solid #ccc; }
        .status-paid { color: #166534; }
        .status-pending { color: #92400e; }
        .footer { margin-top: 28px; font-size: 10px; color: #94a3b8; text-align: center; }
        .party-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .party-table td { border: none; width: 50%; vertical-align: top; padding: 4px 8px; font-size: 11px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="brand">{{ $invoice['seller']['name'] }}</div>
                <div class="type">{{ $invoice['invoice_type_label'] }}</div>
            </td>
            <td class="meta">
                <div>شماره: <strong>{{ $invoice['invoice_number'] }}</strong></div>
                <div>تاریخ: {{ $invoice['issued_at'] ? date('Y/m/d H:i', strtotime($invoice['issued_at'])) : now()->format('Y/m/d H:i') }}</div>
                <span class="status {{ $invoice['status'] === 'paid' ? 'status-paid' : 'status-pending' }}">{{ $invoice['status_label'] }}</span>
            </td>
        </tr>
    </table>

    <table class="party-table">
        <tr>
            <td>
                <strong>خریدار</strong><br>
                {{ $invoice['buyer']['office_name'] ?? '—' }}<br>
                {{ $invoice['buyer']['user_name'] ?? '' }}<br>
                {{ $invoice['buyer']['user_phone'] ?? '' }}
            </td>
            <td>
                <strong>فروشنده</strong><br>
                {{ $invoice['seller']['name'] }}<br>
                {{ $invoice['seller']['support_phone'] }}<br>
                {{ $invoice['seller']['support_email'] }}
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>شرح</th>
                <th>تعداد</th>
                <th>قیمت واحد</th>
                <th>تخفیف</th>
                <th>جمع</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice['items'] as $item)
            <tr>
                <td>{{ $item['title'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ number_format($item['unit_price']) }}</td>
                <td>{{ number_format($item['discount']) }}</td>
                <td>{{ number_format($item['total']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>جمع کل:</td><td>{{ number_format($invoice['subtotal']) }} {{ $invoice['currency'] }}</td></tr>
        @if($invoice['discount'] > 0)
        <tr><td>تخفیف:</td><td>{{ number_format($invoice['discount']) }} {{ $invoice['currency'] }}</td></tr>
        @endif
        @if($invoice['vat_amount'] > 0)
        <tr><td>مالیات ({{ $invoice['vat_percent'] }}%):</td><td>{{ number_format($invoice['vat_amount']) }} {{ $invoice['currency'] }}</td></tr>
        @endif
        <tr class="total-row"><td>مبلغ قابل پرداخت:</td><td>{{ number_format($invoice['total']) }} {{ $invoice['currency'] }}</td></tr>
    </table>

    @if(!empty($invoice['ref_id']))
    <p style="font-size:11px;">کد پیگیری: <strong>{{ $invoice['ref_id'] }}</strong> — درگاه: {{ $invoice['gateway_label'] }}</p>
    @endif

    <div class="footer">این سند توسط سامانه پوشه صادر شده است — posheapp.ir</div>
</body>
</html>
