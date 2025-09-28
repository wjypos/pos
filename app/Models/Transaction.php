<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_identifier',
        'customer_id',
        'user_id',
        'total_amount',
        'discount_value',
        'discount_type',
        'platform_fee',
        'platform_fee_type',
        'final_amount',
        'payment_method',
        'status',
        'deleted_at',
        'deleted_by',
        'transaction_date',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'transaction_date'
    ];

    public function menu()
    {
        return $this->belongsToMany(Menu::class, 'transaction_details')
            ->withPivot(['quantity', 'price', 'subtotal']);
    }
    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function calculateFinalAmount()
    {
        return $this->total_amount - ($this->discount_value ?? 0) - ($this->platform_fee ?? 0);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public static function getTemporaryTransactions()
    {
        $tempTransactions = [];
        foreach (session()->all() as $key => $value) {
            if (strpos($key, 'temp_transaction_') === 0) {
                $tempTransactions[] = (object)[
                    'transaction_identifier' => str_replace('temp_transaction_', '', $key),
                    'customer_id' => $value['customer_id'],
                    'total_amount' => $value['total_amount'],
                    'final_amount' => $value['final_amount'],
                    'status' => 'temporary',
                    'transaction_date' => $value['created_at'],
                    'is_temporary' => true
                ];
            }
        }
        return collect($tempTransactions);
    }

    public function scopeWithTemporary($query)
    {
        return $query;  // Return the query builder instead of executing it
    }

    public static function mergeTemporaryTransactions($results)
    {
        $tempTransactions = self::getTemporaryTransactions();
        return $results->concat($tempTransactions);
    }
}