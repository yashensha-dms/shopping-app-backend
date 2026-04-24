<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Page;
use App\Models\User;
use App\Models\Store;
use App\Models\Review;
use App\Models\Product;
use App\Models\Category;
use App\Models\Variation;
use App\Models\Attachment;
use App\Models\ThemeOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ClearStorage extends Command
{
    protected $signature = 'app:clear-storage
                            {--force   : Skip confirmation prompt}
                            {--total   : Delete ALL attachments and storage files (Factory Reset)}
                            {--dry-run : Show what would be deleted without doing anything}';

    protected $description = 'Delete orphaned product/demo media from storage, preserving core system images (theme, categories, admin profile)';

    /**
     * Storage disk root where Spatie puts files: storage/app/public/{attachmentId}/file
     */
    private string $storagePath;

    public function handle(): int
    {
        $this->storagePath = storage_path('app/public');

        if (! is_dir($this->storagePath)) {
            $this->error("Storage directory not found: {$this->storagePath}");
            return self::FAILURE;
        }

        if ($this->option('total')) {
            return $this->handleTotalWipe();
        }

        // ── 1. Build the safe list ────────────────────────────────────────────
        $this->info('Building safe list of protected attachments...');
        $safeIds = $this->buildSafeList();
        $this->line("  → <comment>{$safeIds->count()}</comment> protected attachment ID(s) found.");

        // ── 2. Find all orphaned attachment records (DB) ─────────────────────
        // "Attachment pool" media unlinked from core concepts
        $orphanQuery   = Attachment::withTrashed()->whereNotIn('id', $safeIds->toArray());
        $orphanCount   = $orphanQuery->count();

        // ── 3. Find completely orphaned physical folders ─────────────────────
        // i.e., folders in storage that don't even have an attachment record
        $allAttachmentDbIds = Attachment::withTrashed()->pluck('id')->toArray();
        $physicalFolders = collect(File::directories($this->storagePath))
            ->map(fn($d) => ['path' => $d, 'id' => (int) basename($d)])
            ->filter(fn($d) => is_numeric(basename($d['path'])));

        // A physical folder is orphaned if its ID is NOT in the safe list AND 
        // we'll be deleting it if it's in the orphan query. So physical orphans
        // are folders that aren't in the safe list.
        $orphanPhysical = $physicalFolders->filter(fn($d) => ! $safeIds->contains($d['id']));
        $physicalCount = $orphanPhysical->count();

        if ($orphanCount === 0 && $physicalCount === 0) {
            $this->info('✔ Storage is already clean. Nothing to do.');
            return self::SUCCESS;
        }

        // ── 4. Summary ───────────────────────────────────────────────────────
        $this->table(
            ['Check', 'Count'],
            [
                ['Protected (safe) attachment IDs',  $safeIds->count()],
                ['Orphaned attachment DB pool records', $orphanCount],
                ['Orphaned physical storage folders', $physicalCount],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('[DRY RUN] No files or records were deleted.');
            return self::SUCCESS;
        }

        // ── 5. Delete orphaned attachment records via Eloquent ────────────────
        if ($orphanCount > 0) {
            $this->info("Force deleting {$orphanCount} orphaned DB attachments (+ their files)...");
            
            $ids = $orphanQuery->pluck('id');
            $bar = $this->output->createProgressBar($orphanCount);
            $bar->start();

            foreach ($ids->chunk(100) as $chunk) {
                Attachment::withTrashed()->whereIn('id', $chunk)->get()->each(function ($a) use ($bar) {
                    $a->forceDelete();
                    $bar->advance();
                });
            }
            $bar->finish();
            $this->newLine();
        }

        // ── 6. Physical sweep — catch phantom folders ───────────────────────
        // Only keep folders that belong to an Attachment STILL in the database
        $remainingDbIds = Attachment::withTrashed()->pluck('id')->toArray();
        $leftoverFolders = collect(File::directories($this->storagePath))
            ->map(fn($d) => ['path' => $d, 'id' => (int) basename($d)])
            ->filter(fn($d) => is_numeric(basename($d['path'])))
            ->filter(fn($d) => ! in_array($d['id'], $remainingDbIds));

        if ($leftoverFolders->count() > 0) {
            $this->info("Physical sweep: force removing {$leftoverFolders->count()} phantom folder(s)...");
            $bar = $this->output->createProgressBar($leftoverFolders->count());
            $bar->start();

            foreach ($leftoverFolders as $dir) {
                File::deleteDirectory($dir['path']);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->info('✔ Storage cleanup complete!');

        // ── 7. Final count ───────────────────────────────────────────────────
        $remainingFiles = 0;
        if (is_dir($this->storagePath)) {
            $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->storagePath));
            foreach ($rii as $file) {
                if ($file->isFile()) $remainingFiles++;
            }
        }
        $this->line("  Remaining files in storage: <comment>{$remainingFiles}</comment>");

        return self::SUCCESS;
    }

    private function handleTotalWipe(): int
    {
        $this->warn('!!! TOTAL WIPE INITIATED !!!');
        $this->warn('This will delete EVERY attachment and file in public storage.');

        if (! $this->option('force') && ! $this->confirm('Are you absolutely sure you want to delete everything?', false)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('[DRY RUN] Would truncate `attachments` table.');
            $this->info("[DRY RUN] Would delete all contents of: {$this->storagePath}");
            return self::SUCCESS;
        }

        // 1. Truncate DB
        $this->info('Truncating `attachments` table...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Attachment::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Clear Storage
        $this->info('Clearing physical storage...');
        $files = File::allFiles($this->storagePath, true);
        $directories = File::directories($this->storagePath);

        $bar = $this->output->createProgressBar(count($files) + count($directories));
        $bar->start();

        // Delete files
        foreach ($files as $file) {
            if ($file->getFilename() !== '.gitignore') {
                File::delete($file->getPathname());
            }
            $bar->advance();
        }

        // Delete directories
        foreach ($directories as $directory) {
            File::deleteDirectory($directory);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('✔ Factory reset complete. Storage is empty.');
        return self::SUCCESS;
    }

    /**
     * Collect all attachment IDs that must NOT be deleted.
     *
     * Safe sources:
     *   - All user profile images (especially admin)
     *   - Category images and icons
     *   - Page meta images
     *   - All numeric _id values found inside ThemeOption JSON
     *     (covers store logo, favicon, hero images, etc.)
     */
    private function buildSafeList(): \Illuminate\Support\Collection
    {
        $safeIds = collect();

        // 1. User profile images (admin + any remaining)
        $safeIds = $safeIds->merge(
            User::withTrashed()->whereNotNull('profile_image_id')->pluck('profile_image_id')
        );

        // 2. Category images and icons
        $safeIds = $safeIds->merge(
            Category::withTrashed()->whereNotNull('category_image_id')->pluck('category_image_id')
        );
        $safeIds = $safeIds->merge(
            Category::withTrashed()->whereNotNull('category_icon_id')->pluck('category_icon_id')
        );

        // 3. Products: Thumbnails, Meta, Size Charts, and Galleries
        $safeIds = $safeIds->merge(
            Product::withTrashed()->whereNotNull('product_thumbnail_id')->pluck('product_thumbnail_id')
        );
        $safeIds = $safeIds->merge(
            Product::withTrashed()->whereNotNull('product_meta_image_id')->pluck('product_meta_image_id')
        );
        $safeIds = $safeIds->merge(
            Product::withTrashed()->whereNotNull('size_chart_image_id')->pluck('size_chart_image_id')
        );
        $safeIds = $safeIds->merge(
            DB::table('product_images')->pluck('attachment_id')
        );

        // 4. Variations
        $safeIds = $safeIds->merge(
            Variation::whereNotNull('variation_image_id')->pluck('variation_image_id')
        );

        // 5. Stores: Logos and Covers
        $safeIds = $safeIds->merge(
            Store::withTrashed()->whereNotNull('store_logo_id')->pluck('store_logo_id')
        );
        $safeIds = $safeIds->merge(
            Store::withTrashed()->whereNotNull('store_cover_id')->pluck('store_cover_id')
        );

        // 6. Blogs: Thumbnails and Meta
        $safeIds = $safeIds->merge(
            Blog::withTrashed()->whereNotNull('blog_thumbnail_id')->pluck('blog_thumbnail_id')
        );
        $safeIds = $safeIds->merge(
            Blog::withTrashed()->whereNotNull('blog_meta_image_id')->pluck('blog_meta_image_id')
        );

        // 7. Reviews
        $safeIds = $safeIds->merge(
            Review::withTrashed()->whereNotNull('review_image_id')->pluck('review_image_id')
        );

        // 8. Page meta images
        if (class_exists(Page::class)) {
            $safeIds = $safeIds->merge(
                Page::withTrashed()->whereNotNull('page_meta_image_id')->pluck('page_meta_image_id')
            );
        }

        // 9. ThemeOption JSON — extract every value that looks like an attachment ID
        ThemeOption::all()->each(function ($option) use (&$safeIds) {
            $raw = json_encode($option->getRawOriginal('options'));
            if (preg_match_all('/_id["\s]*:\s*([0-9]+)/', $raw, $matches)) {
                $safeIds = $safeIds->merge(array_map('intval', $matches[1]));
            }
        });

        return $safeIds->unique()->filter()->values();
    }
}
