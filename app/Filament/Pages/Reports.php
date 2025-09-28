<?php

namespace App\Filament\Pages;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Category;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class Reports extends Page
{
    
    protected static ?string $navigationLabel = 'Reports';
    protected static string|UnitEnum|null $navigationGroup = 'POS';
    protected static ?int $navigationSort   = 4;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected string $view = 'filament.pages.reports';

    /* ---------------------------------------------------------------------
     | State
     |---------------------------------------------------------------------*/
    public array $dateRange = [
        'from'  => null,
        'until' => null,
        'hour_start' => null, // Add this
        'hour_end'   => null, // Add this
    ];

    public string $quickDateFilter = 'day'; // Change default from 'custom' to 'day'

    /* ---------------------------------------------------------------------
     | Lifecycle
     |---------------------------------------------------------------------*/
    public function mount(): void
    {
        $this->quickDateFilter = request()->input('quick', 'day'); // Change default from 'custom' to 'day'
        $this->applyQuickDateFilter();
    }

    public function updated($property): void
    {
        if ($property === 'quickDateFilter') {
            $this->applyQuickDateFilter();
            $this->refreshReportData();
        }
        if (in_array($property, ['dateRange.from', 'dateRange.until'], true)) {
            $this->quickDateFilter = 'custom';
            $this->refreshReportData();
        }
    }

    public function refreshReportData(): void
    {
        // Livewire will re-render automatically; hook reserved for side‑effects if needed.
    }

    public function getPaymentSummary(): array
    {
        $query = Transaction::query()
            ->with('payments')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [
                $this->dateRange['from'].' 00:00:00',
                $this->dateRange['until'].' 23:59:59',
            ]);

        // Add hour filter if set
        if (!empty($this->dateRange['hour_start']) && !empty($this->dateRange['hour_end'])) {
            $start = (int)$this->dateRange['hour_start'];
            $end = (int)$this->dateRange['hour_end'];
            
            if ($start > $end) {
                // Handle overnight shifts (e.g. 22:00 - 06:00)
                $query->where(function($q) use ($start, $end) {
                    $q->whereRaw('HOUR(transaction_date) >= ?', [$start])
                      ->orWhereRaw('HOUR(transaction_date) <= ?', [$end]);
                });
            } else {
                // Normal hour range
                $query->whereRaw('HOUR(transaction_date) BETWEEN ? AND ?', [$start, $end]);
            }
        }

        $transactions = $query->get();

        $paymentTotals = [
            'cash'     => 0,
            'qris'     => 0,
            'transfer' => 0,
            'gopay'    => 0,
            'grabpay'  => 0,
        ];

        $transactionCount = 0;

        foreach ($transactions as $transaction) {
            $transactionCount++;

            if ($transaction->payment_method === 'split' && $transaction->payments->isNotEmpty()) {
                // Handle split payments by adding to respective payment method totals
                foreach ($transaction->payments as $payment) {
                    $method = strtolower($payment->payment_method);
                    $paymentTotals[$method] = ($paymentTotals[$method] ?? 0) + $payment->amount;
                }
            } else {
                // Handle regular single payment method
                $method = strtolower($transaction->payment_method);
                $paymentTotals[$method] = ($paymentTotals[$method] ?? 0) + $transaction->final_amount;
            }
        }

        $paymentTotals = array_filter($paymentTotals, static fn ($total) => $total > 0);

        return [
            'totals'           => $paymentTotals,
            'grandTotal'       => array_sum($paymentTotals),
            'transactionCount' => $transactionCount,
        ];
    }

    /* ---------------------------------------------------------------------
     | Top selling items
     |---------------------------------------------------------------------*/    
    protected function getTopItems()
    {
        return TransactionDetail::select(
            'menu_id',
            'menus.name as menu_name',
            'categories.name as category_name',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(subtotal) as total_sales'),
            DB::raw('COUNT(DISTINCT transactions.id) as transaction_count')
        )
            ->join('menus', 'transaction_details.menu_id', '=', 'menus.id')
            ->join('categories', 'menus.category_id', '=', 'categories.id')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.transaction_date', [
                $this->dateRange['from'] . ' 00:00:00',
                $this->dateRange['until'] . ' 23:59:59',
            ])
            ->groupBy('menu_id', 'menus.name', 'categories.name')
            ->orderByDesc('total_quantity')
            ->limit(20)
            ->get();
    }

    /* ---------------------------------------------------------------------
     | Category sales
     |---------------------------------------------------------------------*/
    protected function getCategorySales(): Collection
    {
        return Category::select(
            'categories.id',
            'categories.name',
            DB::raw('COUNT(DISTINCT transaction_details.menu_id)  as unique_items'),
            DB::raw('SUM(transaction_details.quantity)            as total_quantity'),
            DB::raw('SUM(transaction_details.subtotal)            as total_sales'),
            DB::raw('COUNT(DISTINCT transactions.id)              as transaction_count')
        )
            ->leftJoin('menus', 'categories.id', '=', 'menus.category_id')
            ->leftJoin('transaction_details', 'menus.id', '=', 'transaction_details.menu_id')
            ->leftJoin('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.transaction_date', [
                $this->dateRange['from'].' 00:00:00',
                $this->dateRange['until'].' 23:59:59',
            ])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_sales')
            ->get();
    }

    /* ---------------------------------------------------------------------
     | User stats
     |---------------------------------------------------------------------*/
    protected function getUserStats(): Collection
    {
        return Transaction::select(
            'users.name',
            DB::raw('COUNT(*)             as transaction_count'),
            DB::raw('SUM(final_amount)    as total_amount'),
            DB::raw('SUM(platform_fee)    as total_platform_fee'),
            DB::raw('SUM(discount_value)  as total_discount')
        )
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transaction_date', [
                $this->dateRange['from'].' 00:00:00',
                $this->dateRange['until'].' 23:59:59',
            ])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('transaction_count')
            ->get();
    }

    /* ---------------------------------------------------------------------
     | Discount stats
     |---------------------------------------------------------------------*/
    protected function getDiscountStats()
    {
        return Transaction::select(
            DB::raw('DATE(transaction_date) as date'),
            'discount_type',
            DB::raw('COUNT(*) as transaction_count'),
            DB::raw('SUM(total_amount) as total_before_discount'),
            DB::raw('SUM(discount_value) as total_discount'),
            DB::raw('SUM(final_amount) as total_after_discount')
        )
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [
                $this->dateRange['from'] . ' 00:00:00',
                $this->dateRange['until'] . ' 23:59:59',
            ])
            ->whereNotNull('discount_value')
            ->where('discount_value', '>', 0)
            ->groupBy('date', 'discount_type')
            ->orderBy('date', 'desc')
            ->get();
    }

    /* ---------------------------------------------------------------------
     | Platform‑fee stats (detail ➜ marketplace total ➜ grand total)
     |---------------------------------------------------------------------*/
    protected function getPlatformFeeStats(): Collection
    {
        // Base query filtered by date & status
        $tx = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [
                $this->dateRange['from'].' 00:00:00',
                $this->dateRange['until'].' 23:59:59',
            ]);

        /* 1️⃣ Detail rows: payment‑method × fee‑type */
        $detail = (clone $tx)->selectRaw('
                payment_method,
                platform_fee_type,
                COUNT(*) AS transaction_count,
                SUM(CASE WHEN platform_fee_type = "fixed"
                         THEN platform_fee
                         ELSE (platform_fee / 100) * total_amount END)      AS total_fee_rp,
                AVG(CASE WHEN platform_fee_type = "fixed"
                         THEN platform_fee
                         ELSE (platform_fee / 100) * total_amount END)      AS avg_fee_rp,
                AVG(CASE WHEN platform_fee_type = "percentage"
                         THEN platform_fee END)                              AS avg_percent,
                SUM(total_amount)                                            AS total_amount')
            ->groupBy('payment_method', 'platform_fee_type')
            ->orderBy('payment_method')
            ->get();

        /* 2️⃣ Grand total across all marketplaces */
        $grand = (clone $tx)->selectRaw('
                "All"  AS payment_method,
                "grand" AS platform_fee_type,
                COUNT(*) AS transaction_count,
                SUM(CASE WHEN platform_fee_type = "fixed"
                         THEN platform_fee
                         ELSE (platform_fee / 100) * total_amount END)      AS total_fee_rp,
                AVG(CASE WHEN platform_fee_type = "fixed"
                         THEN platform_fee
                         ELSE (platform_fee / 100) * total_amount END)      AS avg_fee_rp,
                NULL  AS avg_percent,
                SUM(total_amount)                                            AS total_amount')
            ->first();

        /* 3️⃣ Merge detail + grand and decorate */
        return $detail
            ->push($grand)
            ->map(function ($row) {
                $row->isGrand  = $row->platform_fee_type === 'grand';
                $row->showPct  = $row->platform_fee_type === 'percentage';
                return $row;
            });
    }

    public function previousDay()
    {
        if ($this->quickDateFilter === 'day') {
            $from = Carbon::parse($this->dateRange['from'])->subDay();
            $this->dateRange['from'] = $from->format('Y-m-d');
            $this->dateRange['until'] = $from->format('Y-m-d');
            $this->refreshReportData();
        }
    }

    public function nextDay()
    {
        if ($this->quickDateFilter === 'day') {
            $from = Carbon::parse($this->dateRange['from'])->addDay();
            $this->dateRange['from'] = $from->format('Y-m-d');
            $this->dateRange['until'] = $from->format('Y-m-d');
            $this->refreshReportData();
        }
    }

    public function previousWeek()
    {
        if ($this->quickDateFilter === 'week') {
            $from = Carbon::parse($this->dateRange['from'])->subWeek()->startOfWeek();
            $until = $from->copy()->endOfWeek();
            $this->dateRange['from'] = $from->format('Y-m-d');
            $this->dateRange['until'] = $until->format('Y-m-d');
            $this->refreshReportData();
        }
    }

    public function nextWeek()
    {
        if ($this->quickDateFilter === 'week') {
            $from = Carbon::parse($this->dateRange['from'])->addWeek()->startOfWeek();
            $until = $from->copy()->endOfWeek();
            $this->dateRange['from'] = $from->format('Y-m-d');
            $this->dateRange['until'] = $until->format('Y-m-d');
            $this->refreshReportData();
        }
    }

    public function previousMonth()
    {
        if ($this->quickDateFilter === 'month') {
            $from = Carbon::parse($this->dateRange['from'])->subMonthNoOverflow()->startOfMonth();
            $until = $from->copy()->endOfMonth();
            $this->dateRange['from'] = $from->format('Y-m-d');
            $this->dateRange['until'] = $until->format('Y-m-d');
            $this->refreshReportData();
        }
    }

    public function nextMonth()
    {
        if ($this->quickDateFilter === 'month') {
            $from = Carbon::parse($this->dateRange['from'])->addMonthNoOverflow()->startOfMonth();
            $until = $from->copy()->endOfMonth();
            $this->dateRange['from'] = $from->format('Y-m-d');
            $this->dateRange['until'] = $until->format('Y-m-d');
            $this->refreshReportData();
        }
    }

    public function previousYear()
    {
        if ($this->quickDateFilter === 'year') {
            $from = Carbon::parse($this->dateRange['from'])->subYear()->startOfYear();
            $until = $from->copy()->endOfYear();
            $this->dateRange['from'] = $from->format('Y-m-d');
            $this->dateRange['until'] = $until->format('Y-m-d');
            $this->refreshReportData();
        }
    }

    public function nextYear()
    {
        if ($this->quickDateFilter === 'year') {
            $from = Carbon::parse($this->dateRange['from'])->addYear()->startOfYear();
            $until = $from->copy()->endOfYear();
            $this->dateRange['from'] = $from->format('Y-m-d');
            $this->dateRange['until'] = $until->format('Y-m-d');
            $this->refreshReportData();
        }
    }

    public function applyQuickDateFilter(): void
    {
        // Add shift presets
        switch ($this->quickDateFilter) {
            case 'morning_shift':
                $this->dateRange = [
                    'from'       => now()->format('Y-m-d'),
                    'until'      => now()->format('Y-m-d'),
                    'hour_start' => 6,
                    'hour_end'   => 14,
                ];
                break;
            case 'evening_shift':
                $this->dateRange = [
                    'from'       => now()->format('Y-m-d'),
                    'until'      => now()->format('Y-m-d'),
                    'hour_start' => 14,
                    'hour_end'   => 22,
                ];
                break;
            case 'night_shift':
                $this->dateRange = [
                    'from'       => now()->format('Y-m-d'),
                    'until'      => now()->format('Y-m-d'),
                    'hour_start' => 22,
                    'hour_end'   => 6,
                ];
                break;
            case 'day':
                $this->dateRange = [
                    'from'  => now()->format('Y-m-d'),
                    'until' => now()->format('Y-m-d'),
                ];
                break;
            case 'yesterday':
                $this->dateRange = [
                    'from'  => now()->subDay()->format('Y-m-d'),
                    'until' => now()->subDay()->format('Y-m-d'),
                ];
                break;
            case 'week':
                $this->dateRange = [
                    'from'  => now()->startOfWeek()->format('Y-m-d'),
                    'until' => now()->endOfWeek()->format('Y-m-d'),
                ];
                break;
            case 'month':
                $this->dateRange = [
                    'from'  => now()->startOfMonth()->format('Y-m-d'),
                    'until' => now()->endOfMonth()->format('Y-m-d'),
                ];
                break;
            case 'year':
                $this->dateRange = [
                    'from'  => now()->startOfYear()->format('Y-m-d'),
                    'until' => now()->endOfYear()->format('Y-m-d'),
                ];
                break;
            default:
                $this->dateRange['hour_start'] = null;
                $this->dateRange['hour_end'] = null;
                break;
        }
    }

    /* ---------------------------------------------------------------------
     | View data
     |---------------------------------------------------------------------*/
    protected function getViewData(): array 
    {
        return [
            'summary'           => $this->getPaymentSummary(),
            'categoryStats'     => $this->getCategorySales(),
            'userStats'         => $this->getUserStats(),
            'discountStats'     => $this->getDiscountStats(),
            'platformFeeStats'  => $this->getPlatformFeeStats(),
            'items'             => $this->getTopItems(),
            'dateRange'         => $this->dateRange,
        ];
    }
}
