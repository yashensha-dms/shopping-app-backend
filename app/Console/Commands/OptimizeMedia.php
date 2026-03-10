<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;

class OptimizeMedia extends Command
{
    protected $signature = 'media:optimize {--dry-run : Only show what would be done} {--limit= : Limit the number of processed attachments}';

    protected $description = 'Optimize existing image files in storage by resizing and compressing them, and update DB records';

    public function handle()
    {
        $this->info("Scanning Attachments for images...");

        $query = Attachment::whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp']);
        
        if ($this->option('limit')) {
            $query->limit((int) $this->option('limit'));
        }

        $attachments = $query->get();

        if ($attachments->isEmpty()) {
            $this->info("No image attachments found.");
            return 0;
        }

        $this->info("Found " . $attachments->count() . " images to process.");

        $bar = $this->output->createProgressBar($attachments->count());
        $bar->start();

        $totalOldSize = 0;
        $totalNewSize = 0;
        $processedCount = 0;

        foreach ($attachments as $attachment) {
            $path = $attachment->getPath();

            if (!File::exists($path)) {
                $this->warn("\nFile not found for Attachment ID {$attachment->id}: {$path}");
                $bar->advance();
                continue;
            }

            $oldSize = filesize($path);
            $totalOldSize += $oldSize;

            if (!$this->option('dry-run')) {
                try {
                    Image::load($path)
                        ->fit(Manipulations::FIT_MAX, 1920, 1920)
                        ->quality(80)
                        ->save();

                    clearstatcache();
                    $newSize = filesize($path);
                    $totalNewSize += $newSize;

                    // Update attachment record
                    $attachment->size = $newSize;
                    $attachment->save();
                    
                    $processedCount++;
                } catch (\Exception $e) {
                    $this->error("\nFailed to process Attachment ID {$attachment->id} at {$path}: " . $e->getMessage());
                    $totalNewSize += $oldSize; // Assume no change if failed
                }
            } else {
                $processedCount++;
                $totalNewSize += $oldSize; // For dry run, assume no change
            }

            $bar->advance();
        }

        $bar->finish();
        
        $savedBytes = $totalOldSize - $totalNewSize;
        $savedMB = round($savedBytes / 1024 / 1024, 2);
        
        $this->info("\nOptimization complete!");
        $this->info("Processed: {$processedCount} images.");
        if (!$this->option('dry-run')) {
            $this->info("Total space saved: {$savedMB} MB");
        } else {
            $this->info("Dry run complete. No files were modified.");
        }

        return 0;
    }
}
