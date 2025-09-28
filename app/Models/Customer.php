<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'id',
        'name',
        'customer_type',
    ];

    public function getCustomerTypeAttribute($value)
    {
        return strtolower($value);
    }

    public function setCustomerTypeAttribute($value)
    {
        $this->attributes['customer_type'] = strtolower($value);
    }
}
