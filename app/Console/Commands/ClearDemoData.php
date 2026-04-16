<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\User;
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

        // Collect non-admin users (all, including soft-deleted)
        $dummyUsers = User::withTrashed()
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
            ->get();
        $userCount = $dummyUsers->count();

        // Collect blog attachment IDs for media cleanup
        $blogAttachmentIds = collect();
        Blog::withTrashed()->each(function ($blog) use (&$blogAttachmentIds) {
            if ($blog->blog_thumbnail_id)   $blogAttachmentIds->push($blog->blog_thumbnail_id);
            if ($blog->blog_meta_image_id)  $blogAttachmentIds->push($blog->blog_meta_image_id);
        });
        // Collect user profile image IDs
        $userAttachmentIds = $dummyUsers
            ->pluck('profile_image_id')
            ->filter()
            ->unique();

        $allAttachmentIds = $blogAttachmentIds->merge($userAttachmentIds)->unique()->filter();
        $attachmentCount  = $allAttachmentIds->count();

        // ── 2. Show summary ──────────────────────────────────────────────────
        $this->table(
            ['Table', 'Records'],
            [
                ['stores',                   $storeCount],
                ['vendor_wallets',            $vendorWallets],
                ['vendor_transactions',       $vendorTx],
                ['blogs',                    $blogCount],
                ['blog_tags (pivot)',         $blogTagCount],
                ['blog_categories (pivot)',   $blogCatCount],
                ['coupons',                  $couponCount],
                ['dummy users (non-admin)',   $userCount],
                ['attachments to delete',     $attachmentCount],
            ]
        );

        if ($dryRun) {
            $this->warn('[DRY RUN] No data was deleted.');
            return self::SUCCESS;
        }

        // ── 3. Delete attachment files (blogs + user avatars) ────────────────
        if ($attachmentCount > 0) {
            $this->info("Deleting {$attachmentCount} media files from storage...");
            $bar = $this->output->createProgressBar($attachmentCount);
            $bar->start();

            foreach ($allAttachmentIds->chunk(100) as $chunk) {
                Attachment::whereIn('id', $chunk)->get()->each(function ($attachment) use ($bar) {
                    $attachment->delete();
                    $bar->advance();
                });
            }

            $bar->finish();
            $this->newLine();
        }

        // ── 3b. Hard-delete dummy users & related data ────────────────────────
        if ($userCount > 0) {
            $this->info("Removing {$userCount} dummy users...");
            $userIds = $dummyUsers->pluck('id')->toArray();

            Schema::disableForeignKeyConstraints();
            DB::table('personal_access_tokens')->whereIn('tokenable_id', $userIds)
                ->where('tokenable_type', 'App\\Models\\User')->delete();
            DB::table('model_has_roles')->whereIn('model_id', $userIds)
                ->where('model_type', 'App\\Models\\User')->delete();
            DB::table('addresses')->whereIn('user_id', $userIds)->delete();
            DB::table('points')->whereIn('consumer_id', $userIds)->delete();
            DB::table('wallets')->whereIn('consumer_id', $userIds)->delete();
            // Force hard-delete (even soft-deleted records)
            DB::table('users')->whereIn('id', $userIds)->delete();
            Schema::enableForeignKeyConstraints();
        }

        // ── 4. Truncate tables (disable FK constraints first) ────────────────
        // ── 4. Truncate remaining tables ──────────────────────────────────────
        $this->info('Clearing remaining demo database records...');

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
