<?php

namespace App\Filament\Resources\Menu;

use App\Filament\Resources\Menu\Pages\ManageMenu;
use App\Models\Menu;
use BackedEnum;
use UnitEnum;
use App\Models\Category;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;
    protected static ?string $navigationLabel = 'Menu';
    protected static string | UnitEnum | null $navigationGroup = 'Management';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->default(fn() => 'M_' . now()->format('His'))
                    ->required()
                    ->maxLength(20),
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name', fn ($query) => $query->select('id', 'name'))
                    ->required()
                    ->createOptionForm([
                        TextInput::make('code')
                            ->default(fn() => 'CA_' . now()->format('His'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255),
                    ]),
                TextInput::make('price_offline')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('price_gofood')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('price_grabfood')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('menu-images')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->imageEditor() // Optional: allow crop/compression
                    ->maxSize(5120), // 5MB max
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price_offline')
                    ->numeric()
                    ->money('IDR'),
                TextColumn::make('price_gofood')
                    ->numeric()
                    ->money('IDR'),
                TextColumn::make('price_grabfood')
                    ->numeric()
                    ->money('IDR'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                ImageColumn::make('image')
                    ->disk('public')
                    ->imageHeight(40)
                    ->square()
                    ->label('Image'),
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
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->is_admin ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMenu::route('/'),
        ];
    }
}
