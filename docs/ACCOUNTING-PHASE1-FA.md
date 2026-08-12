# Phase 1 — گزارش تحلیل حسابداری پوشه

تاریخ: ۲۰۲۶-۰۸-۱۱  
شاخه: `cursor/safe-bugfixes-batch-a876`

## خلاصه

ماژول حسابداری فعلی فقط یک دفتر دخل‌وخرج ساده است (`accounting_transactions` با type=income|expense).  
کمیسیون‌ها جدا هستند و هنگام تسویه سند حسابداری نمی‌سازند.  
کیف پول SaaS (`wallets`/`payments`) مربوط به اشتراک پلتفرم است و **باید جدا بماند**.

## موجودیت‌های فعلی قابل توسعه

| موجود | تصمیم |
|--------|--------|
| `accounting_transactions` + `AccountingService` + `AccountingPage` | **توسعه** — هسته تراکنش‌های عملیاتی |
| `commissions` + `CommissionService` + CRM `closed_won` | **توسعه** — موتور کمیسیون و اتصال به Ledger |
| `BelongsToOffice` / `office_id` | استفاده برای Multi-Tenant |
| `ActivityLogger` | Audit عملیات مالی |
| Export Excel/CSV موجود | گزارش‌ها |
| Jalali (`morilog` + `formatJalaliDate`) | نمایش تاریخ |

## Conflict Report

- **نساز دوباره:** جدول `payments`/`wallets` (SaaS)
- **Duplicate نکن:** صفحه کمیسیون جدا می‌ماند؛ حسابداری آن را تسویه می‌کند
- **Gapها:** Chart of Accounts، Double-entry، صندوق/بانک/POS، چک، تسویه، دفتر اشخاص، گزارش P&L حرفه‌ای، Permissionهای مالی

## Implementation Plan (ادغام‌شده)

1. Migration افزودنی (بدون حذف ستون‌های قدیمی)
2. Ledger + Chart of Accounts + Journal
3. Cash/Bank/POS + Income/Expense/Transfer
4. People ledger + Consultant accounts
5. Commission settlement → journal
6. Cheques + dashboard alerts
7. Frontend shell `/accounting/*`
8. Tests tenant isolation + balanced journal

## اصل معماری

```
Deal/CRM → Commission → Settlement → Journal (double-entry) → Cash/Bank/Cheque → Reports
```

موجودی حساب‌ها فقط از Ledger محاسبه می‌شود؛ UPDATE مستقیم موجودی ممنوع است.
