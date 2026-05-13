<?php

namespace Database\Seeders;

use App\Models\Attachment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SyncAttachmentNamesFromCSVSeeder extends Seeder
{
    public function run()
    {
        $csvPath = base_path("product_set.csv");

        if (!file_exists($csvPath)) {
            echo "CSV file not found at: {$csvPath}\n";
            return;
        }

        echo "Fetching all attachments for mapping and backup...\n";
        $attachments = DB::table('attachments')
            ->select('id', 'name', 'custom_properties')
            ->whereNotNull('custom_properties')
            ->get();

        $urlMap = [];
        $backupData = [];
        
        foreach ($attachments as $attachment) {
            $props = json_decode($attachment->custom_properties, true);
            if (isset($props['external_url'])) {
                $urlMap[$props['external_url']] = $attachment->id;
                $backupData[$attachment->id] = $attachment->name;
            }
        }

        // Save backup to storage/app/backups/
        $backupPath = storage_path('app/backups');
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }
        $backupFile = $backupPath . '/attachment_names_backup_' . date('Y-m-d_H-i-s') . '.json';
        File::put($backupFile, json_encode($backupData, JSON_PRETTY_PRINT));
        echo "Backup created at: {$backupFile}\n";

        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file);
        $map = array_flip($headers);

        echo "Starting sync...\n";
        $updatedCount = 0;
        
        while (($row = fgetcsv($file)) !== false) {
            $nameFromCsv = $row[$map['Name']] ?? null;
            $urlFromCsv = $row[$map['STC']] ?? null;

            if ($nameFromCsv && $urlFromCsv && isset($urlMap[$urlFromCsv])) {
                $attachmentId = $urlMap[$urlFromCsv];
                DB::table('attachments')
                    ->where('id', $attachmentId)
                    ->update(['name' => $nameFromCsv]);
                $updatedCount++;
            }
        }

        fclose($file);
        echo "Sync complete! Updated {$updatedCount} names.\n";
    }
}
