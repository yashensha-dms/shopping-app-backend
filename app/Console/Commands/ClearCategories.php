<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-categories {--force : Skip confirmation} {--dry-run : Only show counts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all categories and associated media files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('This will PERMANENTLY delete all categories and their icons/images. Are you sure?')) {
                return;
            }
        }

        $dryRun = $this->option('dry-run');

        // 1. Get all categories and their attachment IDs
        $categories = Category::withTrashed()->get();
        $categoryCount = $categories->count();

        if ($categoryCount === 0) {
            $this->info('No categories found.');
            return;
        }

        $attachmentIds = collect();

        foreach ($categories as $category) {
            if ($category->category_image_id) $attachmentIds->push($category->category_image_id);
            if ($category->category_icon_id) $attachmentIds->push($category->category_icon_id);
        }

        $uniqueAttachmentIds = $attachmentIds->unique()->filter();
        $attachmentCount = $uniqueAttachmentIds->count();

        if ($dryRun) {
            $this->warn("[DRY RUN] Would delete $categoryCount categories and $attachmentCount attachments/files.");
            return;
        }

        $this->info("Deleting $categoryCount categories and $attachmentCount attachments...");

        $bar = $this->output->createProgressBar($attachmentCount + 1);
        $bar->start();

        // 2. Delete Attachments (and physical files)
        foreach ($uniqueAttachmentIds->chunk(100) as $chunk) {
            $attachments = Attachment::whereIn('id', $chunk)->get();
            foreach ($attachments as $attachment) {
                $attachment->delete();
                $bar->advance();
            }
        }

        // 3. Clear Database Records
        Schema::disableForeignKeyConstraints();

        DB::table('product_categories')->truncate();
        DB::table('blog_categories')->truncate();
        DB::table('categories')->truncate();

        Schema::enableForeignKeyConstraints();

        $bar->finish();
        $this->newLine();
        $this->success("Categories cleanup completed successfully!");
    }

    private function success($message)
    {
        $this->output->writeln("<info>✔</info> $message");
    }
}
