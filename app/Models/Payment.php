<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    
    protected $fillable = [
        'payment_method',
        'customer_type'
    ];

    public static function getAllowedPaymentMethods($customerType)
    {
        $methods = [
            'gofood' => ['Gopay'],
            'grabfood' => ['Grabpay'],
            'dine-in' => ['cash', 'Qris', 'transfer'],
            'delivery' => ['cash', 'Qris', 'transfer']
        ];

        return $methods[$customerType] ?? [];
    }

    public static function canUseSplitPayment($customerType)
    {
        return in_array($customerType, ['delivery', 'dine-in']);
    }

    public static function validateSplitPayments($splitPayments, $totalAmount, $customerType)
    {
        if (!self::canUseSplitPayment($customerType)) {
            return false;
        }

        if (empty($splitPayments)) {
            return false;
        }

        $totalSplitAmount = 0;
        foreach ($splitPayments as $payment) {
            if (!isset($payment['method']) || !isset($payment['amount'])) {
                return false;
            }
            $totalSplitAmount += $payment['amount'];
        }

        return $totalSplitAmount === $totalAmount;
    }
}