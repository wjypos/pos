<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExtraToping extends Model
{
    protected $fillable = [
        'name',
        'price_offline',
        'price_online',
        'description',
    ];

    protected $casts = [
        'price_offline' => 'decimal:2',
        'price_online' => 'decimal:2',
    ];

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_extra_toping')
            ->withPivot('price')
            ->withTimestamps();
    }

    public function getPriceByCustomerType($customerType)
    {
        return match ($customerType) {
            'dine-in', 'delivery' => $this->price_offline,
            'gofood', 'grabfood' => $this->price_online,
            default => $this->price_offline,
        };
    }

    /**
     * Get the price to display in the cart, preferring pivot price if available.
     */
    public function getCartDisplayPriceAttribute()
    {
        // If loaded via pivot (e.g., from a transaction/cart), use that price
        if ($this->pivot && isset($this->pivot->price)) {
            return $this->pivot->price;
        }
        // Fallback to offline price
        return $this->price_offline;
    }

    /**
     * Get the display name for cart (can be extended for more info).
     */
    public function getCartDisplayNameAttribute()
    {
        return $this->name;
    }
}
