<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferBanner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'banner_image_id',
        'redirect_type',
        'redirect_id',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
        'banner_image_id' => 'integer',
        'redirect_id' => 'integer',
    ];

    protected $with = ['banner_image'];

    public function banner_image()
    {
        return $this->belongsTo(Attachment::class, 'banner_image_id');
    }

    public function redirect_product()
    {
        return $this->belongsTo(Product::class, 'redirect_id');
    }

    public function redirect_category()
    {
        return $this->belongsTo(Category::class, 'redirect_id');
    }
}
