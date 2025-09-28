<?php

namespace App\Filament\Resources\TransactionHistoryManagement;

use UnitEnum;
use BackedEnum;
use Carbon\Carbon;
use Filament\Tables\Table;
use Mike42\Escpos\Printer;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Tables\Columns;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Support\Icons\Heroicon;
use Filament\Support\Exceptions\Halt;
use App\Models\Printer as PrinterModel;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use Mike42\Escpos\Printer as EscposDriver;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use App\Filament\Resources\TransactionHistoryManagement\Pages\ManageTransactionHistoryManagement;

class TransactionHistoryManagementResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationLabel = 'Transactions';
    protected static string|UnitEnum|null $navigationGroup = 'POS';
    protected static ?int $navigationSort = 2;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_identifier')
                    ->searchable()
                    ->label('ID'),
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('payment_method')
                    ->searchable()
                    ->label('Payment')
                    ->formatStateUsing(function ($state, $record) {
                        // If using split payment, show 'Split' or join payment methods if available
                        if ($state === 'split' && $record->relationLoaded('payments') && $record->payments->count()) {
                            return $record->payments->pluck('payment_method')->implode(' / ');
                        }
                        // Otherwise, just show the payment method as text
                        return ucfirst($state ?? '-');
                    }),
                TextColumn::make('total_amount')
                    ->money('IDR'),
                TextColumn::make('final_amount')
                    ->money('IDR'),
                TextColumn::make('discount_value')
                    ->label('Discount')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->discount_type === 'percentage') {
                            $amount = $record->total_amount * $state / 100;
                            return "{$state}% (Rp " . number_format($amount, 0, ',', '.') . ")";
                        }
                        if ($state > 0) {
                            return 'Rp ' . number_format($state, 0, ',', '.');
                        }
                        return '-';
                    }),
                TextColumn::make('platform_fee')
                    ->label('Platform Fee')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->platform_fee_type === 'percentage') {
                            $amount = $record->total_amount * $state / 100;
                            return "{$state}% (Rp " . number_format($amount, 0, ',', '.') . ")";
                        }
                        if ($state > 0) {
                            return 'Rp ' . number_format($state, 0, ',', '.');
                        }
                        return '-';
                    }),
                TextColumn::make('transaction_date')
                    ->dateTime(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function ($state, $record) {
                        // Check if the record is deleted
                        if ($record->trashed()) {
                            return 'deleted';
                        }
                        return $state ?? 'completed';
                    })
                    ->color(fn ($record): string => 
                        $record->trashed() ? 'danger' : 'success'
                    ),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Transactions')
                    ->visible(fn () => auth()->user()?->is_admin ?? false),
                SelectFilter::make('short')
                    ->label('Short By Date')
                    ->options([
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'this_week' => 'This Week',
                        'this_month' => 'This Month',
                        'this_year' => 'This Year',
                    ])
                    ->default('today')
                    ->query(function (Builder $query, array $data) {
                        $value = $data['short'] ?? $data['value'] ?? 'today';
                        switch ($value) {
                            case 'today':
                                $query->whereDate('transaction_date', now()->toDateString());
                                break;
                            case 'yesterday':
                                $query->whereDate('transaction_date', now()->subDay()->toDateString());
                                break;
                            case 'this_week':
                                $query->whereBetween('transaction_date', [
                                    now()->startOfWeek(), now()->endOfWeek()
                                ]);
                                break;
                            case 'this_month':
                                $query->whereMonth('transaction_date', now()->month)
                                      ->whereYear('transaction_date', now()->year);
                                break;
                            case 'this_year':
                                $query->whereYear('transaction_date', now()->year);
                                break;
                        }
                    }),
                // Custom date range filter
                \Filament\Tables\Filters\Filter::make('custom_date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('start_date')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('end_date')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['start_date']) && !empty($data['end_date'])) {
                            $query->whereBetween('transaction_date', [
                                $data['start_date'] . ' 00:00:00',
                                $data['end_date'] . ' 23:59:59',
                            ]);
                        }
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->icon('heroicon-o-eye')
                        ->modalContent(fn ($record) => view('filament.pages.partials.transaction-view-details', [
                            'transaction' => $record->load(['customer', 'transactionDetails.menu.category'])
                        ]))
                        ->modalWidth('6xl')
                        ->modalHeading(fn ($record) => "Transaction #{$record->transaction_identifier}"),
                    Action::make('print')
                        ->label('Print Receipt')
                        ->icon('heroicon-o-printer')
                        ->visible(fn ($record): bool => ($record->status ?? null) !== 'deleted')
                        ->requiresConfirmation()
                        ->modalHeading('Print Receipt')
                        ->modalDescription('Are you sure you want to print this receipt?')
                        ->action(function ($record) {
                            static::printReceiptStatic($record);
                        }),
                    Action::make('delete')
                        ->label('Delete Transaction')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            if ($record->status === 'deleted') {
                                throw new Halt('This transaction has already been deleted.');
                            }
                            $record->deleted_by = auth()->id();
                            $record->save();
                            $record->delete();
                        }),
                    Action::make('forceDelete')
                        ->label('Delete History')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()?->is_admin ?? false)
                        ->action(function (Transaction $record) {
                            $record->forceDelete();
                        }),
                ]),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->is_admin ?? false),
                ]),
            ]);
    }

    protected function getTablePollingInterval(): ?string
    {
        return null;
    }

    // Add a static wrapper for printReceipt
    public static function printReceiptStatic($transaction)
    {
        try {
            $defaultPrinter = \App\Models\Printer::where('is_default', true)
                ->where('status', 'active')
                ->first();

            if (!$defaultPrinter) {
                \Filament\Notifications\Notification::make()
                    ->title('No default printer found')
                    ->body('Please configure a default printer first.')
                    ->warning()
                    ->send();
                return;
            }

            $connector = new \Mike42\Escpos\PrintConnectors\NetworkPrintConnector($defaultPrinter->ip_address, $defaultPrinter->port);
            $printer = new \Mike42\Escpos\Printer($connector);


            // Print header
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
            $printer->text("P-Plus\n");
            $printer->text("Jl.Kemang 17 Pitara Rangkapan jaya Depok\n");
            $printer->text("Phone: 0853-1184-0881\n");
            $printer->feed();

            $printer->text("================================\n");

            // Transaction details
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_LEFT);
            $printer->text("Date: " . ($transaction->transaction_date ?? now()) . "\n");
            $printer->text("Transaction ID: " . ($transaction->transaction_identifier ?? '-') . "\n");
            $printer->text("Customer: " . ($transaction->customer?->name ?? 'Walk-in') . "\n");
            $printer->feed();

            $printer->text("--------------------------------\n");

            // Print items
            foreach ($transaction->transactionDetails as $detail) {
                $printer->text(str_pad($detail->menu->name ?? '-', 20));
                $printer->text(str_pad($detail->quantity . 'x', 4));
                $printer->text(str_pad(number_format($detail->price, 0, ',', '.'), 8, ' ', STR_PAD_LEFT) . "\n");

                // Print toppings if any
                if (!empty($detail->toppings)) {
                    $toppings = json_decode($detail->toppings, true) ?? [];
                    foreach ($toppings as $topping) {
                        $printer->text("  + " . str_pad($topping['name'], 17));
                        $printer->text(str_pad(number_format($topping['price'], 0, ',', '.'), 8, ' ', STR_PAD_LEFT) . "\n");
                    }
                }
            }

            $printer->text("--------------------------------\n");

            // Totals
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_RIGHT);
            $printer->text("Subtotal: Rp " . number_format($transaction->total_amount, 0, ',', '.') . "\n");

            if ($transaction->discount_value > 0) {
    if ($transaction->discount_type === 'percentage') {
        $discountAmount = $transaction->total_amount * $transaction->discount_value / 100;
        $printer->text("Discount: {$transaction->discount_value}% (Rp " . number_format($discountAmount, 0, ',', '.') . ")\n");
    } else {
        $printer->text("Discount: Rp " . number_format($transaction->discount_value, 0, ',', '.') . "\n");
    }
}

if ($transaction->platform_fee > 0) {
    if ($transaction->platform_fee_type === 'percentage') {
        $platformAmount = $transaction->total_amount * $transaction->platform_fee / 100;
        $printer->text("Platform Fee: {$transaction->platform_fee}% (Rp " . number_format($platformAmount, 0, ',', '.') . ")\n");
    } else {
        $printer->text("Platform Fee: Rp " . number_format($transaction->platform_fee, 0, ',', '.') . "\n");
    }
}


            $printer->text("Total: Rp " . number_format($transaction->final_amount, 0, ',', '.') . "\n");

            // Split payments breakdown
            if ($transaction->relationLoaded('payments') || isset($transaction->payments)) {
                $printer->text("\nPembayaran Detail:\n");
                foreach ($transaction->payments as $payment) {
                    $method = strtoupper($payment->payment_method);
                    $amount = number_format($payment->amount, 0, ',', '.');
                    $printer->text("  {$method}: Rp {$amount}\n");
                }
            }

            // Footer
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
            $printer->text("================================\n");
            $printer->feed(2);
            $printer->text("Terima Kasih\n");
            $printer->text("Silakan datang kembali!\n");
            $printer->feed(4);

            $printer->cut();
            $printer->close();

            \Filament\Notifications\Notification::make()
                ->title('Receipt printed successfully')
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Failed to print receipt')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    public static function getPages(): array
    {
        return [
            'index' => ManageTransactionHistoryManagement::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}