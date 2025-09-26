<?php

namespace App\Models;

use App\Enums\NewOrderSystemEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'is_active',
        'shopify_domain',
        'shopify_client_id',
        'shopify_client_secret',
        'shopify_access_token',
        'bc_sku',
        'new_order_system',
        'is_process_gift_cards',
        'order_origin',
        'is_process_new_orders'
    ];

    protected $casts = [
        'shopify_client_secret' => 'encrypted',
        'shopify_access_token' => 'encrypted',
        'new_order_system' => NewOrderSystemEnum::class
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
