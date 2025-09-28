<?php

namespace App\Filament\Resources\PendingTransactions;

use App\Filament\Resources\PendingTransactions\Pages\ManagePendingTransactions;
use App\Models\PendingTransaction;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;

class PendingTransactionResource extends Resource
{
    protected static ?string $model = PendingTransaction::class;
    protected static ?string $navigationLabel = 'Tickets';
    protected static string|UnitEnum|null $navigationGroup = 'POS';
    protected static ?int $navigationSort = 2;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

   

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_identifier')
                    ->label('Transaction ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime(),
                TextColumn::make('total_amount')
                    ->money('IDR'),
            ])
            ->actions([ 
                ActionGroup::make([
                    Action::make('view')
                        ->label('Continue Transaction')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('success')
                        ->url(fn (PendingTransaction $record): string =>
                            route('filament.admin.pages.pos', ['load' => $record->transaction_identifier])
                    ),
                    DeleteAction::make()
                    ->visible(fn () => auth()->user()?->is_admin ?? false),
                ]),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => ManagePendingTransactions::route('/'),
        ];
    }
}
