<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturnItem extends Model
{
    use HasFactory;

    protected $table = 'order_return_items';

    protected $fillable = [
        'return_id',
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
       return  $this->orderReturn->order->currency;
    }

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class, 'return_id');
    }
}
