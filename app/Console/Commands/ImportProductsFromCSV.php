<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProductsFromCSV extends Command
{
    protected $signature = 'app:import-products-csv {file}';
    protected $description = 'Import products from CSV (Fast version)';

    public function handle()
    {
        $filePath = $this->argument('file');
        if (!file_exists($filePath)) {
            $this->error("File not found");
            return;
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);
        $map = array_flip($headers);

        $this->info("Starting fast import...");
        
        $count = 0;
        while (($row = fgetcsv($file)) !== false) {
            $name = $row[$map['Name']] ?? null;
            if (!$name) continue;

            // 1. Category (Raw DB for speed)
            $categoryName = $row[$map['Category']] ?? 'Uncategorized';
            $category = DB::table('categories')->where('name', $categoryName)->first();
            if (!$category) {
                $categoryId = DB::table('categories')->insertGetId([
                    'name' => $categoryName,
                    'slug' => Str::slug($categoryName),
                    'type' => 'product',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $categoryId = $category->id;
            }

            // 2. Attachment (Raw DB to bypass Spatie conversions)
            $imageUrl = $row[$map['STC']] ?? null;
            $attachmentId = null;
            if ($imageUrl) {
                $existingAttachment = DB::table('attachments')
                    ->where('name', $name)
                    ->where('disk', 'external')
                    ->first();

                if (!$existingAttachment) {
                    $attachmentId = DB::table('attachments')->insertGetId([
                        'name' => $name,
                        'file_name' => Str::slug($name) . '.png',
                        'mime_type' => 'image/png',
                        'disk' => 'external',
                        'collection_name' => 'attachment',
                        'size' => 0,
                        'custom_properties' => json_encode(['external_url' => $imageUrl]),
                        'model_type' => 'App\Models\Product',
                        'created_by_id' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    $attachmentId = $existingAttachment->id;
                }
            }

            // 3. Product (Using model for Sluggable support, but we could bypass if needed)
            $product = Product::updateOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'short_description' => $row[$map['Name-Malayalam']] ?? '',
                    'description' => $row[$map['Name']] . ' (' . ($row[$map['Name-Malayalam']] ?? '') . ')',
                    'type' => 'simple',
                    'unit' => '1',
                    'quantity' => 100,
                    'price' => 0,
                    'sale_price' => 0,
                    'stock_status' => 'in_stock',
                    'product_thumbnail_id' => $attachmentId,
                    'status' => 1,
                    'is_approved' => 1,
                    'store_id' => 1,
                    'sku' => $row[$map['Product ID']] ?? Str::random(10),
                ]
            );
            
            // Sync categories
            DB::table('product_categories')->updateOrInsert(
                ['product_id' => $product->id, 'category_id' => $categoryId]
            );

            $count++;
            if ($count % 10 == 0) {
                $this->line("Imported $count products...");
            }
        }
        fclose($file);
        $this->info("Import complete. Total: $count");
    }
}
