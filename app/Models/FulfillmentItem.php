<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FulfillmentItem extends Model
{
    use HasFactory;

    protected $table = 'order_fulfillment_items';

    protected $fillable = [
        'fulfillment_id',
        'shopify_id',
        'sku',
        'title',
        'quantity',
        'price',
        'tax_amount',
        'tax_rate',
        'discount',
        'total',
        'created_at',
        'updated_at',
    ];

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(Fulfillment::class, 'fulfillment_id');
    }
}
