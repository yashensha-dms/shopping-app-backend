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
        Schema::create('trending_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Data Backfill: Migrate currently trending products to the new table
        if (Schema::hasColumn('products', 'is_trending')) {
            $existingTrending = DB::table('products')->where('is_trending', 1)->orderBy('id')->get();
            foreach ($existingTrending as $index => $product) {
                DB::table('trending_products')->insert([
                    'product_id' => $product->id,
                    'order'      => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Remove the deprecated column from products table
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('is_trending');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('products', 'is_trending')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('is_trending')->default(0);
            });

            $trending = DB::table('trending_products')->pluck('product_id');
            DB::table('products')->whereIn('id', $trending)->update(['is_trending' => 1]);
        }

        Schema::dropIfExists('trending_products');
    }
};
