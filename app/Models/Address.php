<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $table = 'order_addresses';

    protected $fillable = [
        'order_id',
        'type',
        'first_name',
        'last_name',
        'address',
        'phone',
        'city',
        'province',
        'zip',
        'country',
        'company'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
