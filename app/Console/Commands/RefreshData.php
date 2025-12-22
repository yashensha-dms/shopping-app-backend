<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fastkart:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshing fastkart data...';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('db:wipe');
        $this->info('Importing dummy data...');
        $this->call('fastkart:import');
        $this->call('optimize:clear');
        $this->call('storage:link');
    }
}
