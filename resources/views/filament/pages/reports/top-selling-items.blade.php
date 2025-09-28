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

        <!-- Top Items Table -->
        <div class="bg-cyan-100 rounded-xl border-[1.5px] border-gray-500 shadow overflow-hidden p-4 gap-4 mb-4 focus:border-gray-500 ring-2 ring-cyan-400">
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

        <!-- Summary Stats -->
        <div class="bg-purple-100 rounded-xl shadow p-4 border-[1.5px] border-gray-800 focus:border-gray-800 ring-2 ring-purple-600">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <span class="text-sm text-gray-600">Total Items Sold</span>
                    <p class="text-xl font-bold">{{ number_format($items->sum('total_quantity'), 0) }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Total Revenue</span>
                    <p class="text-xl font-bold">Rp {{ number_format($items->sum('total_sales'), 0) }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Avg. Price/Item</span>
                    <p class="text-xl font-bold">
                        Rp {{ number_format($items->sum('total_sales') / max(1, $items->sum('total_quantity')), 0) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
