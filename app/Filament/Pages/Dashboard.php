<?php

namespace App\Filament\Pages;

use App\Models\Transaction;
use App\Models\Menu;
use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use Carbon\Carbon;

class Dashboard extends Page
{
    
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
    protected string $view = 'filament.pages.dashboard';

    public function getViewData(): array
    {
        return [
            'todayStats' => $this->getTodayStats(),
            'dailySales' => $this->getDailySales(),
            'paymentMethodStats' => $this->getPaymentMethodStats(),
            'topProducts' => $this->getTopProducts(),
            'recentTransactions' => $this->getRecentTransactions(),
        ];
    }

    protected function getTodayStats()
    {
        $today = now()->today();
        
        return [
            'total_sales' => Transaction::whereDate('transaction_date', $today)
                ->where('status', 'completed')
                ->sum('final_amount'),
            'transaction_count' => Transaction::whereDate('transaction_date', $today)
                ->where('status', 'completed')
                ->count(),
            'average_sale' => Transaction::whereDate('transaction_date', $today)
                ->where('status', 'completed')
                ->avg('final_amount') ?? 0,
            'total_discount' => Transaction::whereDate('transaction_date', $today)
                ->where('status', 'completed')
                ->sum('discount_value'),
        ];
    }

    protected function getDailySales()
    {
        return Transaction::where('status', 'completed')
            ->whereBetween('transaction_date', [now()->subDays(30), now()])
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(final_amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    protected function getPaymentMethodStats()
    {
        return Payment::select(
            'payments.payment_method',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(payments.amount) as total_amount')
        )
            ->join('transactions', 'payments.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->whereDate('transactions.transaction_date', now()->today())
            ->groupBy('payments.payment_method')
            ->orderByDesc('total_amount')
            ->get();
    }

    protected function getTopProducts()
    {
        return DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('menus', 'transaction_details.menu_id', '=', 'menus.id')
            ->where('transactions.status', 'completed')
            ->whereDate('transactions.transaction_date', now()->today())
            ->select(
                'menus.name',
                DB::raw('SUM(transaction_details.quantity) as total_quantity'),
                DB::raw('SUM(transaction_details.subtotal) as total_sales')
            )
            ->groupBy('menus.id', 'menus.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();
    }

    protected function getRecentTransactions()
    {
        return Transaction::with(['customer', 'payments'])
            ->where('status', 'completed')
            ->latest('transaction_date')
            ->limit(5)
            ->get();
    }
}
