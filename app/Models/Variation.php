<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Variation extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The variations that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'price',
        'sku',
        'status',
        'quantity',
        'discount',
        'sale_price',
        'product_id',
        'stock_status',
        'attribute_value_id',
        'variation_image_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'float',
        'sale_price' => 'float',
        'discount' => 'float',
        'product_id' => 'integer',
        'status' => 'integer',
        'attribute_value_id' => 'integer',
        'variation_image_id' => 'integer',
    ];

    protected $with = [
        'variation_image',
        'attribute_values'
    ];

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('tax')->id;
    }

    /**
     * @return BelongsTo
     */
    public function variation_image(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'variation_image_id');
    }

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * @return BelongsToMany
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_products');
    }

    /**
     * @return HasMany
     */
    public function cart(): HasMany
    {
        return $this->hasMany(Cart::class, 'variation_id');
    }

    /**
     * @return BelongsToMany
     */
    public function attribute_values(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'variation_attribute_values');
    }
}
