<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Tahoma, sans-serif; color: #1e293b; margin: 0; padding: 24px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #2563eb; padding-bottom: 16px; margin-bottom: 24px; }
        .brand { font-size: 22px; font-weight: bold; color: #2563eb; }
        .type { font-size: 14px; color: #64748b; }
        .meta { font-size: 12px; color: #64748b; line-height: 1.8; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: right; font-size: 12px; }
        th { background: #f8fafc; }
        .totals { width: 280px; margin-right: auto; }
        .totals td { border: none; padding: 6px 0; }
        .total-row { font-weight: bold; font-size: 14px; color: #2563eb; border-top: 2px solid #2563eb !important; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .footer { margin-top: 32px; font-size: 11px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">{{ $invoice['seller']['name'] }}</div>
            <div class="type">{{ $invoice['invoice_type_label'] }}</div>
        </div>
        <div class="meta">
            <div>شماره: <strong>{{ $invoice['invoice_number'] }}</strong></div>
            <div>تاریخ: {{ $invoice['issued_at'] ? date('Y/m/d H:i', strtotime($invoice['issued_at'])) : now()->format('Y/m/d H:i') }}</div>
            <span class="status {{ $invoice['status'] === 'paid' ? 'status-paid' : 'status-pending' }}">{{ $invoice['status_label'] }}</span>
        </div>
    </div>

    <table style="border:none; margin-bottom: 8px;">
        <tr>
            <td style="border:none; width:50%; vertical-align:top;">
                <strong>خریدار</strong><br>
                {{ $invoice['buyer']['office_name'] ?? '—' }}<br>
                {{ $invoice['buyer']['user_name'] ?? '' }}<br>
                {{ $invoice['buyer']['user_phone'] ?? '' }}
            </td>
            <td style="border:none; width:50%; vertical-align:top;">
                <strong>فروشنده</strong><br>
                {{ $invoice['seller']['name'] }}<br>
                {{ $invoice['seller']['support_phone'] }}<br>
                {{ $invoice['seller']['support_email'] }}
            </td>
        </tr>
    </table>

    <table>
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

    @if($invoice['ref_id'])
    <p style="font-size:12px;">کد پیگیری: <strong>{{ $invoice['ref_id'] }}</strong> — درگاه: {{ $invoice['gateway_label'] }}</p>
    @endif

    <div class="footer">این سند توسط سامانه پوشه صادر شده است — posheapp.ir</div>
</body>
</html>
