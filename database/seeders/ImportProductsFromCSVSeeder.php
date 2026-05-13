<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportProductsFromCSVSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csvPath = base_path("product_set.csv");

        if (!file_exists($csvPath)) {
            echo "CSV file not found at: {$csvPath}\n";
            return;
        }

        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file);
        $map = array_flip($headers);

        echo "Starting Full Product Import from CSV...\n";
        
        $adminId = \App\Models\User::role('admin')->first()?->id ?? 1;

        $store = \App\Models\Store::first();
        if (!$store) {
            $vendorId = \App\Models\User::role('vendor')->first()?->id ?? $adminId;
            $store = \App\Models\Store::create([
                'store_name' => 'Main Store',
                'slug' => 'main-store',
                'status' => 1,
                'is_approved' => 1,
                'vendor_id' => $vendorId,
            ]);
        }
        $storeId = $store->id;

        $count = 0;
        while (($row = fgetcsv($file)) !== false) {
            $name = $row[$map['Name']] ?? null;
            if (!$name) continue;

            // 1. Handle Categories & Sub-categories
            $categoryName = $row[$map['Category']] ?? 'Uncategorized';
            $subCategoryName = $row[$map['Sub Category']] ?? null;

            $category = Category::updateOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName), 'type' => 'product', 'status' => 1]
            );

            $currentCategoryId = $category->id;
            if ($subCategoryName) {
                $subCategory = Category::updateOrCreate(
                    ['name' => $subCategoryName, 'parent_id' => $category->id],
                    ['slug' => Str::slug($subCategoryName), 'type' => 'product', 'status' => 1]
                );
                $currentCategoryId = $subCategory->id;
            }

            // 2. Handle Attachment (External Image URL)
            $imageUrl = $row[$map['STC']] ?? null;
            $attachmentId = null;
            if ($imageUrl) {
                $existingAttachment = DB::table('attachments')
                    ->where('custom_properties', 'like', '%' . $imageUrl . '%')
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
                        'created_by_id' => $adminId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    $attachmentId = $existingAttachment->id;
                }
            }

            // 3. Create/Update Product
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
                    'store_id' => $storeId,
                    'created_by_id' => $adminId,
                    'sku' => $row[$map['Product ID']] ?? Str::random(10),
                ]
            );

            // Link categories
            DB::table('product_categories')->updateOrInsert(
                ['product_id' => $product->id, 'category_id' => $currentCategoryId]
            );

            $count++;
            if ($count % 10 == 0) {
                echo "Imported {$count} items...\n";
            }
        }

        fclose($file);
        echo "Full Import Complete! Total items: {$count}\n";
    }
}
