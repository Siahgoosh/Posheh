<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاکتور {{ $invoice['invoice_number'] }}</title>
    @if(empty($forPdf))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700&display=swap" rel="stylesheet">
    @endif
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: {{ $forPdf ?? false ? 'vazirmatn' : 'Vazirmatn' }}, Tahoma, sans-serif;
            color: #0f172a;
            direction: rtl;
            background: {{ ($forPdf ?? false) ? '#ffffff' : '#eef2ff' }};
            @if(empty($forPdf)) padding: 24px; @endif
        }
        .toolbar { max-width: 820px; margin: 0 auto 16px; }
        .toolbar button {
            background: {{ ($forPdf ?? false) ? '#2563eb' : 'linear-gradient(135deg, #2563eb, #1d4ed8)' }};
            color: #fff; border: none; border-radius: 10px;
            padding: 10px 20px; cursor: pointer; font-family: inherit; font-size: 14px;
            margin-left: 8px;
        }
        .toolbar button.secondary { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .invoice {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            border-radius: {{ ($forPdf ?? false) ? '0' : '16px' }};
            overflow: hidden;
            box-shadow: {{ ($forPdf ?? false) ? 'none' : '0 20px 60px rgba(37,99,235,0.12)' }};
        }
        .hero {
            background: {{ ($forPdf ?? false) ? '#1e40af' : 'linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%)' }};
            color: #fff;
            padding: 28px 32px;
        }
        .hero-table { width: 100%; border-collapse: collapse; }
        .hero-table td { vertical-align: top; border: none; padding: 0; }
        .brand { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }
        .brand-sub { font-size: 13px; opacity: 0.85; margin-top: 4px; }
        .doc-type {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.35);
            border-radius: 999px;
            padding: 4px 14px;
            font-size: 12px;
            margin-top: 10px;
        }
        .meta-box { text-align: left; direction: ltr; font-size: 12px; opacity: 0.95; line-height: 1.9; }
        .meta-box strong { color: #fff; font-size: 13px; }
        .body { padding: 28px 32px; }
        .parties { width: 100%; border-collapse: separate; border-spacing: 12px 0; margin-bottom: 20px; }
        .parties td { width: 50%; vertical-align: top; border: none; padding: 0; }
        .party-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }
        .party-label {
            font-size: 11px;
            font-weight: 700;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .party-name { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .party-detail { font-size: 12px; color: #64748b; line-height: 1.7; }
        .items { width: 100%; border-collapse: collapse; margin: 8px 0 20px; }
        .items th {
            background: #1e40af;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 12px 10px;
            text-align: right;
        }
        .items th:first-child { border-radius: 0 8px 0 0; }
        .items th:last-child { border-radius: 8px 0 0 0; }
        .items td {
            padding: 12px 10px;
            font-size: 13px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        .items tr:nth-child(even) td { background: #f8fafc; }
        .items .item-title { font-weight: 600; color: #0f172a; }
        .summary-wrap { width: 100%; }
        .summary-wrap td { border: none; padding: 0; vertical-align: top; }
        .summary {
            width: 280px;
            margin-right: auto;
            background: {{ ($forPdf ?? false) ? '#eff6ff' : 'linear-gradient(135deg, #eff6ff, #f0f9ff)' }};
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 16px 20px;
        }
        .summary-row { width: 100%; border-collapse: collapse; }
        .summary-row td { border: none; padding: 5px 0; font-size: 13px; color: #475569; }
        .summary-row .val { text-align: left; direction: ltr; font-weight: 600; }
        .summary-total td {
            border-top: 2px solid #2563eb !important;
            padding-top: 12px !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            color: #1e40af !important;
        }
        .ref-box {
            margin-top: 20px;
            padding: 12px 16px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            font-size: 12px;
            color: #166534;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 6px;
        }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px dashed #cbd5e1;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
        .footer strong { color: #2563eb; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .invoice { box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    @if(empty($forPdf))
    <div class="toolbar">
        <button type="button" onclick="window.print()">🖨 چاپ / ذخیره PDF</button>
        <button type="button" class="secondary" onclick="window.close()">بستن</button>
    </div>
    @endif

    <div class="invoice">
        <div class="hero">
            <table class="hero-table">
                <tr>
                    <td style="width:55%">
                        <div class="brand">{{ $invoice['seller']['name'] }}</div>
                        <div class="brand-sub">سامانه مدیریت املاک و CRM</div>
                        <div class="doc-type">{{ $invoice['invoice_type_label'] }}</div>
                    </td>
                    <td style="width:45%">
                        <div class="meta-box">
                            <div>شماره فاکتور: <strong>{{ $invoice['invoice_number'] }}</strong></div>
                            <div>تاریخ: <strong>{{ $invoice['issued_at_jalali'] ?? '—' }}</strong></div>
                            <span class="status-badge {{ $invoice['status'] === 'paid' ? 'status-paid' : 'status-pending' }}">
                                {{ $invoice['status_label'] }}
                            </span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="body">
            <table class="parties">
                <tr>
                    <td>
                        <div class="party-card">
                            <div class="party-label">خریدار</div>
                            <div class="party-name">{{ $invoice['buyer']['office_name'] ?? '—' }}</div>
                            <div class="party-detail">
                                @if($invoice['buyer']['user_name']){{ $invoice['buyer']['user_name'] }}<br>@endif
                                @if($invoice['buyer']['user_phone'])<span dir="ltr">{{ $invoice['buyer']['user_phone'] }}</span>@endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="party-card">
                            <div class="party-label">فروشنده</div>
                            <div class="party-name">{{ $invoice['seller']['name'] }}</div>
                            <div class="party-detail">
                                @if($invoice['seller']['support_phone'])<span dir="ltr">{{ $invoice['seller']['support_phone'] }}</span><br>@endif
                                {{ $invoice['seller']['support_email'] ?? '' }}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="items">
                <thead>
                    <tr>
                        <th style="width:40%">شرح کالا / خدمات</th>
                        <th style="width:10%">تعداد</th>
                        <th style="width:18%">قیمت واحد</th>
                        <th style="width:14%">تخفیف</th>
                        <th style="width:18%">جمع</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice['items'] as $item)
                    <tr>
                        <td class="item-title">{{ $item['title'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>{{ number_format($item['unit_price']) }}</td>
                        <td>{{ number_format($item['discount']) }}</td>
                        <td><strong>{{ number_format($item['total']) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="summary-wrap">
                <tr>
                    <td>
                        <div class="summary">
                            <table class="summary-row">
                                <tr>
                                    <td>جمع کل</td>
                                    <td class="val">{{ number_format($invoice['subtotal']) }} {{ $invoice['currency'] }}</td>
                                </tr>
                                @if($invoice['discount'] > 0)
                                <tr>
                                    <td>تخفیف</td>
                                    <td class="val" style="color:#16a34a">−{{ number_format($invoice['discount']) }}</td>
                                </tr>
                                @endif
                                @if($invoice['vat_amount'] > 0)
                                <tr>
                                    <td>مالیات ({{ $invoice['vat_percent'] }}%)</td>
                                    <td class="val">{{ number_format($invoice['vat_amount']) }}</td>
                                </tr>
                                @endif
                                <tr class="summary-total">
                                    <td>مبلغ قابل پرداخت</td>
                                    <td class="val">{{ number_format($invoice['total']) }} {{ $invoice['currency'] }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            @if(!empty($invoice['ref_id']))
            <div class="ref-box">
                ✓ پرداخت موفق — کد پیگیری: <strong dir="ltr">{{ $invoice['ref_id'] }}</strong>
                @if(!empty($invoice['gateway_label'])) — درگاه: {{ $invoice['gateway_label'] }}@endif
            </div>
            @endif

            <div class="footer">
                این سند توسط <strong>پوشه</strong> صادر شده است — <strong>posheapp.ir</strong>
            </div>
        </div>
    </div>
</body>
</html>
