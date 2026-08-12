# حسابداری حرفه‌ای — پیشرفت پیاده‌سازی

شاخه: `cursor/accounting-professional-a876`

## Phase 1 — تحلیل (انجام شد)
جزئیات: `docs/ACCOUNTING-PHASE1-FA.md`

## Phase 2–4 — Database + Ledger + Chart of Accounts
- Migration: `2026_08_11_210000_create_professional_accounting_module.php`
- جداول: accounts, cash_accounts, pos_terminals, journal_entries/lines, cheques, settlements, audit_logs
- توسعه `accounting_transactions` و `commissions`
- `AccountingBootstrapService` سرفصل‌های پیش‌فرض
- `LedgerService` ثبت دوبل + reversal + تراز اجباری

## Phase 5–7 — Income/Expense/Cash/Bank + People + Commission
- `AccountingService`: create / transfer / void / dashboard / P&L
- `ChequeService`: ثبت، وصول، برگشت
- `SettlementService`: recognizeCommission + settleCommission
- `CommissionService` به Settlement وصل شد
- `AccountingReportService`: دفتر اشخاص، بدهکار/بستانکار، مشاوران، مالی معامله/ملک

## Phase 8–10 — UI + Deal/Property
- صفحه `/accounting` با تب‌های داشبورد، دریافت/پرداخت، صندوق، چک، تسویه، مشاوران، بدهکار/بستانکار، سودوزیان، سرفصل
- بخش «امور مالی معامله» در CRM
- بخش «وضعیت مالی» در جزئیات فایل ملک

## Phase 11–12 — امنیت و تست
- Tenant isolation با `office_id` در تمام سرویس‌ها
- ابطال فقط با reversal (نه حذف)
- Feature test: `tests/Feature/Accounting/AccountingLedgerTest.php`
- دسترسی حساس با `canManageOffice`؛ مشاور فقط حساب خودش در people ledger

## تاریخ شمسی (UI)
- کامپوننت `JalaliDatePicker` با تقویم جلالی واقعی (نه `type=date` میلادی)
- در حسابداری: درآمد/هزینه، انتقال، چک (صدور+سررسید)، فیلترها، سودوزیان
- نمایش لیست‌ها با `formatJalaliLong` / `formatJalaliYmd`
- فیلدهای `jalali_date` فرم پرونده نیز از همین picker استفاده می‌کنند
- API همچنان تاریخ را به صورت استاندارد `YYYY-MM-DD` میلادی ذخیره می‌کند

## باقیمانده (نسخه بعدی)
- Permissionهای دانه‌ای `accounting.*` در جدول جدا
- Export PDF/Excel برای گزارش‌ها
- Notification خودکار سررسید چک
- UI اسناد Journal دستی
- چند مشاور روی یک معامله با Commission Items
