<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Page;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use App\Models\Variation;
use App\Models\Attachment;
use App\Models\ThemeOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ClearProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-products {--force : Skip confirmation} {--dry-run : Only show counts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all products, variations, and associated media files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('This will PERMANENTLY delete all products and their images. Are you sure?')) {
                return;
            }
        }

        $dryRun = $this->option('dry-run');

        // 1. Get all product IDs and their attachment IDs
        $products = Product::withTrashed()->get();
        $productCount = $products->count();

        $attachmentIds = collect();

        if ($productCount > 0) {
            foreach ($products as $product) {
                // Collect thumbnail, meta, and size chart IDs
                if ($product->product_thumbnail_id) $attachmentIds->push($product->product_thumbnail_id);
                if ($product->product_meta_image_id) $attachmentIds->push($product->product_meta_image_id);
                if ($product->size_chart_image_id) $attachmentIds->push($product->size_chart_image_id);

                // Collect gallery images
                $galleryIds = DB::table('product_images')->where('product_id', $product->id)->pluck('attachment_id');
                $attachmentIds = $attachmentIds->merge($galleryIds);

                // Collect variation images
                $variationImageIds = Variation::where('product_id', $product->id)->whereNotNull('variation_image_id')->pluck('variation_image_id');
                $attachmentIds = $attachmentIds->merge($variationImageIds);
            }
        } else {
            $this->info('No active products found in database.');
        }

        $uniqueAttachmentIds = $attachmentIds->unique()->filter();
        $attachmentCount = $uniqueAttachmentIds->count();

        if ($dryRun) {
            $this->warn("[DRY RUN] Would delete $productCount products and $attachmentCount unique attachments/files.");
        } else {
            $this->info("Deleting $productCount products and $attachmentCount attachments...");

            $bar = $this->output->createProgressBar($attachmentCount + 1);
            $bar->start();

            // 2. Delete Attachments (and physical files via Spatie MediaLibrary)
            foreach ($uniqueAttachmentIds->chunk(100) as $chunk) {
                $attachments = Attachment::whereIn('id', $chunk)->get();
                foreach ($attachments as $attachment) {
                    // Deleting the model should trigger Spatie's file deletion
                    $attachment->delete();
                    $bar->advance();
                }
            }

            // 3. Clear Database Records
            Schema::disableForeignKeyConstraints();

            DB::table('product_images')->truncate();
            DB::table('product_categories')->truncate();
            DB::table('product_tags')->truncate();
            DB::table('product_attributes')->truncate();
            DB::table('related_products')->truncate();
            DB::table('cross_sell_products')->truncate();
            DB::table('variation_attribute_values')->truncate();
            DB::table('variations')->truncate();
            DB::table('reviews')->truncate();
            DB::table('wishlists')->truncate();
            DB::table('carts')->truncate();
            DB::table('feedbacks')->truncate();
            DB::table('question_and_answers')->truncate();
            
            // Final wipe of products
            DB::table('products')->truncate();

            Schema::enableForeignKeyConstraints();

            $bar->finish();
            $this->newLine();
        }

        // 4. Handle Orphaned Attachments (if any remain)
        $this->deleteOrphanedAttachments($dryRun);

        $this->success("Cleanup completed successfully!");
    }

    private function deleteOrphanedAttachments($dryRun)
    {
        $this->info("Scanning for orphaned attachments...");

        // Collect all attachment IDs used by preserved models
        $usedIds = collect();

        // Add IDs from established relationships
        $usedIds = $usedIds->merge(Variation::whereNotNull('variation_image_id')->pluck('variation_image_id'));
        $usedIds = $usedIds->merge(Category::whereNotNull('category_image_id')->pluck('category_image_id'));
        $usedIds = $usedIds->merge(Category::whereNotNull('category_icon_id')->pluck('category_icon_id'));
        $usedIds = $usedIds->merge(Blog::whereNotNull('blog_thumbnail_id')->pluck('blog_thumbnail_id'));
        $usedIds = $usedIds->merge(Blog::whereNotNull('blog_meta_image_id')->pluck('blog_meta_image_id'));
        $usedIds = $usedIds->merge(User::whereNotNull('profile_image_id')->pluck('profile_image_id'));
        $usedIds = $usedIds->merge(Store::whereNotNull('store_logo_id')->pluck('store_logo_id'));
        $usedIds = $usedIds->merge(Store::whereNotNull('store_cover_id')->pluck('store_cover_id'));
        $usedIds = $usedIds->merge(Page::whereNotNull('page_meta_image_id')->pluck('page_meta_image_id'));

        // Scrape IDs from ThemeOption JSON
        ThemeOption::all()->each(function ($option) use (&$usedIds) {
            $raw = json_encode($option->getRawOriginal('options'));
            if (preg_match_all('/_id":\s*([0-9]+)/', $raw, $matches)) {
                $usedIds = $usedIds->merge($matches[1]);
            }
        });

        $uniqueUsedIds = $usedIds->unique()->filter()->values();

        // Find attachments not in the used list and likely product-related
        $orphansQuery = Attachment::whereNotIn('id', $uniqueUsedIds)
            ->where(function($query) {
                // We target standard media types or those explicitly orphaned
                $query->where('model_type', 'App\Models\Attachment')
                      ->orWhere('model_type', 'App\Models\Product')
                      ->orWhereNull('model_id');
            });

        $orphanCount = $orphansQuery->count();

        if ($orphanCount === 0) {
            $this->info("No orphaned attachments found.");
            return;
        }

        if ($dryRun) {
            $this->warn("[DRY RUN] Would delete $orphanCount additional orphaned attachments.");
            return;
        }

        $this->info("Deleting $orphanCount orphaned attachments...");
        $bar = $this->output->createProgressBar($orphanCount);
        $bar->start();

        foreach ($orphansQuery->cursor() as $attachment) {
            $attachment->delete();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function success($message)
    {
        $this->output->writeln("<info>✔</info> $message");
    }
}
