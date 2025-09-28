<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'location',
        'is_default',
        'status'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // When setting a printer as default, unset others
        static::saving(function ($printer) {
            if ($printer->is_default) {
                static::where('id', '!=', $printer->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
