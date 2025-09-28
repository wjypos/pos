<?php

namespace App\Filament\Resources\ExtraTopings;

use App\Filament\Resources\ExtraTopings\Pages\ManageExtraTopings;
use App\Models\ExtraToping;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Sections\forms;
use Filament\Forms\Components\Select;

class ExtraTopingResource extends Resource
{
    protected static ?string $model = ExtraToping::class;
    protected static ?string $navigationLabel = 'Topping';
    protected static string | UnitEnum | null $navigationGroup = 'Management';
    protected static ?int $navigationSort = 62;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('price_offline')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->label('Offline Price (Dine-in/Delivery)'),
                TextInput::make('price_online')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->label('Online Price (GoFood/GrabFood)'),
                Select::make('menus')
                    ->label('Menu Assignment')
                    ->multiple()
                    ->relationship(
                        'menus',
                        'name',
                        fn (Builder $query) => $query->with('category')->orderBy('category_id')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        ($record->category ? "{$record->category->name} - " : '') . $record->name
                    )
                    ->preload()
                    ->searchable()
                    ->optionsLimit(50)
                    ->columnSpanFull()
                    ->helperText('Select the menus this topping can be added to'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('price_offline')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price_online')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('menus.name')
                    ->badge()
                    ->separator(',')
                    ->color('primary')
                    ->label('Menus')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn () => auth()->user()?->is_admin ?? false),
                    DeleteAction::make()
                        ->visible(fn () => auth()->user()?->is_admin ?? false),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->is_admin ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExtraTopings::route('/'),
        ];
    }
}
