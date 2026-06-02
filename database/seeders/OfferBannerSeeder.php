<?php

namespace Database\Seeders;

use App\Models\OfferBanner;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attachment;
use Illuminate\Database\Seeder;

class OfferBannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Try to get some sample attachments, products, and categories
        $attachment = Attachment::first();
        $product = Product::first();
        $category = Category::whereNotNull('parent_id')->first() ?: Category::first();

        // Create sample offer banners
        OfferBanner::updateOrCreate(
            ['name' => 'Summer Sale Banner'],
            [
                'banner_image_id' => $attachment ? $attachment->id : null,
                'redirect_type' => 'product',
                'redirect_id' => $product ? $product->id : 1,
                'status' => 1
            ]
        );

        OfferBanner::updateOrCreate(
            ['name' => 'New Arrivals Banner'],
            [
                'banner_image_id' => $attachment ? $attachment->id : null,
                'redirect_type' => 'category',
                'redirect_id' => $category ? $category->id : 1,
                'status' => 1
            ]
        );
    }
}
