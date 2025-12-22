<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Installation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fastkart:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command line fastkart installation.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('If you have previously run this command or migrated tables, be advised that it will erase all of your data.');
        if ($this->confirm('Do you want to continue installation?')) {
            $this->info('Installing Fastkart ...');
            if ($this->confirm('Do you want to import dummy data?')) {
                $this->call('db:wipe');
                $this->info('Dropping all tables...');
                $this->info('Importing dummy data...');
                $this->call('fastkart:import');
                $this->info('Dummy Data Imported Successfully!');
            } else {

                $this->info('Migration is being run to build tables...');
                $this->call('migrate:fresh');
                $this->info('The seeder is being used for Generating the Administrator Credentials.');
                $this->call('db:seed');
                $this->info('Seed completed successfully!');
            }

            $this->info('');
            $this->info('Fastkart installed Successfully.');
        }
    }
}
