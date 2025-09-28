<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationLabel = 'Customer';
    protected static string | UnitEnum | null $navigationGroup = 'Management';
    protected static ?int $navigationSort = 2;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('customer_type')
                    ->required()
                    ->options([
                        'dine-in' => 'Dine-in',
                        'delivery' => 'Delivery',
                        'gofood' => 'GoFood',
                        'grabfood' => 'GrabFood',
                    ])    
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
           ->actions([
                ActionGroup::make([
                    EditAction::make()
                    ->visible(fn () => auth()->user()?->is_admin ?? false),
                    DeleteAction::make()
                    ->visible(fn () => auth()->user()?->is_admin ?? false),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()?->is_admin ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomers::route('/'),
        ];
    }
}
