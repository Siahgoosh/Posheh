<?php

namespace Tests\Feature\Accounting;

use App\Enums\UserRole;
use App\Models\AccountingAccount;
use App\Models\AccountingCashAccount;
use App\Models\AccountingJournalEntry;
use App\Models\AccountingTransaction;
use App\Models\Commission;
use App\Models\Office;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Accounting\AccountingBootstrapService;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\ChequeService;
use App\Services\Accounting\LedgerService;
use App\Services\Accounting\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountingLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->office = Office::create([
            'name' => 'Test Office',
            'slug' => 'test-office-acc',
            'is_active' => true,
        ]);
        Wallet::create(['office_id' => $this->office->id, 'balance' => 0]);

        $plan = SubscriptionPlan::create([
            'slug' => 'basic-acc',
            'name' => 'پایه',
            'max_users' => 3,
            'max_properties' => 100,
            'storage_gb' => 5,
            'monthly_price' => 990000,
            'features' => ['accounting', 'crm', 'commissions'],
        ]);

        Subscription::create([
            'office_id' => $this->office->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->office->load('subscription.plan');
        $this->manager = User::create([
            'name' => 'Manager',
            'mobile' => '09121111113',
            'office_id' => $this->office->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);

        app(AccountingBootstrapService::class)->ensureDefaultsForOffice($this->office->id);
    }

    public function test_income_creates_balanced_journal(): void
    {
        Sanctum::actingAs($this->manager);

        $service = app(AccountingService::class);
        $tx = $service->create($this->manager, [
            'type' => 'income',
            'category' => 'سایر',
            'amount' => 5_000_000,
            'title' => 'درآمد تست',
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $this->assertSame('approved', $tx->status);
        $this->assertNotNull($tx->journal_entry_id);

        $entry = AccountingJournalEntry::with('lines')->findOrFail($tx->journal_entry_id);
        $this->assertTrue($entry->isBalanced());
        $this->assertSame(5_000_000, $entry->totalDebit());
        $this->assertSame(5_000_000, $entry->totalCredit());
    }

    public function test_expense_creates_balanced_journal(): void
    {
        Sanctum::actingAs($this->manager);
        $service = app(AccountingService::class);

        $service->create($this->manager, [
            'type' => 'income',
            'amount' => 10_000_000,
            'title' => 'موجودی اولیه',
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $tx = $service->create($this->manager, [
            'type' => 'expense',
            'amount' => 2_000_000,
            'title' => 'هزینه تبلیغات',
            'category' => 'تبلیغات',
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $entry = AccountingJournalEntry::with('lines')->findOrFail($tx->journal_entry_id);
        $this->assertTrue($entry->isBalanced());
    }

    public function test_unbalanced_journal_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $cash = AccountingAccount::query()
            ->where('office_id', $this->office->id)
            ->where('code', '1101')
            ->firstOrFail();

        app(LedgerService::class)->post(
            $this->manager,
            'manual',
            null,
            'نامتوازن',
            [
                ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
            ]
        );
    }

    public function test_cash_to_bank_transfer_is_balanced(): void
    {
        Sanctum::actingAs($this->manager);
        $service = app(AccountingService::class);

        $service->create($this->manager, [
            'type' => 'income',
            'amount' => 10_000_000,
            'title' => 'شارژ صندوق',
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $cash = AccountingCashAccount::query()
            ->where('office_id', $this->office->id)
            ->where('kind', 'cashbox')
            ->firstOrFail();
        $bank = AccountingCashAccount::query()
            ->where('office_id', $this->office->id)
            ->where('kind', 'bank')
            ->firstOrFail();

        $result = $service->transfer($this->manager, [
            'from_cash_account_id' => $cash->id,
            'to_cash_account_id' => $bank->id,
            'amount' => 3_000_000,
            'transaction_date' => now()->toDateString(),
            'description' => 'انتقال تست',
        ]);

        $this->assertInstanceOf(AccountingTransaction::class, $result['out']);
        $entry = AccountingJournalEntry::with('lines')->findOrFail($result['journal']->id);
        $this->assertTrue($entry->isBalanced());
    }

    public function test_cheque_receive_and_clear(): void
    {
        Sanctum::actingAs($this->manager);
        $cheques = app(ChequeService::class);

        $cheque = $cheques->create($this->manager, [
            'direction' => 'in',
            'cheque_number' => '123456',
            'amount' => 4_000_000,
            'due_date' => now()->addDays(3)->toDateString(),
            'bank_name' => 'ملی',
            'issuer_name' => 'مشتری',
        ]);

        $this->assertSame('received', $cheque->status);
        $cleared = $cheques->updateStatus($this->manager, $cheque->id, 'cleared');
        $this->assertSame('cleared', $cleared->status);
    }

    public function test_cheque_bounce_reverses_journal(): void
    {
        Sanctum::actingAs($this->manager);
        $cheques = app(ChequeService::class);

        $cheque = $cheques->create($this->manager, [
            'direction' => 'in',
            'cheque_number' => '999',
            'amount' => 1_000_000,
            'due_date' => now()->toDateString(),
        ]);

        $cheques->updateStatus($this->manager, $cheque->id, 'bounced');
        $entry = AccountingJournalEntry::find($cheque->journal_entry_id);
        $this->assertSame('reversed', $entry?->status);
    }

    public function test_commission_recognize_and_settle(): void
    {
        Sanctum::actingAs($this->manager);
        $consultant = User::create([
            'name' => 'Consultant',
            'mobile' => '09124444444',
            'office_id' => $this->office->id,
            'role' => UserRole::Consultant,
            'is_active' => true,
        ]);

        $commission = Commission::create([
            'office_id' => $this->office->id,
            'user_id' => $consultant->id,
            'title' => 'کمیسیون تست',
            'base_amount' => 100_000_000,
            'rate_percent' => 3,
            'commission_amount' => 3_000_000,
            'office_share_amount' => 1_800_000,
            'consultant_share_amount' => 1_200_000,
            'status' => 'pending',
        ]);

        $settlements = app(SettlementService::class);
        $settlements->recognizeCommission($this->manager, $commission);

        app(AccountingService::class)->create($this->manager, [
            'type' => 'income',
            'amount' => 5_000_000,
            'title' => 'موجودی',
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $settlement = $settlements->settleCommission($this->manager, $commission->id, []);
        $this->assertSame('approved', $settlement->status);
        $this->assertSame('paid', $commission->fresh()->status);
    }

    public function test_tenant_isolation_on_list(): void
    {
        $other = Office::create(['name' => 'Other', 'slug' => 'other-acc', 'is_active' => true]);
        Wallet::create(['office_id' => $other->id, 'balance' => 0]);
        $otherPlan = SubscriptionPlan::create([
            'slug' => 'other-acc-plan',
            'name' => 'دیگر',
            'max_users' => 3,
            'max_properties' => 100,
            'storage_gb' => 5,
            'monthly_price' => 990000,
            'features' => ['accounting'],
        ]);
        Subscription::create([
            'office_id' => $other->id,
            'subscription_plan_id' => $otherPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
        app(AccountingBootstrapService::class)->ensureDefaultsForOffice($other->id);

        Sanctum::actingAs($this->manager);
        app(AccountingService::class)->create($this->manager, [
            'type' => 'income',
            'amount' => 1_000_000,
            'title' => 'دفتر یک',
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $otherUser = User::create([
            'name' => 'Other Mgr',
            'mobile' => '09123333333',
            'office_id' => $other->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);

        Sanctum::actingAs($otherUser);
        $response = $this->getJson('/api/v1/accounting');
        $response->assertOk();
        $data = $response->json('data') ?? [];
        $this->assertCount(0, collect($data)->filter(fn ($row) => ($row['office_id'] ?? null) === $this->office->id));
        $this->assertCount(0, $data);
    }

    public function test_void_creates_reversal(): void
    {
        Sanctum::actingAs($this->manager);
        $service = app(AccountingService::class);
        $tx = $service->create($this->manager, [
            'type' => 'income',
            'amount' => 2_000_000,
            'title' => 'برای ابطال',
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $voided = $service->void($this->manager, $tx->id, 'تست');
        $this->assertSame('void', $voided->status);
        $entry = AccountingJournalEntry::find($tx->journal_entry_id);
        $this->assertSame('reversed', $entry?->status);
    }
}
