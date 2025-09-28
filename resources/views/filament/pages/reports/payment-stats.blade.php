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
        <div class="bg-danger-100 rounded-lg border-[1.5px] border-danger-600 focus:border-danger-600 shadow-lg p-2 md:p-3 flex flex-col w-full ring-1 ring-danger-600">
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
        <div class="bg-indigo-100 rounded-xl border-[1.5px] border-gray-500 shadow overflow-hidden p-4 gap-4 mb-4 focus:border-gray-500 ring-2 ring-indigo-600">
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
    </div>
</x-filament::page>
