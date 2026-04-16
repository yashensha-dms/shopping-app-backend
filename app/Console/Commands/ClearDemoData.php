<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearDemoData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:clear-demo-data
                            {--force : Skip confirmation}
                            {--dry-run : Only show counts, do not delete}';

    /**
     * The console command description.
     */
    protected $description = 'Remove all demo/seeder data: stores, blogs, coupons, vendor wallets, and related attachments';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->option('dry-run')) {
            if (! $this->confirm('This will permanently delete stores, blogs, coupons, and related media. Continue?')) {
                return self::SUCCESS;
            }
        }

        $dryRun = $this->option('dry-run');

        // ── 1. Collect counts ────────────────────────────────────────────────
        $storeCount       = DB::table('stores')->count();
        $vendorWallets    = DB::table('vendor_wallets')->count();
        $vendorTx         = DB::table('vendor_transactions')->count();
        $blogCount        = DB::table('blogs')->count();
        $blogTagCount     = DB::table('blog_tags')->count();
        $blogCatCount     = DB::table('blog_categories')->count();
        $couponCount      = DB::table('coupons')->count();

        // Collect blog attachment IDs for media cleanup
        $blogAttachmentIds = collect();
        Blog::withTrashed()->each(function ($blog) use (&$blogAttachmentIds) {
            if ($blog->blog_thumbnail_id)   $blogAttachmentIds->push($blog->blog_thumbnail_id);
            if ($blog->blog_meta_image_id)  $blogAttachmentIds->push($blog->blog_meta_image_id);
        });
        $blogAttachmentIds = $blogAttachmentIds->unique()->filter();
        $attachmentCount   = $blogAttachmentIds->count();

        // ── 2. Show summary ──────────────────────────────────────────────────
        $this->table(
            ['Table', 'Records'],
            [
                ['stores',              $storeCount],
                ['vendor_wallets',      $vendorWallets],
                ['vendor_transactions', $vendorTx],
                ['blogs',               $blogCount],
                ['blog_tags (pivot)',   $blogTagCount],
                ['blog_categories (pivot)', $blogCatCount],
                ['blog attachments',    $attachmentCount],
                ['coupons',             $couponCount],
            ]
        );

        if ($dryRun) {
            $this->warn('[DRY RUN] No data was deleted.');
            return self::SUCCESS;
        }

        // ── 3. Delete blog attachment files ──────────────────────────────────
        if ($attachmentCount > 0) {
            $this->info("Deleting {$attachmentCount} blog attachments from storage...");
            $bar = $this->output->createProgressBar($attachmentCount);
            $bar->start();

            foreach ($blogAttachmentIds->chunk(100) as $chunk) {
                Attachment::whereIn('id', $chunk)->get()->each(function ($attachment) use ($bar) {
                    $attachment->delete(); // triggers Spatie file removal
                    $bar->advance();
                });
            }

            $bar->finish();
            $this->newLine();
        }

        // ── 4. Truncate tables (disable FK constraints first) ────────────────
        $this->info('Clearing demo database records...');

        Schema::disableForeignKeyConstraints();

        DB::table('blog_tags')->truncate();
        DB::table('blog_categories')->truncate();
        DB::table('blogs')->truncate();
        DB::table('coupons')->truncate();
        DB::table('vendor_transactions')->truncate();
        DB::table('vendor_wallets')->truncate();
        DB::table('stores')->truncate();

        Schema::enableForeignKeyConstraints();

        $this->info('✔ Demo data cleanup completed successfully!');
        $this->newLine();
        $this->line('  <comment>Tip:</comment> Your products, categories, users, and settings have NOT been touched.');

        return self::SUCCESS;
    }
}
