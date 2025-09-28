<div class="space-y-6"> 
    <div class="grid grid-cols-2 gap-4">
        <x-filament::section>
            <x-slot name="heading">Transaction Info</x-slot>
            
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Transaction ID</dt>
                    <dd class="mt-1">{{ $transaction->transaction_identifier }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Customer</dt>
                    <dd class="mt-1">{{ $transaction->customer?->name ?? 'Walk-in' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Date</dt>
                    <dd class="mt-1">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                        <x-filament::badge :color="$transaction->trashed() ? 'danger' : 'success'">
                            {{ $transaction->trashed() ? 'Deleted' : ucfirst($transaction->status) }}
                        </x-filament::badge>
                        @if($transaction->trashed() && $transaction->deletedBy)
                            <span class="text-xs text-gray-500 block mt-1">
                                Deleted by: {{ $transaction->deletedBy->name }}
                            </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Payment Method</dt>
                    <dd class="mt-1 uppercase">{{ $transaction->payment_method }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Subtotal</dt>
                    <dd class="mt-1">Rp {{ number_format($transaction->total_amount) }}</dd>
                </div>

                @if($transaction->discount_value > 0)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Discount</dt>
                        <dd class="mt-1">
                            @if($transaction->discount_type === 'percentage')
                                {{ $transaction->discount_value }}%
                                (Rp {{ number_format($transaction->total_amount * $transaction->discount_value / 100, 0, ',', '.') }})
                            @else
                                Rp {{ number_format($transaction->discount_value, 0, ',', '.') }}
                            @endif
                        </dd>
                    </div>
                @endif

                @if($transaction->platform_fee > 0)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Platform Fee</dt>
                        <dd class="mt-1">
                            @if($transaction->platform_fee_type === 'percentage')
                                {{ $transaction->platform_fee }}%
                                (Rp {{ number_format($transaction->total_amount * $transaction->platform_fee / 100, 0, ',', '.') }})
                            @else
                                Rp {{ number_format($transaction->platform_fee, 0, ',', '.') }}
                            @endif
                        </dd>
                    </div>
                @endif

                <div class="col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Total Amount</dt>
                    <dd class="mt-1 text-lg font-bold">Rp {{ number_format($transaction->final_amount) }}</dd>
                </div>                
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Payment Details</x-slot>
            <div class="mt-4">
                @if($transaction->payment_method === 'split')
                    <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                        <div class="font-medium text-gray-700">Split Payment</div>
                        @foreach($transaction->payments as $payment)
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">{{ strtoupper($payment->payment_method) }}</span>
                                <span class="font-medium">Rp {{ number_format($payment->amount, 0) }}</span>
                            </div>
                        @endforeach
                        <div class="border-t pt-2 mt-2 flex justify-between items-center font-medium">
                            <span>Total</span>
                            <span>Rp {{ number_format($transaction->final_amount, 0) }}</span>
                        </div>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">{{ strtoupper($transaction->payment_method) }}</span>
                            <span class="font-medium">Rp {{ number_format($transaction->final_amount, 0) }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Items</x-slot>
        
        <div class="space-y-4">
            @foreach($transaction->transactionDetails as $detail)
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex justify-between items-start">
                        <div class="space-y-1">
                            <div class="font-medium">{{ $detail->menu->name }}</div>
                            <div class="text-sm text-gray-500">{{ $detail->menu->category->name }}</div>
                            @if(!empty(json_decode($detail->toppings, true)))
                                <div class="text-sm text-gray-500">
                                    Extra Toppings:
                                    @foreach(json_decode($detail->toppings, true) as $topping)
                                        <span class="inline-flex items-center bg-gray-100 px-2 py-0.5 rounded text-xs">
                                            {{ $topping['name'] }} (+Rp {{ number_format($topping['price']) }})
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="text-sm">{{ $detail->quantity }}x @ Rp {{ number_format($detail->price) }}</div>
                            <div class="font-medium">Rp {{ number_format($detail->subtotal) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</div>

