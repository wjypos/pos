<?php

namespace App\Filament\Resources\Printers;

use App\Models\Printer;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\Printers\Pages\ManagePrinters;
use Mike42\Escpos\Printer as EscposDriver;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Log;

class PrinterResource extends Resource
{
    protected static ?string $model = Printer::class;
    protected static ?string $navigationLabel = 'Printer';
    protected static string|\UnitEnum|null $navigationGroup = 'Management';
    protected static ?int $navigationSort = 5;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->label('Printer Name')->maxLength(255),
            TextInput::make('ip_address')->required()->label('IP Address')->placeholder('192.168.1.100')
                ->regex('/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/')->validationAttribute('IP address')
                ->helperText('Enter valid IPv4 address'),
            TextInput::make('port')->required()->default('9100')->label('Port'),
            TextInput::make('location')->label('Location')->maxLength(255)->placeholder('Kitchen, Bar, etc'),
            Toggle::make('is_default')->label('Default')->inline(false)->onColor('success')->offColor('danger'),
            Select::make('status')->required()->default('active')->options([
                'active' => 'Active',
                'inactive' => 'Inactive',
            ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('ip_address')->searchable()->sortable(),
                TextColumn::make('port')->sortable(),
                TextColumn::make('location')->searchable(),
                IconColumn::make('is_default')->boolean()->label('Default')->sortable(),
                BadgeColumn::make('status')->colors([
                    'success' => 'active',
                    'danger' => 'inactive',
                ]),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ]),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),

                    Action::make('test')
                        ->label('Test Printer')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalContent(function (Printer $record) {
                            $output = '';
                            try {
                                // Try network print first
                                $connector = new NetworkPrintConnector($record->ip_address, $record->port, 2); // 2s timeout
                                $printer = new EscposDriver($connector);
                            } catch (\Throwable $e) {
                                // Fallback to USB
                                try {
                                    $usbPath = '/dev/usb/lp0'; // adjust this path to your environment
                                    $connector = new FilePrintConnector($usbPath);
                                    $printer = new EscposDriver($connector);
                                    $output .= "⚠️ Network failed. Fallback to USB printer ($usbPath).\n";
                                } catch (\Throwable $usbError) {
                                // Fallback to file logging for test
                                    $logPath = storage_path('logs/printer_output.txt');
                                    $connector = new FilePrintConnector($logPath);
                                    $printer = new EscposDriver($connector);
                                    $output .= "⚠️ USB failed. Fallback to file ($logPath).\n";
                                }
                            }

                            // Try printing something
                            try {
                                $printer->setJustification(EscposDriver::JUSTIFY_CENTER);
                                $printer->text("=== Test Print ===\n");
                                $printer->text("Printer: " . $record->name . "\n");
                                $printer->text("IP: " . $record->ip_address . "\n");
                                $printer->text(date('Y-m-d H:i:s') . "\n");
                                $printer->feed(3);
                                $printer->cut();
                                $printer->close();
                                $output .= "✅ Test print successful.\n";
                            } catch (\Exception $e) {
                                $output .= "❌ Print failed: " . $e->getMessage() . "\n";
                        }
                    }),

                DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePrinters::route('/'),
        ];
    }
}
