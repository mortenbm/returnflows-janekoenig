<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([StoreScope::class])]
class Fulfillment extends Model
{
    use HasFactory;

    protected $table = 'order_fulfillments';

    protected $fillable = [
        'store_id',
        'order_id',
        'shopify_id',
        'title',
        'status',
        'delivery_at',
        'estimated_delivery_at',
        'created_at',
        'updated_at',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FulfillmentItem::class, 'fulfillment_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_delivery_at' => 'datetime',
        ];
    }
}
