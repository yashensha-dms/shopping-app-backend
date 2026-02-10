<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderProductPivot extends Pivot
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_products';

    /**
     * The Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'variation_id',
        'quantity',
        'single_price',
        'shipping_cost',
        'refund_status',
        'subtotal'
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'variation_id' => 'integer',
        'quantity' => 'integer',
        'single_price' => 'integer',
        'shipping_cost' => 'integer',
        'subtotal' => 'float',
    ];
}
