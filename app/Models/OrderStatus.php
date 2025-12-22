<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderStatus extends Model
{
    use Sluggable, HasFactory;

    protected $table = 'order_status';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'status',
        'sequence',
        'created_by_id',
        'system_reserve',
    ];

    protected $casts = [
        'status' => 'integer',
        'sequence' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->created_by_id = Helpers::getCurrentUserId() ?? User::role(RoleEnum::ADMIN)->first()?->id;
        });
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => true,
            ]
        ];
    }

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('orderStatus')->id;
    }

    /**
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
