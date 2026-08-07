<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetPlatformRevenueCommand extends Command
{
    protected $signature = 'platform:reset-revenue {--force : بدون تأیید interactive}';

    protected $description = 'صفر کردن درآمد پلتفرم (پرداخت‌ها و تراکنش‌های کیف پول) برای شروع شمارش از صفر';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('تمام پرداخت‌های ثبت‌شده و تراکنش‌های کیف پول حذف می‌شوند. ادامه؟')) {
            $this->warn('لغو شد.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            $payments = Payment::count();
            $tx = WalletTransaction::count();

            WalletTransaction::query()->delete();
            Payment::query()->delete();
            Wallet::query()->update(['balance' => 0]);

            $this->info("حذف شد: {$payments} پرداخت، {$tx} تراکنش کیف پول. موجودی کیف پول‌ها صفر شد.");
        });

        $this->info('درآمد پلتفرم اکنون صفر است. از این نقطه شمارش شروع می‌شود.');

        return self::SUCCESS;
    }
}
