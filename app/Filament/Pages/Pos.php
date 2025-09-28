<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Menu;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use App\Models\Printer as PrinterModel;
use App\Models\PendingTransaction;
use App\Models\Payment;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer as EscposDriver;
use Mike42\Escpos\Printer;
use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Support\Facades\DB;

class Pos extends Page
{
    protected static ?string $navigationLabel = 'POS';
    protected static string|UnitEnum|null $navigationGroup = 'POS';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;
    protected string $view = 'filament.pages.pos';

    public $cart = [];
    public $customer_id;
    public $payment_method = 'cash';
    public $total_amount = 0;
    public $discount_type = 'fixed'; // "fixed" atau "percentage"
    public $discount_value = 0;
    public $platform_fee_type = 'fixed'; // "fixed" atau "percentage"
    public $platform_fee_value = 0;
    public $final_amount = 0;
    public $selectedCategory = '';
    public $transaction_identifier;
    public $manual_code = '';
    public $printer_ip = '';
    public $printer_port = '9100';
    public $available_printers = [];
    public $showTopingModal = false;
    public $selectedItemId = null;
    public $availableTopings = [];
    public $selectedCartKey = null;
    public $selectedPrinter = null;
    public $showSaveModal = false;
    public $showCompleteModal = false;
    public $showPrintDialog = false;
    public $currentAction = null;
    public $useSplitPayment = false;
    public $splitPayments = [
        ['method' => '', 'amount' => 0],
        ['method' => '', 'amount' => 0]
    ];

    protected $queryString = ['selectedCategory'];

    public function mount()
    {
        $this->cart = [];
        $this->transaction_identifier = '';
        $this->manual_code = '';
        
        // Get default printer settings
        $defaultPrinter = PrinterModel::where('is_default', true)
            ->where('status', 'active')
            ->first();
            
        if ($defaultPrinter) {
            $this->selectedPrinter = $defaultPrinter->id;
            $this->printer_ip = $defaultPrinter->ip_address;
            $this->printer_port = $defaultPrinter->port;
        }
        
        if (request()->has('load') && request()->get('load')) {
            $this->loadTransaction(request()->get('load'));
        }

        $this->initializeSplitPayments();
    }

    protected function initializeSplitPayments()
    {
        // Initialize with fixed amounts instead of calculated
        $this->splitPayments = [
            ['method' => '', 'amount' => 0],
            ['method' => '', 'amount' => $this->final_amount ?? 0]
        ];
    }

    public function toggleSplitPayment()
    {
        $this->useSplitPayment = !$this->useSplitPayment;
        if ($this->useSplitPayment) {
            // Set initial state for split payments
            $this->splitPayments = [
                ['method' => '', 'amount' => 0],
                ['method' => '', 'amount' => $this->final_amount]
            ];
        } else {
            // Reset to single payment
            $this->payment_method = '';
            $this->initializeSplitPayments();
        }
    }

    public function addToCart($menuId)
    {
        if (!$this->customer_id) {
            Notification::make()
                ->warning()
                ->title('Silahkan pilih customer dulu')
                ->duration(2000)
                ->send();
            return;
        }

        $menu = Menu::with('extraTopings')->find($menuId);
        $customer = Customer::find($this->customer_id);
        $price = $menu->getPriceByCustomerType($customer?->customer_type ?? 'dine-in');

        // Show topping modal immediately after adding to cart
        $cartKey = $menuId . '_' . uniqid();
        $this->cart[$cartKey] = [
            'id' => $menu->id,
            'name' => $menu->name,
            'price' => $price,
            'base_price' => $price,
            'toping_price' => 0,
            'topings' => [],
            'quantity' => 1,
            'cart_key' => $cartKey
        ];

        // Prepare available toppings for modal
        $this->selectedItemId = $menuId;
        $this->selectedCartKey = $cartKey;
        $this->availableTopings = $menu->extraTopings->map(function($toping) use ($customer) {
            return [
                'id' => $toping->id,
                'name' => $toping->name,
                'price' => $toping->getPriceByCustomerType($customer?->customer_type ?? 'dine-in'),
                'selected' => false
            ];
        })->toArray();
        $this->showTopingModal = true;

        $this->calculateTotals();
    }

    public function getActions(): array
    {
        return [];
    }

    public function selectItem($menuId, $cartKey)
    {
        $this->selectedItemId = $menuId;
        $this->selectedCartKey = $cartKey;

        $menu = Menu::with('extraTopings')->find($menuId);
        if (!$menu || !isset($this->cart[$cartKey])) return;

        $customer = Customer::find($this->customer_id);
        
        // Get current cart item toppings
        $currentTopings = $this->cart[$cartKey]['topings'] ?? [];
        
        // Map extra toppings with selection state
        $this->availableTopings = $menu->extraTopings->map(function($toping) use ($customer, $currentTopings) {
            $isSelected = collect($currentTopings)->contains('id', $toping->id);
            return [
                'id' => $toping->id,
                'name' => $toping->name,
                'price' => $toping->getPriceByCustomerType($customer?->customer_type ?? 'dine-in'),
                'selected' => $isSelected
            ];
        })->toArray();
        
        $this->showTopingModal = true;
    }

    public function updateTopingsFromModal(array $selectedIds)
    {
        if (!$this->selectedCartKey || !isset($this->cart[$this->selectedCartKey])) {
            $this->showTopingModal = false;
            return;
        }
        
        $menu = Menu::with('extraTopings')->find($this->selectedItemId);
        $customer = Customer::find($this->customer_id);

        $topingPrice = 0;
        $selectedTopings = [];

        foreach ($menu->extraTopings as $toping) {
            if (in_array($toping->id, $selectedIds)) {
                $price = $toping->getPriceByCustomerType($customer?->customer_type ?? 'dine-in');
                $topingPrice += $price;
                $selectedTopings[] = [
                    'id' => $toping->id,
                    'name' => $toping->name,
                    'price' => $price,
                ];
            }
        }

        $this->cart[$this->selectedCartKey]['topings'] = $selectedTopings;
        $this->cart[$this->selectedCartKey]['toping_price'] = $topingPrice;
        $this->cart[$this->selectedCartKey]['price'] = $this->cart[$this->selectedCartKey]['base_price'] + $topingPrice;

        $this->calculateTotals();
        $this->resetModalState();
    }

    protected function resetModalState()
    {
        $this->showTopingModal = false;
        $this->selectedItemId = null;
        $this->selectedCartKey = null;
        $this->availableTopings = [];
    }

    protected function buildTopingForm(): array
    {
        return [
            Forms\Components\CheckboxList::make('topings')
                ->label('Pilih Topping Tambahan')
                ->options(function () {
                    return collect($this->availableTopings)->pluck('name', 'id')->toArray();
                }),
        ];
    }

    public function updateTopings()
    {
        if (!$this->selectedCartKey || !isset($this->cart[$this->selectedCartKey])) {
            $this->showTopingModal = false;
            return;
        }

        $selectedTopings = collect($this->availableTopings)
            ->filter(fn($toping) => $toping['selected'])
            ->map(function($toping) {
                return [
                    'id' => $toping['id'],
                    'name' => $toping['name'],
                    'price' => $toping['price'],
                ];
            })->values()->toArray();

        $topingPrice = collect($selectedTopings)->sum('price');

        // Update cart item with new toppings
        $this->cart[$this->selectedCartKey]['topings'] = $selectedTopings;
        $this->cart[$this->selectedCartKey]['toping_price'] = $topingPrice;
        $this->cart[$this->selectedCartKey]['price'] = 
            $this->cart[$this->selectedCartKey]['base_price'] + $topingPrice;

        $this->calculateTotals();
        $this->showTopingModal = false;
        $this->resetModalState();
    }

    public function increaseQuantity($cartKey)
    {
        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity']++;
            $this->calculateTotals();
        }
    }

    public function decreaseQuantity($cartKey)
    {
        if (isset($this->cart[$cartKey])) {
            if ($this->cart[$cartKey]['quantity'] > 1) {
                $this->cart[$cartKey]['quantity']--;
                $this->calculateTotals();
            }
        }
    }

    public function removeFromCart($cartKey)
    {
        if (isset($this->cart[$cartKey])) {
            unset($this->cart[$cartKey]);
            $this->calculateTotals();
        }
    }

    public function calculateTotals()
    {
        $totalAmount = 0;
        foreach ($this->cart as $item) {
            $totalAmount += ($item['base_price'] + $item['toping_price']) * $item['quantity'];
        }
        $this->total_amount = $totalAmount;
        $this->calculateFinalAmount();
    }

    public function updated($field)
    {
        $this->calculateFinalAmount();
    }

    public function updatedCustomerId($value)
    {
        if ($value) {
            $customer = Customer::find($value);
            if ($customer) {
                $prefix = strtolower($customer->customer_type);
                $this->transaction_identifier = $prefix . '-';

                // Reset split payment when customer changes
                $this->useSplitPayment = false;
                $this->initializeSplitPayments();
            }
        }
        $this->calculateTotals();
    }

    public function updatedManualCode($value)
    {
        if (empty($value)) {
            Notification::make()
                ->title('Manual code is required')
                ->duration(1000)
                ->send();
            return;
        }

        $customer = Customer::find($this->customer_id);
        if ($customer) {
            $prefix = strtolower($customer->customer_type);
            // Allow same code/name on different days, so do not append date
            $this->transaction_identifier = $prefix . '-' . $value;
        }
    }
    

    public function validatePaymentMethod($customer, $paymentMethod) 
    {
        if (!$customer) {
            return false;
        }

        // For GoFood and GrabFood - fixed payment methods
        if (strtolower($customer->customer_type) === 'gofood') {
            $this->payment_method = 'Gopay';
            return true;
        }
        
        if (strtolower($customer->customer_type) === 'grabfood') {
            $this->payment_method = 'Grabpay';
            return true;
        }

        // For Dine-in and Delivery
        if (in_array(strtolower($customer->customer_type), ['dine-in', 'delivery'])) {
            $allowedMethods = ['cash', 'Qris', 'transfer'];
            
            // If using split payment, validate each payment method
            if ($this->useSplitPayment) {
                foreach ($this->splitPayments as $payment) {
                    if (!in_array(strtolower($payment['method']), array_map('strtolower', $allowedMethods))) {
                        return false;
                    }
                }
                return true;
            }

            // Single payment validation
            return in_array(strtolower($paymentMethod), array_map('strtolower', $allowedMethods));
        }

        return false;
    }

    public function updatedPaymentMethod($value)
    {
        $customer = Customer::find($this->customer_id);
        
        if (!$this->validatePaymentMethod($customer, $value)) {
            $this->payment_method = null;
            Notification::make()
                ->danger()
                ->title('Invalid payment method for this customer type')
                ->duration(1000)
                ->send();
            return;
        }

        $this->platform_fee_value = 0;
        $this->platform_fee_type = 'fixed';

        if ($customer && in_array($customer->customer_type, ['gofood', 'grabfood'])) {
            $this->platform_fee_type = 'percentage';
            
        }

        $this->calculateTotals();
    }

    public function calculateFinalAmount()
    {
        $total = $this->total_amount;

        // Calculate discount
        $discount = $this->discount_type === 'percentage' 
            ? ($this->discount_value / 100) * $total 
            : $this->discount_value;

        // Calculate platform fee
        $platformFee = 0;
        $customer = Customer::find($this->customer_id);
        if ($customer && in_array($customer->customer_type, ['gofood', 'grabfood'])) {
            $platformFee = $this->platform_fee_type === 'percentage'
                ? ($this->platform_fee_value / 100) * ($total - $discount)
                : $this->platform_fee_value;
        }

        $this->final_amount = $total - $discount - $platformFee;

        // Don't reset split payments on amount change
        if ($this->useSplitPayment) {
            $firstAmount = floatval($this->splitPayments[0]['amount']);
            if ($firstAmount > $this->final_amount) {
                $this->splitPayments[0]['amount'] = $this->final_amount;
                $firstAmount = $this->final_amount;
            }
            $this->splitPayments[1]['amount'] = $this->final_amount - $firstAmount;
        }
    }

    public function getSplitPaymentRemainingProperty()
    {
        if (!$this->useSplitPayment) {
            return 0;
        }
        
        $totalPaid = collect($this->splitPayments)->sum('amount');
        return max(0, $this->final_amount - $totalPaid);
    }

    public function updatedUseSplitPayment($value)
    {
        if ($value) {
            $this->splitPayments = [
                ['method' => '', 'amount' => $this->final_amount ?? 0]
            ];
        } else { 
            $this->splitPayments = [['method' => '', 'amount' => 0]];
        }
    }

    public function updatedSplitPayments($value, $key)
    {
        // Don't reset values, just update the second payment amount
        if (str_contains($key, '.amount.0')) {
            $firstAmount = floatval($this->splitPayments[0]['amount']);
            if ($firstAmount > $this->final_amount) {
                $this->splitPayments[0]['amount'] = $this->final_amount;
                $firstAmount = $this->final_amount;
            }
            $this->splitPayments[1]['amount'] = $this->final_amount - $firstAmount;
        }
    }

    public function addSplitPayment()
    {
        $remaining = $this->getSplitPaymentRemainingProperty();
        if ($remaining > 0) {
            $this->splitPayments[] = [
                'method' => '',
                'amount' => $remaining
            ];
        }
    }

    public function completeOnly()
    {
        try {
            if ($this->useSplitPayment) {
                $totalSplitAmount = collect($this->splitPayments)->sum('amount');
                if (abs($totalSplitAmount - $this->final_amount) > 0.01) {
                    Notification::make()
                        ->danger()
                        ->title('Split payment amounts must equal total amount')
                        ->send();
                    return;
                }
            }

            DB::beginTransaction();

            // Lock payment method for gofood/grabfood
            $customer = Customer::find($this->customer_id);
            $paymentMethodToSave = $this->payment_method;
            if ($customer) {
                $ctype = strtolower($customer->customer_type);
                if ($ctype === 'gofood') {
                    $paymentMethodToSave = 'Gopay';
                } elseif ($ctype === 'grabfood') {
                    $paymentMethodToSave = 'Grabpay';
                }
            }

            $transaction = Transaction::create([
                'transaction_identifier' => $this->transaction_identifier,
                'customer_id' => $this->customer_id,
                'user_id' => auth()->id(),
                'total_amount' => $this->total_amount,
                'discount_value' => $this->discount_value,
                'discount_type' => $this->discount_type,
                'platform_fee' => $this->platform_fee_value,
                'platform_fee_type' => $this->platform_fee_type,
                'final_amount' => $this->final_amount,
                'payment_method' => $this->useSplitPayment ? 'split' : $paymentMethodToSave,
                'status' => 'completed',
                'transaction_date' => now(),
            ]);
            // Then create payment(s)
            if ($this->useSplitPayment) {
                foreach ($this->splitPayments as $payment) {
                    if ($payment['amount'] > 0 && $payment['method']) {
                        DB::table('payments')->insert([
                            'transaction_id' => $transaction->id,
                            'payment_method' => $payment['method'],
                            'amount' => $payment['amount'],
                            'is_split_payment' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } else {
                DB::table('payments')->insert([
                    'transaction_id' => $transaction->id,
                    'payment_method' => $paymentMethodToSave,
                    'amount' => $this->final_amount,
                    'is_split_payment' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Create transaction details
            foreach ($this->cart as $item) {
                $transaction->transactionDetails()->create([
                    'menu_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['base_price'],
                    'subtotal' => ($item['base_price'] + $item['toping_price']) * $item['quantity'],
                    'toppings' => json_encode($item['topings'] ?? []),
                ]);
            }

            // Delete pending transaction if exists
            PendingTransaction::where('transaction_identifier', $this->transaction_identifier)->delete();

            DB::commit();

            // Reset form after successful transaction
            $this->reset([
                'cart',
                'customer_id',
                'payment_method',
                'total_amount',
                'discount_value',
                'platform_fee_value',
                'final_amount',
                'transaction_identifier',
                'useSplitPayment',
                'splitPayments',
            ]);

            Notification::make()
                ->success()
                ->title('Transaction successfully')
                ->duration(1000) // 1 second popup
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->danger()
                ->title('Failed to complete transaction')
                ->duration(1000)
                ->body($e->getMessage())
                ->send();
        }
    }

    public function testPrinterConnection()
    {
        try {
            if (empty($this->printer_ip)) {
                throw new \Exception('Printer IP not configured');
            }

            if (@fsockopen($this->printer_ip, $this->printer_port, $errno, $errstr, 1)) {
                Notification::make()
                    ->success()
                    ->title('Printer connection successful')
                    ->duration(1000)
                    ->send();
            } else {
                throw new \Exception("Could not connect to printer: $errstr ($errno)");
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Printer connection failed')
                ->duration(1000)
                ->body($e->getMessage())
                ->send();
        }
    }

    public function validateCartItem($item)
    {
        if (empty($item['id']) || empty($item['quantity']) || !is_numeric($item['quantity']) || $item['quantity'] <= 0) {
            throw new \Exception('Invalid cart item data: Menu ID or quantity is missing or invalid.');
        }

        if (empty($item['cart_key']) || !is_string($item['cart_key'])) {
            throw new \Exception('Invalid cart key: Cart key is missing or not a valid string.');
        }

        return true;
    }

    protected function processTransaction()
    {
        try {
            // Validate transaction data
            if (empty($this->transaction_identifier)) {
                throw new \Exception('Transaction identifier is required');
            }

            DB::beginTransaction();
            
            // Process transaction logic here
            
            DB::commit();

            Notification::make()
                ->success()
                ->title('Transaction saved successfully')
                ->duration(1000)
                ->send();

        } catch (\Throwable $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Failed to save transaction')
                ->duration(1000)
                ->body($e->getMessage())
                ->send();
        }
    }

    // Move this inside a method
    protected function resetCart()
    {
        $this->reset([
            'cart', 
            'customer_id', 
            'total_amount', 
            'discount_value', 
            'platform_fee_value', 
            'final_amount', 
            'transaction_identifier'
        ]);
        session()->forget('cart');
    }

    public function loadTransaction($identifier)
    {
        if (empty($identifier)) return;

        // Try to load from pending transactions first
        $pending = PendingTransaction::where('transaction_identifier', $identifier)->first();

        if ($pending) {
            // Load from pending transaction
            $this->transaction_identifier = $pending->transaction_identifier;
            $this->customer_id = $pending->customer_id;
            $this->payment_method = $pending->payment_method;
            $this->discount_value = $pending->discount_value;
            $this->platform_fee_value = $pending->platform_fee;
            $this->cart = $pending->cart_data;

            $this->calculateTotals();

            Notification::make()
                ->success()
                ->title('Pending transaction loaded')
                ->duration(1000)
                ->send();
            return;
        }

        // If not found in pending, try regular transactions
        $transaction = Transaction::where('transaction_identifier', $identifier)
            ->where('status', 'pending')
            ->with('transactionDetails')
            ->first();

        if (!$transaction) {
            Notification::make()
                ->danger()
                ->title('No transaction found')
                ->duration(1000)
                ->send();
            return;
        }

        // Load from regular transaction
        $this->loadRegularTransaction($transaction);
    }

    protected function loadRegularTransaction($transaction)
    {
        $this->transaction_identifier = $transaction->transaction_identifier;
        $this->customer_id = $transaction->customer_id;
        $this->payment_method = $transaction->payment_method;
        $this->discount_value = $transaction->discount_value;
        $this->platform_fee_value = $transaction->platform_fee;
        $this->platform_fee_type = $transaction->platform_fee_percentage ? 'percentage' : 'fixed';
        
        $this->cart = [];
        foreach ($transaction->transactionDetails as $detail) {
            $menu = Menu::find($detail->menu_id);
            if (!$menu) continue;

            $cartKey = $detail->cart_key ?? ($menu->id . '_' . uniqid());
            $this->cart[$cartKey] = $this->buildCartItem($menu, $detail);
        }

        $this->calculateTotals();

        Notification::make()
            ->success()
            ->title('Transaction loaded')
            ->duration(1000)
            ->send();
    }

    public function getMenusProperty()
    {
        // If no category is selected, default to "Pizza" category if exists
        if ($this->selectedCategory === '' || $this->selectedCategory === null) {
            $pizzaCategory = Category::where('name', 'like', '%pizza%')->first();
            if ($pizzaCategory) {
            $this->selectedCategory = $pizzaCategory->id;
            }
        }

        return Menu::query()
            ->when($this->selectedCategory !== '', function($query) {
            $query->where('category_id', $this->selectedCategory);
            })
            ->orderBy('name')
            ->simplePaginate(12);
    }

    public function updatedSelectedPrinter($value)
    {
        if ($value) {
            $printer = PrinterModel::find($value);
            if ($printer) {
                $this->printer_ip = $printer->ip_address;
                $this->printer_port = $printer->port;
            }
        }
    }

    protected function getViewData(): array 
    {
        return [
            'menus' => $this->menus,
            'categories' => Category::all(),
            'availablePrinters' => PrinterModel::where('status', 'active')->get(),
        ];
    }

    protected function discoverNetworkPrinters()
    {
        $printers = [];
        $subnet = '192.168.18.1'; // Adjust based on your network
        
        for ($i = 1; $i < 255; $i++) {
            $ip = $subnet . $i;
            if (@fsockopen($ip, $this->printer_port, $errno, $errstr, 0.5)) {
                $printers[] = $ip;
            }
        }
        
        $this->available_printers = $printers;
    }

    public function printReceipt($transaction_id = null)
    {
        if (!$this->selectedPrinter) {
            Notification::make()
                ->warning()
                ->title('Please select a printer first')
                ->duration(1000)
                ->send();
            return;
        }

        try {
            if (empty($this->printer_ip)) {
                throw new \Exception('Printer IP not configured');
            }

            // Test connection first
            if (!@fsockopen($this->printer_ip, $this->printer_port, $errno, $errstr, 1)) {
                throw new \Exception("Could not connect to printer: $errstr ($errno)");
            }

            // If connection successful, proceed with printing
            $connector = new \Mike42\Escpos\PrintConnectors\NetworkPrintConnector($this->printer_ip, $this->printer_port);
            $printer = new \Mike42\Escpos\Printer($connector);

            // Print receipt header
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("P-Plus\n");
            $printer->text("Jl.Kemang 17 Pitara Rangkapan jaya Depok\n");
            $printer->text("Phone: 0853-1184-0881\n");
            $printer->feed();

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("================================\n");

            // Transaction details
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Date: " . now()->format('Y-m-d H:i:s') . "\n");
            $printer->text("Transaction ID: " . ($this->transaction_identifier ?? 'N/A') . "\n");
            $printer->text("Customer: " . (Customer::find($this->customer_id)?->name ?? 'Walk-in') . "\n");
            $printer->feed();            
            
            $printer->text("--------------------------------\n");
             
            // Items           
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            foreach ($this->cart as $item) {
                // Print main item with base price
                $printer->text(str_pad($item['name'], 20));
                $printer->text(str_pad($item['quantity'] . 'x', 4));
                $printer->text(str_pad(number_format($item['base_price'], 0), 8, ' ', STR_PAD_LEFT) . "\n");
                
                // Print toppings if any
                if (!empty($item['topings'])) {
                    foreach ($item['topings'] as $topping) {
                        $printer->text("  + " . str_pad($topping['name'], 17));
                        $printer->text(str_pad(number_format($topping['price'], 0), 8, ' ', STR_PAD_LEFT) . "\n");
                    }
                }
            }
            
            $printer->text("--------------------------------\n");

            // Totals
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text("Subtotal: Rp " . number_format($this->total_amount, 0) . "\n");

            if ($this->discount_value > 0) {
                $printer->text("Discount: Rp " . number_format($this->discount_value, 0) . "\n");
            }

            if ($this->platform_fee_value > 0) {
                $printer->text("Platform Fee: Rp " . number_format($this->platform_fee_value, 0) . "\n");
            }

            $printer->text("Total: Rp " . number_format($this->final_amount, 0) . "\n");

            // Print payment details with split payment breakdown
            if ($this->useSplitPayment) {
                $printer->text("--------------------------------\n");
                $printer->text("Pembayaran Details:\n");
                foreach ($this->splitPayments as $payment) {
                    if ($payment['amount'] > 0 && $payment['method']) {
                        $printer->text(sprintf(
                            "%s: Rp %s\n",
                            strtoupper($payment['method']),
                            number_format($payment['amount'], 0)
                        ));
                    }
                }
            } else {
                // Print correct payment method for gofood/grabfood
                $customer = Customer::find($this->customer_id);
                $paymentMethodToPrint = strtoupper($this->payment_method);
                if ($customer) {
                    $ctype = strtolower($customer->customer_type);
                    if ($ctype === 'gofood') {
                        $paymentMethodToPrint = 'GOPAY';
                    } elseif ($ctype === 'grabfood') {
                        $paymentMethodToPrint = 'GRABPAY';
                    }
                }
                $printer->text("Payment: " . $paymentMethodToPrint . "\n");
            }

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("================================\n");

            // Footer
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->feed(2);
            $printer->text("Terima Kasih\n");
            $printer->text("Silakan datang kembali!\n");
            
            // Add proper feed and cut
            $printer->feed(4);
            $printer->cut(EscposDriver::CUT_FULL);
            $printer->close();

            Notification::make()
                ->success()
                ->title('Receipt printed successfully')
                ->duration(1000)
                ->send();
        
        } catch (\Exception $e) {
            \Log::error('Error printing receipt', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->danger()
                ->title('Error printing receipt: ' . $e->getMessage())
                ->duration(1000)
                ->send();
            throw $e; // Re-throw to be caught by caller
        }
    }    

    public function saveOnly()
    {
        try {
            if (empty($this->transaction_identifier)) {
                Notification::make()
                    ->danger()
                    ->title('Please enter transaction identifier')
                    ->duration(1000)
                    ->send();
                return;
            }

            // Save to pending transactions
            PendingTransaction::updateOrCreate(
                ['transaction_identifier' => $this->transaction_identifier],
                [
                    'customer_id' => $this->customer_id,
                    'user_id' => auth()->id(),
                    'total_amount' => $this->total_amount,
                    'discount_value' => $this->discount_value,
                    'platform_fee' => $this->platform_fee_value,
                    'final_amount' => $this->final_amount,
                    'payment_method' => $this->payment_method,
                    'cart_data' => $this->cart,
                ]
            );

            $this->reset(['cart', 'customer_id', 'total_amount', 'discount_value', 'platform_fee_value', 'final_amount', 'transaction_identifier']);
            session()->forget('cart');

            Notification::make()
                ->success()
                ->title('Transaction saved as pending')
                ->duration(1000)
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Failed to save pending transaction')
                ->duration(1000)
                ->body($e->getMessage())
                ->send();
        }
    }
}