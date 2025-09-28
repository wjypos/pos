<?php

namespace App\Filament\Resources\Printers\Pages;

use App\Filament\Resources\Printers\PrinterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePrinters extends ManageRecords
{
    protected static string $resource = PrinterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
