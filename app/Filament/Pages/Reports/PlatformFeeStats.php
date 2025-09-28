<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlatformFeeStats extends Page
{
    protected static ?string $navigationLabel = 'Platform Fee Stats';
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMinusCircle;
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.reports.platform-fee-stats';

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
            'items' => $this->getPlatformFeeStats(),
            'dateRange' => $this->dateRange,
        ];
    }

    protected function getPlatformFeeStats(): Collection
    {
        $tx = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [
                $this->dateRange['from'].' 00:00:00',
                $this->dateRange['until'].' 23:59:59',
            ]);

        // 1️⃣ Detail rows: payment‑method × fee‑type
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

        // 2️⃣ Grand total across all marketplaces
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

        return $detail
            ->push($grand)
            ->map(function ($row) {
                $row->isGrand  = $row->platform_fee_type === 'grand';
                $row->showPct  = $row->platform_fee_type === 'percentage';
                return $row;
            });
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
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

