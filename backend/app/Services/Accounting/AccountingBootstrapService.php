<?php

namespace App\Services\Accounting;

use App\Models\AccountingAccount;
use App\Models\AccountingCashAccount;
use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountingBootstrapService
{
    /** @return list<array{code:string,name:string,group_key:string,type:string,sort_order:int}> */
    public function defaultAccounts(): array
    {
        return [
            ['code' => '1101', 'name' => 'صندوق', 'group_key' => 'assets', 'type' => 'cash', 'sort_order' => 10],
            ['code' => '1102', 'name' => 'بانک', 'group_key' => 'assets', 'type' => 'bank', 'sort_order' => 20],
            ['code' => '1103', 'name' => 'کارت‌خوان', 'group_key' => 'assets', 'type' => 'bank', 'sort_order' => 30],
            ['code' => '1201', 'name' => 'حساب‌های دریافتنی', 'group_key' => 'assets', 'type' => 'receivable', 'sort_order' => 40],
            ['code' => '1202', 'name' => 'چک‌های دریافتی', 'group_key' => 'assets', 'type' => 'receivable', 'sort_order' => 50],
            ['code' => '2101', 'name' => 'حساب‌های پرداختنی', 'group_key' => 'liabilities', 'type' => 'payable', 'sort_order' => 60],
            ['code' => '2102', 'name' => 'بدهی به مشاوران', 'group_key' => 'liabilities', 'type' => 'payable', 'sort_order' => 70],
            ['code' => '2103', 'name' => 'چک‌های پرداختی', 'group_key' => 'liabilities', 'type' => 'payable', 'sort_order' => 80],
            ['code' => '4101', 'name' => 'کمیسیون فروش', 'group_key' => 'income', 'type' => 'income', 'sort_order' => 90],
            ['code' => '4102', 'name' => 'کمیسیون خرید', 'group_key' => 'income', 'type' => 'income', 'sort_order' => 100],
            ['code' => '4103', 'name' => 'کمیسیون اجاره', 'group_key' => 'income', 'type' => 'income', 'sort_order' => 110],
            ['code' => '4104', 'name' => 'کمیسیون رهن', 'group_key' => 'income', 'type' => 'income', 'sort_order' => 120],
            ['code' => '4199', 'name' => 'سایر درآمدها', 'group_key' => 'income', 'type' => 'income', 'sort_order' => 130],
            ['code' => '5101', 'name' => 'حقوق', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 140],
            ['code' => '5102', 'name' => 'تبلیغات', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 150],
            ['code' => '5103', 'name' => 'اجاره دفتر', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 160],
            ['code' => '5104', 'name' => 'اینترنت و تلفن', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 170],
            ['code' => '5105', 'name' => 'آب و برق و گاز', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 180],
            ['code' => '5106', 'name' => 'پذیرایی', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 190],
            ['code' => '5107', 'name' => 'حمل‌ونقل', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 200],
            ['code' => '5108', 'name' => 'تعمیرات', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 210],
            ['code' => '5109', 'name' => 'نرم‌افزار و سرویس‌ها', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 220],
            ['code' => '5110', 'name' => 'مالیات و عوارض', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 230],
            ['code' => '5199', 'name' => 'سایر هزینه‌ها', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 240],
            ['code' => '5201', 'name' => 'پرداخت سهم مشاور', 'group_key' => 'expenses', 'type' => 'expense', 'sort_order' => 250],
        ];
    }

    public function ensureDefaultsForOffice(int $officeId): void
    {
        DB::transaction(function () use ($officeId) {
            foreach ($this->defaultAccounts() as $row) {
                AccountingAccount::withTrashed()->updateOrCreate(
                    ['office_id' => $officeId, 'code' => $row['code']],
                    [
                        'name' => $row['name'],
                        'group_key' => $row['group_key'],
                        'type' => $row['type'],
                        'is_system' => true,
                        'is_active' => true,
                        'sort_order' => $row['sort_order'],
                        'deleted_at' => null,
                    ]
                );
            }

            $cashLedger = AccountingAccount::where('office_id', $officeId)->where('code', '1101')->first();
            $bankLedger = AccountingAccount::where('office_id', $officeId)->where('code', '1102')->first();

            AccountingCashAccount::withTrashed()->updateOrCreate(
                ['office_id' => $officeId, 'name' => 'صندوق اصلی'],
                [
                    'ledger_account_id' => $cashLedger?->id,
                    'kind' => 'cashbox',
                    'opening_balance' => 0,
                    'is_active' => true,
                    'deleted_at' => null,
                ]
            );

            AccountingCashAccount::withTrashed()->firstOrCreate(
                ['office_id' => $officeId, 'name' => 'حساب بانکی اصلی'],
                [
                    'ledger_account_id' => $bankLedger?->id,
                    'kind' => 'bank',
                    'opening_balance' => 0,
                    'is_active' => true,
                ]
            );
        });
    }

    public function ensureForUser(User $user): void
    {
        if (! $user->office_id) {
            return;
        }
        $this->ensureDefaultsForOffice((int) $user->office_id);
    }

    public function ensureForOffice(Office $office): void
    {
        $this->ensureDefaultsForOffice((int) $office->id);
    }
}
