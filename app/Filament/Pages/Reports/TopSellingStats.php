<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Models\TransactionDetail;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TopSellingStats extends Page
{
    protected static ?string $navigationLabel = 'Top Selling';
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.reports.top-selling-items';

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
            'items' => $this->getTopItems(),
            'dateRange' => $this->dateRange,
        ];
    }

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

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
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

