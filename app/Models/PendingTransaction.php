<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingTransaction extends Model
{
    protected $fillable = [
        'transaction_identifier',
        'customer_id',
        'user_id',
        'total_amount',
        'discount_value',
        'platform_fee',
        'final_amount',
        'payment_method',
        'cart_data',
    ];

    protected $casts = [
        'cart_data' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
