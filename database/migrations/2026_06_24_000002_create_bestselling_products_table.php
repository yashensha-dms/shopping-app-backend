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
        // 1. Add the bestselling column to products table first (as requested)
        Schema::table('products', function (Blueprint $table) {
            $table->integer('bestselling')->default(0)->nullable();
        });

        // 2. Create the bestselling_products table
        Schema::create('bestselling_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // 3. Drop the column from products table to follow the clean reordering design
        if (Schema::hasColumn('products', 'bestselling')) {
            $existingBestselling = DB::table('products')->where('bestselling', 1)->orderBy('id')->get();
            foreach ($existingBestselling as $index => $product) {
                DB::table('bestselling_products')->insert([
                    'product_id' => $product->id,
                    'order'      => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('bestselling');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('products', 'bestselling')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('bestselling')->default(0)->nullable();
            });

            $bestselling = DB::table('bestselling_products')->pluck('product_id');
            DB::table('products')->whereIn('id', $bestselling)->update(['bestselling' => 1]);
        }

        Schema::dropIfExists('bestselling_products');
    }
};
