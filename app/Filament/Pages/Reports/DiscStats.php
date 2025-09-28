<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Models\Transaction;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DiscStats extends Page
{
    protected static ?string $navigationLabel = 'Discounts';
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.reports.disc-stats';

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

    public function applyQuickDateFilter(): void
    {
        switch ($this->quickDateFilter) {
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
                // Do not change dateRange for custom
                break;
        }
    }    


    public function getViewData(): array
    {
        return [
            'discountStats' => $this->getDiscountStats(),
            'dateRange'     => $this->dateRange,
        ];
    }

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
}

