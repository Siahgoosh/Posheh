<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Professional office accounting — additive only.
 * Extends existing accounting_transactions; does not touch wallets/payments (SaaS).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_accounts')) {
            Schema::create('accounting_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('code', 32);
                $table->string('name');
                $table->string('group_key', 40); // assets|liabilities|income|expenses|equity
                $table->string('type', 40); // cash|bank|receivable|payable|income|expense|equity|other
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['office_id', 'code']);
                $table->index(['office_id', 'group_key', 'is_active']);
            });
        }

        if (! Schema::hasTable('accounting_cash_accounts')) {
            Schema::create('accounting_cash_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('ledger_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
                $table->string('name');
                $table->string('kind', 20); // cashbox|bank
                $table->string('bank_name')->nullable();
                $table->string('account_holder')->nullable();
                $table->string('account_number', 64)->nullable();
                $table->string('card_number', 32)->nullable();
                $table->string('iban', 34)->nullable();
                $table->bigInteger('opening_balance')->default(0);
                $table->date('opening_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['office_id', 'kind', 'is_active']);
            });
        }

        if (! Schema::hasTable('accounting_pos_terminals')) {
            Schema::create('accounting_pos_terminals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cash_account_id')->nullable()->constrained('accounting_cash_accounts')->nullOnDelete();
                $table->string('name');
                $table->string('bank_name')->nullable();
                $table->string('terminal_id', 64)->nullable();
                $table->string('merchant_id', 64)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['office_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('accounting_journal_entries')) {
            Schema::create('accounting_journal_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('entry_number', 40);
                $table->date('entry_date');
                $table->string('status', 20)->default('approved'); // draft|pending|approved|cancelled|reversed
                $table->string('source_type', 40)->nullable(); // income|expense|receipt|payment|transfer|cheque|settlement|commission|manual
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('description')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('reversed_entry_id')->nullable()->constrained('accounting_journal_entries')->nullOnDelete();
                $table->timestamps();
                $table->unique(['office_id', 'entry_number']);
                $table->index(['office_id', 'entry_date', 'status']);
                $table->index(['office_id', 'source_type', 'source_id']);
            });
        }

        if (! Schema::hasTable('accounting_journal_lines')) {
            Schema::create('accounting_journal_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('journal_entry_id')->constrained('accounting_journal_entries')->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('accounting_accounts')->restrictOnDelete();
                $table->unsignedBigInteger('debit')->default(0);
                $table->unsignedBigInteger('credit')->default(0);
                $table->string('memo')->nullable();
                $table->nullableMorphs('party'); // party_type, party_id
                $table->timestamps();
                $table->index(['office_id', 'account_id']);
            });
        }

        if (! Schema::hasTable('accounting_cheques')) {
            Schema::create('accounting_cheques', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('direction', 10); // in|out
                $table->string('cheque_number', 64);
                $table->string('sayad_id', 64)->nullable();
                $table->unsignedBigInteger('amount');
                $table->string('bank_name')->nullable();
                $table->string('branch_name')->nullable();
                $table->string('issuer_name')->nullable();
                $table->date('issue_date')->nullable();
                $table->date('due_date');
                $table->string('status', 30)->default('received'); // received|pending|cleared|bounced|returned|spent|cancelled|issued
                $table->nullableMorphs('party');
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('crm_deal_id')->nullable()->constrained('crm_deals')->nullOnDelete();
                $table->foreignId('cash_account_id')->nullable()->constrained('accounting_cash_accounts')->nullOnDelete();
                $table->foreignId('journal_entry_id')->nullable()->constrained('accounting_journal_entries')->nullOnDelete();
                $table->text('description')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['office_id', 'direction', 'status']);
                $table->index(['office_id', 'due_date']);
            });
        }

        if (! Schema::hasTable('accounting_settlements')) {
            Schema::create('accounting_settlements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('settlement_number', 40);
                $table->string('kind', 30); // consultant|customer|owner|deal|other
                $table->unsignedBigInteger('amount');
                $table->date('settlement_date');
                $table->string('payment_method', 30)->default('cash'); // cash|bank|pos|cheque|transfer
                $table->foreignId('cash_account_id')->nullable()->constrained('accounting_cash_accounts')->nullOnDelete();
                $table->foreignId('cheque_id')->nullable()->constrained('accounting_cheques')->nullOnDelete();
                $table->foreignId('consultant_id')->nullable()->constrained('users')->nullOnDelete();
                $table->nullableMorphs('party');
                $table->foreignId('crm_deal_id')->nullable()->constrained('crm_deals')->nullOnDelete();
                $table->foreignId('journal_entry_id')->nullable()->constrained('accounting_journal_entries')->nullOnDelete();
                $table->string('reference')->nullable();
                $table->text('description')->nullable();
                $table->string('status', 20)->default('approved');
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['office_id', 'settlement_number']);
                $table->index(['office_id', 'settlement_date']);
            });
        }

        if (! Schema::hasTable('accounting_settlement_items')) {
            Schema::create('accounting_settlement_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('settlement_id')->constrained('accounting_settlements')->cascadeOnDelete();
                $table->foreignId('commission_id')->nullable()->constrained('commissions')->nullOnDelete();
                $table->unsignedBigInteger('amount');
                $table->string('label')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('accounting_audit_logs')) {
            Schema::create('accounting_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 60);
                $table->string('auditable_type', 120)->nullable();
                $table->unsignedBigInteger('auditable_id')->nullable();
                $table->json('before')->nullable();
                $table->json('after')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamps();
                $table->index(['office_id', 'created_at']);
                $table->index(['auditable_type', 'auditable_id']);
            });
        }

        Schema::table('accounting_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('accounting_transactions', 'transaction_number')) {
                $table->string('transaction_number', 40)->nullable()->after('id');
            }
            if (! Schema::hasColumn('accounting_transactions', 'status')) {
                $table->string('status', 20)->default('approved')->after('type');
            }
            if (! Schema::hasColumn('accounting_transactions', 'payment_method')) {
                $table->string('payment_method', 30)->nullable()->after('category');
            }
            if (! Schema::hasColumn('accounting_transactions', 'account_id')) {
                $table->foreignId('account_id')->nullable()->after('property_id')->constrained('accounting_accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('accounting_transactions', 'cash_account_id')) {
                $table->foreignId('cash_account_id')->nullable()->after('account_id')->constrained('accounting_cash_accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('accounting_transactions', 'pos_terminal_id')) {
                $table->foreignId('pos_terminal_id')->nullable()->after('cash_account_id')->constrained('accounting_pos_terminals')->nullOnDelete();
            }
            if (! Schema::hasColumn('accounting_transactions', 'crm_deal_id')) {
                $table->foreignId('crm_deal_id')->nullable()->after('property_id')->constrained('crm_deals')->nullOnDelete();
            }
            if (! Schema::hasColumn('accounting_transactions', 'commission_id')) {
                $table->foreignId('commission_id')->nullable()->after('crm_deal_id')->constrained('commissions')->nullOnDelete();
            }
            if (! Schema::hasColumn('accounting_transactions', 'consultant_id')) {
                $table->foreignId('consultant_id')->nullable()->after('commission_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('accounting_transactions', 'journal_entry_id')) {
                $table->foreignId('journal_entry_id')->nullable()->after('consultant_id')->constrained('accounting_journal_entries')->nullOnDelete();
            }
            if (! Schema::hasColumn('accounting_transactions', 'party_type')) {
                $table->string('party_type', 80)->nullable()->after('journal_entry_id');
                $table->unsignedBigInteger('party_id')->nullable()->after('party_type');
                $table->index(['party_type', 'party_id']);
            }
            if (! Schema::hasColumn('accounting_transactions', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('accounting_transactions', 'voided_at')) {
                $table->timestamp('voided_at')->nullable();
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('void_reason')->nullable();
            }
        });

        Schema::table('commissions', function (Blueprint $table) {
            if (! Schema::hasColumn('commissions', 'office_share_amount')) {
                $table->unsignedBigInteger('office_share_amount')->nullable()->after('commission_amount');
            }
            if (! Schema::hasColumn('commissions', 'consultant_share_amount')) {
                $table->unsignedBigInteger('consultant_share_amount')->nullable()->after('office_share_amount');
            }
            if (! Schema::hasColumn('commissions', 'accounting_settlement_id')) {
                $table->foreignId('accounting_settlement_id')->nullable()->after('status')->constrained('accounting_settlements')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            if (Schema::hasColumn('commissions', 'accounting_settlement_id')) {
                $table->dropConstrainedForeignId('accounting_settlement_id');
            }
            foreach (['office_share_amount', 'consultant_share_amount'] as $col) {
                if (Schema::hasColumn('commissions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Drop new FKs on accounting_transactions carefully
        Schema::table('accounting_transactions', function (Blueprint $table) {
            foreach ([
                'account_id', 'cash_account_id', 'pos_terminal_id', 'crm_deal_id',
                'commission_id', 'consultant_id', 'journal_entry_id', 'approved_by', 'voided_by',
            ] as $col) {
                if (Schema::hasColumn('accounting_transactions', $col)) {
                    try {
                        $table->dropConstrainedForeignId($col);
                    } catch (\Throwable) {
                        // ignore if not FK
                    }
                }
            }
        });

        Schema::dropIfExists('accounting_audit_logs');
        Schema::dropIfExists('accounting_settlement_items');
        Schema::dropIfExists('accounting_settlements');
        Schema::dropIfExists('accounting_cheques');
        Schema::dropIfExists('accounting_journal_lines');
        Schema::dropIfExists('accounting_journal_entries');
        Schema::dropIfExists('accounting_pos_terminals');
        Schema::dropIfExists('accounting_cash_accounts');
        Schema::dropIfExists('accounting_accounts');
    }
};
