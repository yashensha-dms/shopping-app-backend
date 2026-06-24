<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('featured_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Data Backfill: Migrate currently featured products to the new table
        if (Schema::hasColumn('products', 'is_featured')) {
            $existingFeatured = DB::table('products')->where('is_featured', 1)->orderBy('id')->get();
            foreach ($existingFeatured as $index => $product) {
                DB::table('featured_products')->insert([
                    'product_id' => $product->id,
                    'order'      => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Remove the deprecated column from products table
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('is_featured');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('products', 'is_featured')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('is_featured')->default(0);
            });

            $featured = DB::table('featured_products')->pluck('product_id');
            DB::table('products')->whereIn('id', $featured)->update(['is_featured' => 1]);
        }

        Schema::dropIfExists('featured_products');
    }
};
