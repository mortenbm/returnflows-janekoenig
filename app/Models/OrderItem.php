<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shopify_id',
        'sku',
        'title',
        'quantity',
        'price',
        'tax_amount',
        'tax_rate',
        'discount',
        'total',
    ];

    public function getcurrencyAttribute(): string
    {
       return  $this->order->currency;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
