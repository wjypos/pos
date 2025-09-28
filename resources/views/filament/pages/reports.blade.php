<x-filament::page>
    <div class="space-y-6">
        <!-- Date Filter -->
        <div class="bg-blue-100 rounded-lg border-[1.5px] border-danger-600 focus:border-danger-600 shadow-lg p-2 md:p-3 flex flex-col w-full ring-1 ring-blue-600">
            <div class="flex gap-1 mb-1">
                <select wire:model.live="quickDateFilter" class="form-select text-sm md:text-base bg-green-400 hover:bg-green-500 rounded-lg border-[1.5px] border-gray-500 text-white text-center p-0 gap-0 mb-0 font-semibold">
                    <optgroup label="Date Range">
                        <option value="day">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="week">Week</option>
                        <option value="month">Month</option>
                        <option value="year">Year</option>
                        <option value="custom">Custom</option>
                    </optgroup>
                    <optgroup label="Shifts">
                        <option value="morning_shift">Morning (10-4)</option>
                        <option value="evening_shift">Evening (4-10)</option>
                        <option value="night_shift">Night (10-6)</option>
                    </optgroup>
                </select>
                @if(
                    $quickDateFilter === 'day' || $quickDateFilter === 'week' || $quickDateFilter === 'month' || $quickDateFilter === 'year'
                )
                    <div class="mb-2">
                    @if($quickDateFilter === 'day')
                        <x-filament::button wire:click="previousDay" size="sm" color="primary" icon="heroicon-o-chevron-left" class="rounded-lg border-[1.5px] border-gray-500">
                            Prev
                        </x-filament::button>
                    </div> 
                    <div class="mb-2">                    
                        <x-filament::button wire:click="nextDay" size="sm" color="danger" class="rounded-lg border-[1.5px] border-gray-500">
                            Next
                            <x-heroicon-o-chevron-right class="w-5 h-5 inline-block ml-1" />
                        </x-filament::button>
                    </div> 

                    <div class="mb-2">  
                    @elseif($quickDateFilter === 'week')
                        <x-filament::button wire:click="previousWeek" size="sm" color="primary" icon="heroicon-o-chevron-left" class="rounded-lg border-[1.5px] border-gray-500">
                            Prev
                        </x-filament::button>
                    </div> 
                    <div class="mb-2">
                        <x-filament::button wire:click="nextWeek" size="sm" color="danger" class="rounded-lg border-[1.5px] border-gray-500">
                            Next
                            <x-heroicon-o-chevron-right class="w-5 h-5 inline-block ml-1" />
                        </x-filament::button>
                    </div>

                    <div class="mb-2">  
                    @elseif($quickDateFilter === 'month')
                        <x-filament::button wire:click="previousMonth" size="sm" color="primary" icon="heroicon-o-chevron-left" class="rounded-lg border-[1.5px] border-gray-500">
                            Prev
                        </x-filament::button>
                    </div>
                    <span class="w-full rounded-lg text-center border-[1.5px] border-gray-500 bg-gray-100 text-sm font-medium focus:border-gray-500">
                            {{ \Carbon\Carbon::parse($dateRange['from'])->format('M') }}
                    </span>
                    <div class="mb-2">
                        <x-filament::button wire:click="nextMonth" size="sm" color="danger" class="rounded-lg border-[1.5px] border-gray-500">
                            Next
                            <x-heroicon-o-chevron-right class="w-5 h-5 inline-block ml-1" />
                        </x-filament::button>
                    </div>  

                    <div class="mb-2">  
                    @elseif($quickDateFilter === 'year')
                        <x-filament::button wire:click="previousYear" size="sm" color="primary" icon="heroicon-o-chevron-left" class="rounded-lg border-[1.5px] border-gray-500">
                            Prev
                        </x-filament::button>
                    </div>
                    <div class="w-full rounded-lg text-center border-[1.5px] border-gray-500 bg-gray-100 text-sm font-semibold focus:border-gray-500">
                            {{ \Carbon\Carbon::parse($dateRange['from'])->format('M') }}
                    </div> 
                    <div class="mb-2">
                        <x-filament::button wire:click="nextYear" size="sm" color="danger" class="rounded-lg border-[1.5px] border-gray-500">
                            Next
                            <x-heroicon-o-chevron-right class="w-5 h-5 inline-block ml-1" />
                        </x-filament::button>
                    @endif
                    </div>        
                @endif
            </div>
           <form wire:submit.prevent="refreshReportData">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input 
                            type="date" 
                            wire:model="dateRange.from" 
                            class="block w-full rounded-lg shadow-sm bg-gray-100 border-[1.5px] border-gray-500 shadow overflow-hidden p-1 gap-1 mb-1 focus:border-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input 
                            type="date" 
                            wire:model="dateRange.until" 
                            class="block w-full rounded-lg shadow-sm bg-gray-100  border-[1.5px] border-gray-500 shadow overflow-hidden p-1 gap-1 mb-1 focus:border-gray-500">
                    </div>
                </div>
                <div class="mt-2">
                    @if($quickDateFilter === 'custom')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Hour (0-23)</label>
                            <input 
                                type="number" 
                                min="0" 
                                max="23"
                                wire:model="dateRange.hour_start" 
                                class="block w-full rounded-lg shadow-sm bg-gray-100 border-[1.5px] border-gray-500 shadow overflow-hidden p-1 gap-1 mb-1 focus:border-gray-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Hour (0-23)</label>
                            <input 
                                type="number"
                                min="0" 
                                max="23"
                                wire:model="dateRange.hour_end" 
                                class="block w-full rounded-lg shadow-sm bg-gray-100 border-[1.5px] border-gray-500 shadow overflow-hidden p-1 gap-1 mb-1 focus:border-gray-500">
                        </div>
                    </div>
                    <x-filament::button type="submit" color="info" class="rounded-lg border-[1.5px] border-gray-500">
                        Show Report
                    </x-filament::button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Payment Method Summary -->
        <div class="bg-white rounded-lg border-[1.5px] border-danger-600 focus:border-danger-600 shadow-lg p-2 md:p-3 flex flex-col w-full ring-1 ring-danger-600">
            <h3 class="text-lg font-medium mb-4">Payment Method Summary</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-4 mb-4">
                @foreach(['Cash', 'QRIS', 'Transfer', 'Gopay', 'Grabpay'] as $method)
                    @php
                        $amount = $summary['totals'][strtolower($method)] ?? 0;
                        // Assign ring color based on payment method
                        $ringClass = '';
                        switch (strtolower($method)) {
                            case 'cash':
                                $ringClass = 'ring-2 ring-gray-400';
                                break;
                            case 'qris':
                                $ringClass = 'ring-2 ring-pink-400';
                                break;
                            case 'transfer':
                                $ringClass = 'ring-2 ring-blue-400';
                                break;
                            case 'gopay':
                                $ringClass = 'ring-2 ring-cyan-400';
                                break;
                            case 'grabpay':
                                $ringClass = 'ring-2 ring-lime-500';
                                break;
                        }
                    @endphp
                    @if($amount > 0)
                        <div class="bg-yellow-50 border-[1.5px] border-yellow-500 p-3 rounded-lg focus:border-yellow-500 {{ $ringClass }}">
                            <div class="text-sm font-medium text-gray-600">{{ $method }}</div>
                            <div class="text-lg font-bold">Rp {{ number_format($amount, 0) }}</div>
                        </div>
                    @endif
                @endforeach
                {{-- Split Payment Summary --}}
                @php
                    $splitPaymentTotal = $summary['totals']['split'] ?? 0;
                    $transactionRingClass = 'ring-2 ring-yellow-600';
                @endphp
                @if($splitPaymentTotal > 0)
                    <div class="bg-yellow-50 border-[1.5px] border-yellow-500 p-3 rounded-lg focus:border-yellow-500 ring-2 ring-yellow-400">
                        <div class="text-sm font-bold text-yellow-700">Split Payment</div>
                        <div class="text-lg font-extrabold text-yellow-800">Rp {{ number_format($splitPaymentTotal, 0) }}</div>
                    </div>
                @endif
            </div>

        <!-- Summary Footer -->
        <div class="bg-orange-100 rounded-xl border-[1.5px] border-gray-500 shadow overflow-hidden p-4 gap-4 mb-4 focus:border-gray-500 ring-2 ring-orange-600">
            <div class="flex justify-between items-center text-sm text-gray-600 mb-2">
            <span>Total Transactions:</span>
            <span>{{ $summary['transactionCount'] }}</span>
            </div>
            <div class="flex justify-between items-center">
            <span class="text-lg font-medium text-gray-900">Grand Total:</span>
            <span class="text-xl font-bold text-primary-600">
                Rp {{ number_format($summary['grandTotal'], 0) }}
            </span>
            </div>
        </div>

        <!-- Top Menu Items -->
        <div class="bg-cyan-100 rounded-xl border-[1.5px] border-gray-500 shadow overflow-hidden p-4 gap-4 mb-4 focus:border-gray-500 ring-2 ring-cyan-400">
            <h2 class="text-lg font-medium mb-4">Top Selling Item</h2>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Rank</th>
                        <th class="px-4 py-3 text-left">Menu Item</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-center">Qty Sold</th>
                        <th class="px-4 py-3 text-right">Total Sales</th>
                        <th class="px-4 py-3 text-center">Transactions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($items as $index => $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">{{ $item->menu_name }}</td>
                            <td class="px-4 py-3">{{ $item->category_name }}</td>
                            <td class="px-4 py-3 text-center">{{ number_format($item->total_quantity, 0) }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($item->total_sales, 0) }}</td>
                            <td class="px-4 py-3 text-center">{{ $item->transaction_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Category Stats -->
        <div class="bg-yellow-100 rounded-xl border-[1.5px] border-gray-500 shadow overflow-hidden p-6 gap-4 mb-4 focus:border-gray-500 ring-2 ring-yellow-400">
            <h2 class="text-lg font-medium mb-4">Sales by Category</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Category</th>
                            <th class="text-right">Items Sold</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryStats as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td class="text-right">{{ $category->total_quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- User Stats -->        
        <div class="bg-purple-100 rounded-xl border-[1.5px] border-gray-500 shadow overflow-hidden p-6 gap-4 mb-4 focus:border-gray-500 ring-2 ring-purple-400">
            <h2 class="text-lg font-medium mb-4">Transactions by User</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left">User</th>
                            <th class="text-right">Transactions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($userStats as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td class="text-right">{{ $user->transaction_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Discount Statistics --}}
        <div class="bg-red-100 rounded-xl shadow overflow-hidden border-[1.5px] border-gray-800 focus:border-gray-800 ring-2 ring-red-400">
            <div class="p-4 border-b">
                <h3 class="text-lg font-medium">Discount Details</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-right">Before Discount</th>
                            <th class="px-4 py-3 text-right">Discount Amount</th>
                            <th class="px-4 py-3 text-right">After Discount</th>
                            <th class="px-4 py-3 text-center">Count</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($discountStats as $stat)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ \Carbon\Carbon::parse($stat->date)->format('d M Y') }}</td>
                                <td class="px-4 py-3">{{ ucfirst($stat->discount_type) }}</td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($stat->total_before_discount, 0) }}</td>
                                <td class="px-4 py-3 text-right text-red-600">
                                    Rp {{ number_format($stat->total_discount, 0) }}
                                </td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($stat->total_after_discount, 0) }}</td>
                                <td class="px-4 py-3 text-center">{{ $stat->transaction_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 font-medium">
                        <tr>
                            <td class="px-4 py-3" colspan="2">Totals</td>
                            <td class="px-4 py-3 text-right">
                                Rp {{ number_format($discountStats->sum('total_before_discount'), 0) }}
                            </td>
                            <td class="px-4 py-3 text-right text-red-600">
                                Rp {{ number_format($discountStats->sum('total_discount'), 0) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                Rp {{ number_format($discountStats->sum('total_after_discount'), 0) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ $discountStats->sum('transaction_count') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        {{-- Platform Fee Statistics --}}
        <section class="space-y-4">
            <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Platform Fee Statistics</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 ">
            
            @foreach ($platformFeeStats as $stat)
                @php
                    /// Hide Cash-fixed column
                        if (
                            isset($stat->payment_method, $stat->platform_fee_type)
                            && strtolower($stat->payment_method) === 'cash'
                            && $stat->platform_fee_type === 'fixed'
                        ) continue;

                        // Hide Qris-fixed column
                        if (
                            isset($stat->payment_method, $stat->platform_fee_type)
                            && strtolower($stat->payment_method) === 'qris'
                            && $stat->platform_fee_type === 'fixed'
                        ) continue;

                        // Hide Transfer-fixed column
                        if (
                            isset($stat->payment_method, $stat->platform_fee_type)
                            && strtolower($stat->payment_method) === 'transfer'
                            && $stat->platform_fee_type === 'fixed'
                        ) continue;

                        // Hide Split-fixed column
                        if (
                            isset($stat->payment_method, $stat->platform_fee_type)
                            && strtolower($stat->payment_method) === 'split'
                            && $stat->platform_fee_type === 'fixed'
                        ) continue;

                    // Ring color logic
                    $ringClass = '';
                    if ($stat->isGrand) {
                        $ringClass = 'ring-2 ring-indigo-600';
                    } elseif ($stat->isSummaryMarket) {
                        $ringClass = 'ring-2 ring-emerald-500';
                    } elseif (strtolower($stat->payment_method) === 'gopay') {
                        $ringClass = 'ring-2 ring-cyan-400';
                    } elseif (strtolower($stat->payment_method) === 'grabpay') {
                        $ringClass = 'ring-2 ring-lime-500';
                    }
                @endphp

                <div class="relative p-5 bg-gray-100 rounded-2xl shadow-sm border-[1.5px] border-gray-800 focus:border-gray-800 {{ $ringClass }}">
                    {{-- Badge: move to top-right --}}
                    <span
                        @class([
                            'absolute top-3 right-3 text-xs font-semibold px-2 py-0.5 rounded-full z-10',
                            'bg-indigo-600 text-white'               => $stat->isGrand,
                            'bg-emerald-500 text-white'              => $stat->isSummaryMarket,
                            'bg-amber-500/10 text-amber-700'         => !$stat->isGrand && !$stat->isSummaryMarket && $stat->showPct,
                            'bg-sky-500/10 text-sky-700'             => !$stat->isGrand && !$stat->isSummaryMarket && !$stat->showPct,
                        ])
                    >
                        @if ($stat->isGrand)
                            TOTAL
                        @elseif ($stat->isSummaryMarket)
                            SUM
                        @elseif ($stat->showPct)
                            %
                        @else
                            Rp
                        @endif
                    </span>

                    {{-- Header ------------------------------------------------ --}}
                    <p class="text-sm text-gray-500 mb-1 truncate">
                        {{ $stat->payment_method }}
                        @if ($stat->isGrand)
                            <span class="font-medium">(All Markets)</span>
                        @elseif ($stat->isSummaryMarket)
                            <span class="font-medium">(Market Total)</span>
                        @elseif ($stat->showPct)
                            – Percentage
                        @else
                            – Fixed
                        @endif
                    </p>

                    {{-- Main figure ------------------------------------------- --}}
                    <p class="text-3xl font-bold text-gray-900">
                        Rp {{ number_format($stat->total_fee_rp) }}
                    </p>

                    {{-- Secondary data ---------------------------------------- --}}
                    <div class="mt-2 text-sm text-gray-600 space-y-0.5">
                        @if ($stat->showPct)
                            <div>Avg Rate: {{ number_format($stat->avg_percent, 2) }}%</div>
                        @endif
                        <div>Avg Rp/Txn: Rp {{ number_format($stat->avg_fee_rp) }}</div>
                        <div>Transactions: {{ $stat->transaction_count }}</div>
                        <div>Total Sales: Rp {{ number_format($stat->total_amount) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
  </div>
</x-filament::page>
