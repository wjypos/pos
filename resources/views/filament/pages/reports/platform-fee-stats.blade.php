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

        {{-- Platform Fee Statistics ------------------------------------ --}}
        <section class="space-y-4">
            <h2 class="text-lg font-semibold">Platform Fee Statistics</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse ($items as $stat)
                    @php
                        // Hide Cash-fixed column
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


                        $ringClass = $stat->isGrand
                            ? 'ring-2 ring-indigo-600'
                            : ($stat->isSummaryMarket ? 'ring-2 ring-emerald-500' : '');
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

                        {{-- Header ------------------------------------ --}}
                        <p class="text-sm text-gray-500 mb-1 truncate">
                            {{ ucfirst($stat->payment_method) }}
                            @if ($stat->isGrand)
                                <span class="font-medium">(All Markets)</span>
                            @elseif ($stat->isSummaryMarket)
                                <span class="font-medium">(Market Total)</span>
                            @elseif ($stat->showPct)
                                — Percentage
                            @else
                                — Fixed
                            @endif
                        </p>

                        {{-- Main figure -------------------------------- --}}
                        <p class="text-3xl font-bold text-gray-900">
                            Rp {{ number_format($stat->total_fee_rp) }}
                        </p>

                        {{-- Secondary data ---------------------------- --}}
                        <div class="mt-2 text-sm text-gray-600 space-y-0.5">
                            @if ($stat->showPct)
                                <div>Avg Rate: {{ number_format($stat->avg_percent, 2) }}%</div>
                            @endif
                            <div>Avg Rp/Txn: Rp {{ number_format($stat->avg_fee_rp) }}</div>
                            <div>Transactions: {{ $stat->transaction_count }}</div>
                            <div>Total Sales: Rp {{ number_format($stat->total_amount) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-sm text-gray-500">
                        No data for selected range.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament::page>

