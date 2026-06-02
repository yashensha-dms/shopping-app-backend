<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('offer_banners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('banner_image_id')->nullable();
            $table->enum('redirect_type', ['product', 'category'])->default('product');
            $table->unsignedBigInteger('redirect_id')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('banner_image_id')->references('id')->on('attachments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_banners');
    }
};
