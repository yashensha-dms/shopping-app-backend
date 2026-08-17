<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Pool;

class MigrateCloudinaryToLocal extends Command
{
    protected $signature = 'media:migrate-cloudinary-to-local 
                            {--concurrency=20 : Number of simultaneous image downloads} 
                            {--chunk=50 : Number of DB records per batch}
                            {--timeout=15 : Timeout in seconds for each download}';

    protected $description = 'Downloads all live Cloudinary images and saves them into local storage with identical IDs, maintaining 100% database relation integrity.';

    public function handle()
    {
        $concurrency = (int) $this->option('concurrency') ?: 20;
        $chunkSize = (int) $this->option('chunk') ?: 50;
        $timeout = (int) $this->option('timeout') ?: 15;

        $this->info("===================================================================");
        $this->info("  Cloudinary In-Place Migration Engine (Preserving Exact IDs)");
        $this->info("===================================================================");
        $this->info("  - Concurrency : {$concurrency} concurrent image downloads");
        $this->info("  - Chunk Size  : {$chunkSize} records per batch");
        $this->info("  - Timeout     : {$timeout}s per download");
        $this->info("===================================================================\n");

        $baseQuery = DB::table('attachments')->where(function ($q) {
            $q->where('disk', 'external')
              ->orWhere('custom_properties', 'like', '%cloudinary%')
              ->orWhere('file_name', 'like', '%cloudinary%')
              ->orWhere('name', 'like', '%cloudinary%');
        });

        $totalCount = (clone $baseQuery)->count();
        if ($totalCount === 0) {
            $this->info("No external Cloudinary media found to migrate.");
            return 0;
        }

        $this->info("Found {$totalCount} live Cloudinary media records to migrate to local storage.");
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $syncedCount = 0;
        $failedCount = 0;

        DB::table('attachments')
            ->where(function ($q) {
                $q->where('disk', 'external')
                  ->orWhere('custom_properties', 'like', '%cloudinary%')
                  ->orWhere('file_name', 'like', '%cloudinary%')
                  ->orWhere('name', 'like', '%cloudinary%');
            })
            ->orderBy('id')
            ->chunkById($chunkSize, function ($attachments) use (&$syncedCount, &$failedCount, $concurrency, $timeout, $bar) {
                $urlMap = [];

                foreach ($attachments as $item) {
                    $customProps = json_decode($item->custom_properties ?? '{}', true);
                    $url = $customProps['external_url'] 
                        ?? (filter_var($item->file_name, FILTER_VALIDATE_URL) ? $item->file_name : null)
                        ?? null;

                    if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                        $urlMap[$item->id] = [
                            'item' => $item,
                            'url' => $url,
                            'custom_props' => $customProps,
                        ];
                    } else {
                        $failedCount++;
                    }
                }

                // Download in concurrent sub-batches
                $chunks = array_chunk($urlMap, $concurrency, true);
                foreach ($chunks as $subBatch) {
                    $responses = Http::pool(function (Pool $pool) use ($subBatch, $timeout) {
                        $reqs = [];
                        foreach ($subBatch as $id => $data) {
                            $reqs[] = $pool->as("att_{$id}")
                                ->timeout($timeout)
                                ->connectTimeout(5)
                                ->withHeaders([
                                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                                ])
                                ->get($data['url']);
                        }
                        return $reqs;
                    });

                    foreach ($subBatch as $id => $data) {
                        $item = $data['item'];
                        $res = $responses["att_{$id}"] ?? null;

                        if (($res instanceof \Illuminate\Http\Client\Response) && $res->successful()) {
                            try {
                                $imageContent = $res->body();
                                $contentType = $res->header('Content-Type') ?: 'image/jpeg';

                                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                                $mimeType = $finfo->buffer($imageContent) ?: $contentType;

                                $extension = 'jpg';
                                if (str_contains($mimeType, 'png')) $extension = 'png';
                                elseif (str_contains($mimeType, 'webp')) $extension = 'webp';
                                elseif (str_contains($mimeType, 'gif')) $extension = 'gif';
                                elseif (str_contains($mimeType, 'svg')) $extension = 'svg';

                                $baseName = pathinfo($item->file_name, PATHINFO_FILENAME);
                                $cleanName = Str::slug($item->name ?: $baseName) ?: 'media_' . $item->id;
                                $finalFileName = $cleanName . '.' . $extension;
                                $fileSize = strlen($imageContent);

                                // Save into public disk at storage/app/public/{id}/{fileName}
                                $storageRelativePath = "{$item->id}/{$finalFileName}";
                                Storage::disk('public')->put($storageRelativePath, $imageContent);

                                $customProps = $data['custom_props'];
                                $customProps['synced_from'] = 'cloudinary';
                                $customProps['synced_at'] = now()->toIso8601String();
                                unset($customProps['external_url']);

                                DB::table('attachments')->where('id', $item->id)->update([
                                    'disk' => 'public',
                                    'conversions_disk' => 'public',
                                    'file_name' => $finalFileName,
                                    'mime_type' => $mimeType,
                                    'size' => $fileSize,
                                    'custom_properties' => json_encode($customProps),
                                    'updated_at' => now(),
                                ]);

                                $syncedCount++;
                            } catch (\Throwable $e) {
                                $failedCount++;
                            }
                        } else {
                            $failedCount++;
                        }
                    }
                }

                $bar->advance(count($attachments));
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("===================================================================");
        $this->info("  Migration Completed Successfully!");
        $this->info("===================================================================");
        $this->info("  - Total Synced to Local Disk : " . number_format($syncedCount));
        $this->info("  - Exact IDs Retained         : 100%");
        $this->info("  - Failures / Skipped         : " . number_format($failedCount));
        $this->info("===================================================================\n");

        return 0;
    }
}
