<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete reset of all orders and transaction data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('This will wipe all orders, refunds, commissions, and transactions. Are you sure?')) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->warn('Hiding Foreign Key checks and truncating tables...');

        \Schema::disableForeignKeyConstraints();

        $tables = [
            'order_products',
            'order_transactions',
            'commission_histories',
            'refunds',
            'withdraw_requests',
            'vendor_transactions',
            'transactions',
            'reviews',
            'feedbacks',
            'orders',
        ];

        foreach ($tables as $table) {
            \DB::table($table)->truncate();
            $this->line("Truncated: $table");
        }

        $this->info('Resetting balances in wallets, points, and vendor_wallets...');

        \DB::table('wallets')->update(['balance' => 0.00]);
        \DB::table('points')->update(['balance' => 0.00]);
        \DB::table('vendor_wallets')->update(['balance' => 0.00]);

        \Schema::enableForeignKeyConstraints();

        $this->info('Clean setup of orders and transactions complete.');
    }
}
