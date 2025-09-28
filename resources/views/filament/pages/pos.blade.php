<x-filament::page>
    <div class="page-wrapper">
    <div class="grid md:grid-cols-2 gap-2 md:gap-4">
        <div class="card card-divider w-fit-auto h-fit-auto">
             <div class="bg-green-300 rounded-lg border-[1.5px] border-gray-500 shadow overflow-hidden p-2 md:p-3 flex flex-col w-full shadow-lg ring-2 ring-green-700">
                <!-- Customer Selection -->
                <div class="mb-1">
                    <select wire:model="customer_id" class="w-full rounded-lg text-center border-[1.5px] border-gray-600 bg-white hover:opacity-75 ring-1 ring-cyan-500 text-lg font-semibold ">
                        <option value="">Pilih Customer</option>
                        @foreach(App\Models\Customer::all() as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- Category Filter -->
                <div class="mb-1">
                    <select wire:model.live="selectedCategory" class="w-full rounded-lg text-center border-[1.5px] border-gray-600 bg-white hover:opacity-75 ring-1 ring-cyan-500 text-lg font-semibold ">
                        @foreach($categories as $category)
                               <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>                
                <!-- Menu Grid -->
                <div class="grid grid-cols-4 gap-auto border-t mt-2 pt-2 space-y-2">
                @foreach($menus as $menu)
                    <div class="flex flex-col items-center">
                        <button wire:click="addToCart({{ $menu->id }})" 
                        class="w-18 h-18  md:w-12 md:h-12 rounded-lg bg-white hover:opacity-75 transition-opacity">
                        @if($menu->image)
                            <img src="{{ Storage::url($menu->image) }}" 
                            alt="{{ $menu->name }}"
                            class="w-full h-full object-cover border-[1.5px] border-gray-500 rounded-lg ring-1 ring-cyan-500">
                        @endif
                        </button>
                        <span class="text-xs md:text-sm font-small text-center">
                            {{ $menu->name }}
                        </span>
                    </div>
                @endforeach
                </div>
                <!-- Pagination -->
                <div class="mt-2 text-sm">
                    {{ $menus->links('vendor.pagination.simple-tailwind') }}
                </div>
            </div>
        </div>
        <!-- Right Side Cart Section -->
        <div class="card card-divider w-fit-auto h-fit-auto">
           <div class="bg-green-300 rounded-lg border-[1.5px] border-gray-500 shadow-xl overflow-hidden p-2 md:p-3 flex flex-col w-full ring-2 ring-green-700">
            <div class="space-y-3">
                @foreach ($cart as $item)
                    <div class="border p-2 rounded bg-gray-100">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-semibold text-sm">{{ $item['name'] }}</div>
                                @if (!empty($item['topings']))
                                    <div class="text-xs text-gray-500 mt-1">
                                        Toping: {{ collect($item['topings'])->pluck('name')->implode(', ') }}
                                    </div>
                                @endif
                                <div class="text-xs text-gray-600 mt-1">
                                    {{ number_format($item['base_price'], 2) }}
                                    @if ($item['toping_price'] > 0)
                                        + {{ number_format($item['toping_price'], 2) }} (topping)
                                    @endif
                                    × {{ $item['quantity'] }}
                                </div>
                                <div class="text-sm font-medium mt-1">
                                    Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                                </div>
                            </div>

                            <div class="flex flex-col gap-1 items-end">
                                @if(!empty($item['topings']) || !empty($availableTopingsForCart[$item['id']] ?? []))
                                    <button wire:click="selectItem({{ $item['id'] }}, '{{ $item['cart_key'] }}')" class="text-blue-600 hover:underline text-sm">
                                        Topping
                                    </button>
                                @endif

                                <div class="flex gap-1">
                                    <button wire:click="decreaseQuantity('{{ $item['cart_key'] }}')" class="bg-gray-200 px-4 rounded">-</button>
                                    <button wire:click="increaseQuantity('{{ $item['cart_key'] }}')" class="bg-gray-200 px-4 rounded">+</button>
                                </div>

                                <button wire:click="removeFromCart('{{ $item['cart_key'] }}')" class="text-red-600 hover:underline text-sm">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>  
                      
            <div class="border-t mt-2 pt-2 space-y-2 text-md font-semibold">
                <div class="flex justify-between items-center">
                    <span class="font-lg text-md font-semibold">Total:</span>
                    <span class="font-lg text-md font-semibold">{{ number_format($total_amount, 2) }}</span>
                </div>
            </div>

            <div class="border-t mt-2 pt-2 space-y-2 text-md font-semibold">
            <!-- Discounts & Platform Fee -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="discount_value" class="block text-sm font-medium">Diskon</label>
                    <input id="discount_value" type="number" step="0.01" wire:model.lazy="discount_value" 
                    class="w-32 rounded-lg border-[1.5px] border-gray-500 bg-white ring-1 ring-cyan-500 text-md text-center font-semibold focus:border-gray-500" />
                </div>
                <div class="flex flex-col items-end">    
                    <label for="discount_type" class="block text-sm font-medium">Tipe Diskon</label>
                    <select id="discount_type" wire:model="discount_type" 
                    class="w-28 rounded-lg border-[1.5px] border-gray-500 bg-white ring-1 ring-cyan-500 text-md text-center font-semibold focus:border-gray-500">
                        <option value="fixed">Rp</option>
                        <option value="percentage">%</option>
                    </select>   
                </div>

                <div>
                    <label for="platform_fee_value" class="block text-sm  font-medium">Online Fee</label>
                    <input id="platform_fee_value" type="number" step="0.01" wire:model.lazy="platform_fee_value" 
                    class="w-32 rounded-lg border-[1.5px] border-gray-500 bg-white ring-1 ring-cyan-500 text-md text-center font-semibold focus:border-gray-500" />
                </div>
                <div class="flex flex-col items-end">
                    <label for="platform_fee_type" class="block text-sm font-medium">Tipe Fee</label>
                    <select id="platform_fee_type" wire:model="platform_fee_type"
                        class="w-28 rounded-lg border-[1.5px] border-gray-500 bg-white ring-1 ring-cyan-500 text-md text-center font-semibold focus:border-gray-500">
                        <option value="fixed">Rp</option>
                        <option value="percentage">%</option>
                    </select>
                </div>
            </div>

            <div class="border-t mt-2 pt-2 space-y-2 text-lg font-semibold">
                <div class="flex justify-between items-center text-lg font-semibold">
                    <span>Final Amount:</span>
                    <span>{{ number_format($final_amount, 2) }}</span>
                </div>
            </div>

            <div class="border-t mt-2 pt-2 space-y-2 text-sm font-small">
            <!-- Split Payment Section -->
            @php
                $customer = \App\Models\Customer::find($customer_id);
                $canSplitPayment = $customer && in_array(strtolower($customer->customer_type), ['dine-in', 'delivery']);
            @endphp
        
            @if($canSplitPayment) 
            <!-- Always show split payment button for dine-in/delivery -->
            <div class="mt-3">
                <x-filament::button
                    wire:click="toggleSplitPayment"
                    class="w-full border-[1.5px] border-gray-500 ring-1 ring-cyan-500"
                    :color="$useSplitPayment ? 'danger' : 'warning'"
                >
                    {{ $useSplitPayment ? 'Cancel Split Payment' : 'Enable Split Payment' }}
                </x-filament::button>
            </div>

            @if($useSplitPayment)
                <div class="mt-2 space-y-2">
                    @foreach($splitPayments as $index => $payment)
                    <div class="flex items-center space-x-2">
                        <select wire:model="splitPayments.{{ $index }}.method" 
                            class="w-1/2 rounded-lg text-center border-[1.5px] border-gray-500 bg-white ring-1 ring-cyan-500 text-sm font-small focus:border-gray-500">
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="Qris">QRIS</option>
                            <option value="transfer">Transfer</option>
                        </select>
                        <input type="number" 
                            wire:model.lazy="splitPayments.{{ $index }}.amount"
                            class="w-1/2 rounded-lg text-center border-[1.5px] border-gray-500 bg-white ring-1 ring-cyan-500 text-sm font-small focus:border-gray-500"
                            placeholder="Amount"
                            {{ $index === 1 ? 'readonly' : '' }}
                            step="1000"
                        >
                    </div>
                    @endforeach
                    
                    <div class="flex justify-between text-sm mt-1">
                        <span>Total Split: {{ number_format(collect($splitPayments)->sum('amount'), 2) }}</span>
                        <span>Remaining: {{ number_format($this->getSplitPaymentRemainingProperty(), 2) }}</span>
                    </div>
                </div>
            @else
                <!-- Regular payment method selector -->
                <select wire:model.live="payment_method" class="w-full rounded-lg text-center border-[1.5px] border-gray-500 bg-white ring-1 ring-cyan-500 text-md font-medium focus:border-gray-500">
                    <option value="">Select Payment</option>
                    @php
                        $customer = \App\Models\Customer::find($customer_id);
                        $paymentMethods = [];
                        if ($customer) {
                            switch ($customer->customer_type) {
                                case 'gofood':
                                    $paymentMethods = ['Gopay'];
                                    break;
                                case 'grabfood':
                                    $paymentMethods = ['Grabpay'];
                                    break;
                                case 'dine-in':
                                case 'delivery':
                                    $paymentMethods = ['cash', 'Qris', 'transfer'];
                                    break;
                            }
                        }
                    @endphp
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                    @endforeach
                </select>
            @endif
            @else
                <select wire:model.live="payment_method" class="w-full rounded-lg text-center ring-1 ring-cyan-500 border-[1.5px] border-gray-500 mt-2 text-md font-semibold ">
                <option value="">Select Payment Method</option>
                @php
                    $customer = \App\Models\Customer::find($customer_id);
                    $paymentMethods = [];
                    $isLockedPayment = false;
                    $lockedPaymentValue = '';
                    if ($customer) {
                        switch ($customer->customer_type) {
                            case 'gofood':
                                $paymentMethods = ['Gopay'];
                                $isLockedPayment = true;
                                $lockedPaymentValue = 'Gopay';
                                break;
                            case 'grabfood':
                                $paymentMethods = ['Grabpay'];
                                $isLockedPayment = true;
                                $lockedPaymentValue = 'Grabpay';
                                break;
                            case 'dine-in':
                            case 'delivery':
                                $paymentMethods = ['cash', 'Qris', 'transfer'];
                                if (empty($payment_method)) {
                                    $payment_method = 'cash';
                                }
                                break;
                            }
                    }
                @endphp
                @if($isLockedPayment)
                    <select class="w-full rounded-xs ring-1 ring-cyan-500 border-gray-500 mt-3 text-xs font-xs text-gray-500" disabled>
                        <option value="{{ $lockedPaymentValue }}">{{ ucfirst($lockedPaymentValue) }}</option>
                    </select>
                    <input type="hidden" wire:model="payment_method" value="{{ $lockedPaymentValue }}">
                @else
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                    @endforeach
                @endif
                </select>
            @endif
            <div class="border-t mt-2 pt-2 space-y-2 text-md font-semibold">
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <input type="text" 
                        wire:model="transaction_identifier" 
                        readonly
                        placeholder="Transaction ID"
                        class="w-full rounded-lg text-center bg-red-200 ring-1 ring-cyan-500 border-[1.5px] border-gray-500 text-md font-medium"/>
                    <input type="text" 
                        wire:model.live="manual_code"
                        placeholder="Number or Name"
                        class="w-full rounded-lg text-center ring-1 ring-cyan-500 border-[1.5px] border-gray-500 bg-white text-md font-medium"/>
                </div>
            </div>

            <div class="border-t mt-2 pt-2 flex space-x-2">
                <x-filament::button 
                    wire:click="printReceipt"
                    class="flex-1 bg-orange-500 hover:bg-orange-700 border-[1.5px] border-white text-white py-3 rounded-md font-medium focus:border-gray-500">
                    Print
                </x-filament::button>

                <x-filament::button 
                    wire:click="saveOnly"
                    class="flex-1 bg-info-500 hover:bg-info-700 border-[1.5px] border-white text-white py-3 rounded-md font-mediumfocus:border-gray-500">
                    Save
                </x-filament::button>

                <x-filament::button 
                    wire:click="completeOnly"
                    class="flex-1 bg-green-500 hover:bg-green-700 border-[1.5px] border-white text-white py-3 rounded-md font-medium focus:border-gray-500">
                    Complete
                </x-filament::button>
            </div>
          </div>
        </div>        
    </div>        

<!-- Topping Modal -->
@if($showTopingModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-cyan-200 rounded-lg shadow-lg w-full max-w-lg p-6">
            <div class="mb-4">
                <h2 class="text-lg font-semibold">
                    Pilih Topping 
                    @if($selectedItemId && isset($cart[$selectedCartKey]))
                        untuk {{ $cart[$selectedCartKey]['name'] }}
                    @endif
                </h2>
            </div>

            <div class="space-y-2 max-h-[60vh] overflow-y-auto py-2">
                @if(!empty($availableTopings))
                    @foreach ($availableTopings as $index => $toping)
                        <label class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg hover:bg-gray-100">
                            <input type="checkbox" 
                                wire:model="availableTopings.{{ $index }}.selected"
                                class="rounded border-gray-300">
                            <span class="flex-1">{{ $toping['name'] }}</span>
                            <span class="text-sm text-gray-600">
                                Rp {{ number_format($toping['price'], 0, ',', '.') }}
                            </span>
                        </label>
                    @endforeach
                @else
                    <p class="text-gray-500 text-center py-4">No toppings available</p>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-2 border-t pt-4">
                <x-filament::button
                    wire:click="$set('showTopingModal', false)"
                    color="secondary"
                >
                    Batal
                </x-filament::button>
                
                <x-filament::button
                    wire:click="updateTopings"
                    color="primary"
                >
                    Update
                </x-filament::button>
            </div>
        </div>
    </div>
@endif
    </div>
</x-filament::page>
