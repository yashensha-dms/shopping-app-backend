<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateStorageUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:update-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all storage URLs in database to use local APP_URL';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $appUrl = config('app.url');
        $this->info("Updating storage URLs to use: {$appUrl}");

        // External URL patterns to replace
        $externalUrls = [
            'https://mstore.primeads.ai',
            'https://mstore.primeads.ai',
            'https://mstore.primeads.ai',
        ];

        $updatedCount = 0;

        try {
            // Update attachments using Eloquent
            $attachments = \App\Models\Attachment::all();
            foreach ($attachments as $attachment) {
                $originalUrl = $attachment->original_url;
                $newUrl = $originalUrl;
                
                foreach ($externalUrls as $externalUrl) {
                    $newUrl = str_replace($externalUrl, $appUrl, $newUrl);
                }
                
                if ($newUrl !== $originalUrl) {
                    $attachment->original_url = $newUrl;
                    $attachment->save();
                    $updatedCount++;
                }
            }
            
            if ($updatedCount > 0) {
                $this->line("Updated {$updatedCount} URLs in attachments table");
            }

            // Update settings table
            $settings = \App\Models\Setting::first();
            if ($settings) {
                $values = json_encode($settings->values);
                
                foreach ($externalUrls as $externalUrl) {
                    $values = str_replace($externalUrl, $appUrl, $values);
                }
                
                DB::table('settings')
                    ->where('id', $settings->id)
                    ->update(['values' => $values]);
                
                $this->line("Updated settings table");
            }

            // Update home_pages table
            $homePages = \App\Models\HomePage::all();
            foreach ($homePages as $homePage) {
                $content = json_encode($homePage->content);
                
                foreach ($externalUrls as $externalUrl) {
                    $content = str_replace($externalUrl, $appUrl, $content);
                }
                
                DB::table('home_pages')
                    ->where('id', $homePage->id)
                    ->update(['content' => $content]);
            }
            $this->line("Updated home_pages table");

            $this->info("✓ Successfully updated {$updatedCount} attachment URLs");
            $this->info("✓ All storage URLs now point to: {$appUrl}");

        } catch (\Exception $e) {
            $this->error("Error updating URLs: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
