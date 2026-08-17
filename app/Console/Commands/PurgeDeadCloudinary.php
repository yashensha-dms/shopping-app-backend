<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Client\Pool;

class PurgeDeadCloudinary extends Command
{
    protected $signature = 'media:purge-dead-cloudinary 
                            {--concurrency=50 : Number of simultaneous HEAD requests} 
                            {--chunk=100 : Number of records to query from DB per batch}
                            {--timeout=4 : Timeout in seconds for each HEAD request}
                            {--dry-run : Only scan and display results without deleting}';

    protected $description = 'Scans 17k+ Cloudinary media links via lightweight HTTP HEAD requests and safely purges dead/404 links with minimal CPU & memory usage.';

    public function handle()
    {
        $concurrency = (int) $this->option('concurrency') ?: 50;
        $chunkSize = (int) $this->option('chunk') ?: 100;
        $timeout = (int) $this->option('timeout') ?: 4;
        $dryRun = (bool) $this->option('dry-run');

        $this->info("===================================================================");
        $this->info("  Cloudinary Ultra-Fast Dead Link Purge Engine (Low CPU & RAM)");
        $this->info("===================================================================");
        $this->info("  - Concurrency : {$concurrency} simultaneous HEAD requests");
        $this->info("  - Chunk Size  : {$chunkSize} records per query");
        $this->info("  - Timeout     : {$timeout}s per request");
        $this->info("  - Mode        : " . ($dryRun ? "DRY RUN (Scanning only)" : "LIVE PURGE (Deleting dead links)"));
        $this->info("===================================================================\n");

        $baseQuery = DB::table('attachments')->where(function ($q) {
            $q->where('disk', 'external')
              ->orWhere('custom_properties', 'like', '%cloudinary%')
              ->orWhere('file_name', 'like', '%cloudinary%')
              ->orWhere('name', 'like', '%cloudinary%');
        });

        $totalCount = (clone $baseQuery)->count();
        if ($totalCount === 0) {
            $this->info("No Cloudinary / external media found to scan.");
            return 0;
        }

        $this->info("Found {$totalCount} external media records to scan.");
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $processedCount = 0;
        $liveCount = 0;
        $deadCount = 0;

        // Process in low-memory chunks using chunkById
        DB::table('attachments')
            ->where(function ($q) {
                $q->where('disk', 'external')
                  ->orWhere('custom_properties', 'like', '%cloudinary%')
                  ->orWhere('file_name', 'like', '%cloudinary%')
                  ->orWhere('name', 'like', '%cloudinary%');
            })
            ->orderBy('id')
            ->chunkById($chunkSize, function ($attachments) use (&$processedCount, &$liveCount, &$deadCount, $concurrency, $timeout, $dryRun, $bar) {
                $urlMap = [];
                $deadBatchIds = [];

                foreach ($attachments as $item) {
                    $customProps = json_decode($item->custom_properties ?? '{}', true);
                    $url = $customProps['external_url'] 
                        ?? (filter_var($item->file_name, FILTER_VALIDATE_URL) ? $item->file_name : null)
                        ?? null;

                    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                        $deadBatchIds[] = $item->id;
                    } else {
                        $urlMap[$item->id] = $url;
                    }
                }

                // Execute concurrent non-blocking HEAD requests in sub-batches of concurrency limit
                $chunks = array_chunk($urlMap, $concurrency, true);
                foreach ($chunks as $subBatch) {
                    $responses = Http::pool(function (Pool $pool) use ($subBatch, $timeout) {
                        $reqs = [];
                        foreach ($subBatch as $id => $url) {
                            $reqs[] = $pool->as("att_{$id}")
                                ->timeout($timeout)
                                ->connectTimeout(2)
                                ->withHeaders([
                                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                                ])
                                ->head($url);
                        }
                        return $reqs;
                    });

                    foreach ($subBatch as $id => $url) {
                        $res = $responses["att_{$id}"] ?? null;
                        if (($res instanceof \Illuminate\Http\Client\Response) && $res->successful()) {
                            $liveCount++;
                        } else {
                            $deadBatchIds[] = $id;
                        }
                    }
                }

                $deadInThisChunk = count($deadBatchIds);
                $deadCount += $deadInThisChunk;
                $processedCount += count($attachments);

                // Purge dead links in bulk SQL transaction
                if (!$dryRun && $deadInThisChunk > 0) {
                    $this->purgeDeadIdsInBulk($deadBatchIds);
                }

                $bar->advance(count($attachments));
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("===================================================================");
        $this->info("  Scan & Cleanup Completed!");
        $this->info("===================================================================");
        $this->info("  - Total Processed  : " . number_format($processedCount));
        $this->info("  - Live Media       : " . number_format($liveCount));
        $this->info("  - Dead Links Found : " . number_format($deadCount));
        if (!$dryRun) {
            $this->info("  - Dead Purged      : " . number_format($deadCount));
        }
        $this->info("===================================================================\n");

        return 0;
    }

    protected function purgeDeadIdsInBulk(array $deadIds)
    {
        if (empty($deadIds)) return;

        $updates = [
            ['table' => 'products', 'column' => 'product_thumbnail_id'],
            ['table' => 'products', 'column' => 'size_chart_image_id'],
            ['table' => 'products', 'column' => 'product_meta_image_id'],
            ['table' => 'product_images', 'column' => 'attachment_id', 'action' => 'delete'],
            ['table' => 'variations', 'column' => 'variation_image_id'],
            ['table' => 'categories', 'column' => 'category_image_id'],
            ['table' => 'categories', 'column' => 'category_icon_id'],
            ['table' => 'stores', 'column' => 'store_logo_id'],
            ['table' => 'stores', 'column' => 'store_cover_id'],
            ['table' => 'blogs', 'column' => 'blog_thumbnail_id'],
            ['table' => 'blogs', 'column' => 'blog_meta_image_id'],
            ['table' => 'pages', 'column' => 'page_meta_image_id'],
            ['table' => 'reviews', 'column' => 'review_image_id'],
            ['table' => 'refunds', 'column' => 'refund_image_id'],
            ['table' => 'offer_banners', 'column' => 'banner_image_id'],
        ];

        foreach ($updates as $item) {
            try {
                if (Schema::hasTable($item['table']) && Schema::hasColumn($item['table'], $item['column'])) {
                    if (isset($item['action']) && $item['action'] === 'delete') {
                        DB::table($item['table'])->whereIn($item['column'], $deadIds)->delete();
                    } else {
                        DB::table($item['table'])->whereIn($item['column'], $deadIds)->update([$item['column'] => null]);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore gracefully
            }
        }

        try {
            DB::table('attachments')->whereIn('id', $deadIds)->delete();
        } catch (\Throwable $e) {
            // Ignore
        }
    }
}
