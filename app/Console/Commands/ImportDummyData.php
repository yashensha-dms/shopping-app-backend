<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDummyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fastkart:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import fastkart dummy data.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sql = public_path('db.sql');
        $data = file_get_contents($sql);
        DB::unprepared($data);
    }
}
