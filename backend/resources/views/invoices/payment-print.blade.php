<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاکتور {{ $invoice['invoice_number'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Tahoma, 'Segoe UI', sans-serif; color: #1e293b; margin: 0; padding: 24px; background: #f8fafc; direction: rtl; }
        .sheet { max-width: 800px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; }
        .toolbar { max-width: 800px; margin: 0 auto 16px; display: flex; gap: 8px; }
        .toolbar button { background: #2563eb; color: #fff; border: none; border-radius: 8px; padding: 10px 18px; cursor: pointer; font-family: inherit; }
        .toolbar button.secondary { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .header { display: flex; justify-content: space-between; gap: 16px; border-bottom: 3px solid #2563eb; padding-bottom: 16px; margin-bottom: 20px; }
        .brand { font-size: 24px; font-weight: bold; color: #2563eb; }
        .type { font-size: 14px; color: #64748b; margin-top: 4px; }
        .meta { font-size: 13px; color: #64748b; line-height: 1.8; text-align: left; direction: ltr; }
        .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .party { background: #f8fafc; border-radius: 8px; padding: 12px; font-size: 13px; }
        .party strong { display: block; margin-bottom: 6px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: right; font-size: 13px; }
        th { background: #f1f5f9; }
        .totals { width: 300px; margin-right: auto; margin-top: 12px; }
        .totals td { border: none; padding: 6px 0; }
        .total-row td { font-weight: bold; font-size: 16px; color: #2563eb; border-top: 2px solid #2563eb !important; padding-top: 10px; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; margin-top: 6px; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .footer { margin-top: 28px; font-size: 12px; color: #94a3b8; text-align: center; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { border: none; border-radius: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">چاپ / ذخیره PDF</button>
        <button type="button" class="secondary" onclick="window.close()">بستن</button>
    </div>

    <div class="sheet">
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

        <div class="parties">
            <div class="party">
                <strong>خریدار</strong>
                {{ $invoice['buyer']['office_name'] ?? '—' }}<br>
                {{ $invoice['buyer']['user_name'] ?? '' }}<br>
                {{ $invoice['buyer']['user_phone'] ?? '' }}
            </div>
            <div class="party">
                <strong>فروشنده</strong>
                {{ $invoice['seller']['name'] }}<br>
                {{ $invoice['seller']['support_phone'] ?? '' }}<br>
                {{ $invoice['seller']['support_email'] ?? '' }}
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>شرح</th>
                    <th>تعداد</th>
                    <th>قیمت واحد (تومان)</th>
                    <th>تخفیف</th>
                    <th>جمع (تومان)</th>
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
        <p style="font-size:13px;">کد پیگیری: <strong dir="ltr">{{ $invoice['ref_id'] }}</strong> — درگاه: {{ $invoice['gateway_label'] }}</p>
        @endif

        <div class="footer">این سند توسط سامانه پوشه صادر شده است — posheapp.ir</div>
    </div>
</body>
</html>
