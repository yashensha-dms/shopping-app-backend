<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RollbackAttachmentNamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $backupPath = storage_path('app/backups');
        
        if (!File::exists($backupPath)) {
            echo "No backups directory found.\n";
            return;
        }

        // Get the latest backup file
        $files = File::files($backupPath);
        if (empty($files)) {
            echo "No backup files found.\n";
            return;
        }

        // Sort by modification time to get the latest
        usort($files, function($a, $b) {
            return $b->getMTime() - $a->getMTime();
        });

        $latestBackup = $files[0];
        echo "Rolling back from: " . $latestBackup->getFilename() . "\n";

        $backupData = json_decode(File::get($latestBackup), true);
        $restoredCount = 0;

        foreach ($backupData as $id => $oldName) {
            DB::table('attachments')
                ->where('id', $id)
                ->update(['name' => $oldName]);
            $restoredCount++;
        }

        echo "Rollback complete! Restored {$restoredCount} names.\n";
    }
}
