<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'price_offline',
        'price_gofood',
        'price_grabfood',
        'category_id', // Ensure category_id is in fillable
        'image',
    ];

    protected $casts = [
        'category_id' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getPriceByCustomerType($customerType)
    {
        return match ($customerType) {
            'dine-in' => $this->price_offline,
            'delivery' => $this->price_offline,
            'gofood' => $this->price_gofood,
            'grabfood' => $this->price_grabfood,
            default => $this->price_offline, // Fallback
        };
    }

    public function extraTopings(): BelongsToMany
    {
        return $this->belongsToMany(ExtraToping::class, 'menu_extra_toping')
            ->withPivot('price')
            ->withTimestamps();
    }
}
