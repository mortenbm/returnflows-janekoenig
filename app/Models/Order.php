<?php

namespace App\Models;

use App\Enums\AddressTypeEnum;
use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([StoreScope::class])]
class Order extends Model
{
    use HasFactory;

    protected $casts = [
        'tags' => 'array',
    ];

    protected $fillable = [
        'store_id',
        'shopify_id',
        'name',
        'email',
        'status',
        'currency',
        'subtotal',
        'discount',
        'total',
        'shipping_amount',
        'tags',
        'risk_level',
        'client_ip',
        'customer_note',
        'created_at',
        'updated_at',
        'bc_id',
        'shipping_title',
        'shipping_code',
        'shipping_source'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function shippingAddress(): HasMany
    {
        return $this->addresses()->where('type', AddressTypeEnum::SHIPPING->value);
    }

    public function billingAddress(): HasMany
    {
        return $this->addresses()->where('type', AddressTypeEnum::BILLING->value);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
