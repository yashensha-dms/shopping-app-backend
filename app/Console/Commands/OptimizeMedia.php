<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;

class OptimizeMedia extends Command
{
    protected $signature = 'media:optimize {--dry-run : Only show what would be done}';

    protected $description = 'Optimize existing image files in storage by resizing and compressing them';

    public function handle()
    {
        $storagePath = storage_path('app/public');
        if (!File::exists($storagePath)) {
            $this->error("Storage path not found: {$storagePath}");
            return 1;
        }

        $this->info("Scanning storage for images...");
        $files = File::allFiles($storagePath);
        
        $images = array_filter($files, function ($file) {
            return in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp']);
        });

        $this->info("Found " . count($images) . " images to process.");

        $bar = $this->output->createProgressBar(count($images));
        $bar->start();

        $totalSaved = 0;

        foreach ($images as $file) {
            $path = $file->getRealPath();
            $oldSize = filesize($path);

            if (!$this->option('dry-run')) {
                try {
                    Image::load($path)
                        ->fit(Manipulations::FIT_MAX, 1920, 1920)
                        ->quality(80)
                        ->save();

                    clearstatcache();
                    $newSize = filesize($path);
                    $totalSaved += ($oldSize - $newSize);
                } catch (\Exception $e) {
                    $this->error("\nFailed to process {$path}: " . $e->getMessage());
                }
            }

            $bar->advance();
        }

        $bar->finish();
        
        $savedMB = round($totalSaved / 1024 / 1024, 2);
        $this->info("\nOptimization complete!");
        if (!$this->option('dry-run')) {
            $this->info("Total space saved: {$savedMB} MB");
        }
    }
}
