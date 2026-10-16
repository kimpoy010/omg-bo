<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Casino players the merchant reports on. Data only — no logins.
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        // Every bet/win/cancel processed through the OMG callback.
        Schema::create('omg_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('players')->cascadeOnDelete();
            $table->string('order_id', 64)->unique(); // OMG order_id — idempotency key
            $table->tinyInteger('type'); // 1=bet 2=cancel 3=win 4=round_end 5=luckwin 7=bonus
            $table->decimal('money', 15, 4); // negative=debit, positive=credit (as received from OMG)
            $table->decimal('balance_after', 15, 4);
            $table->unsignedInteger('game_id')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('round_id', 64)->nullable();
            $table->string('cancel_order_id', 64)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            // Reporting queries filter/group by type + created_at.
            $table->index(['type', 'created_at']);
            $table->index(['type', 'user_id', 'created_at']);
        });

        Schema::create('omg_rtp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('players')->cascadeOnDelete();
            $table->tinyInteger('switch')->default(1);       // 1=off 2=on
            $table->tinyInteger('control_type')->default(0); // 0=soft 1=hard
            $table->unsignedSmallInteger('rtp')->default(9000);
            $table->foreignId('set_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Single-row by design — one OMG merchant account per deployment.
        Schema::create('merchant_credit_wallets', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance', 18, 2)->default(0);
            $table->timestamp('last_reconciled_at')->nullable();
            $table->unsignedBigInteger('last_reconciled_transaction_id')->default(0);
            // Reports only count omg_transactions with id > reset_transaction_id.
            $table->unsignedBigInteger('reset_transaction_id')->default(0);
            $table->timestamp('reset_at')->nullable();
            $table->unsignedBigInteger('reset_by')->nullable();
            $table->timestamps();
        });

        DB::table('merchant_credit_wallets')->insert([
            'balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('merchant_credit_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['topup', 'deduction', 'reconciliation']);
            $table->decimal('amount', 18, 2); // signed
            $table->decimal('balance_after', 18, 2);
            // Merchant (users.id) who triggered it, if any; webmaster for top-ups/deductions.
            $table->foreignId('backoffice_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('webmaster_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('backoffice_operation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backoffice_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action', 'created_at']);
        });

        // The merchant's last-saved desired config per OMG game.
        Schema::create('game_configs', function (Blueprint $table) {
            $table->id();
            $table->string('game_id', 32)->unique();
            $table->string('game_name');
            $table->string('provider', 50)->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // One row per changed field; the webmaster applies these by hand in OMG's back office.
        Schema::create('game_config_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backoffice_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('game_id', 32);
            $table->string('game_name');
            $table->string('provider', 50)->nullable();
            $table->string('field', 50);
            $table->string('previous_value')->nullable();
            $table->string('new_value')->nullable();
            $table->enum('status', ['pending', 'applied'])->default('pending');
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['game_config_change_requests', 'game_configs', 'backoffice_operation_logs', 'merchant_credit_logs',
                  'merchant_credit_wallets', 'omg_rtp_settings', 'omg_transactions', 'players'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
