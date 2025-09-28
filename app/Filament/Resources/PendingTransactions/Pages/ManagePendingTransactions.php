<?php

namespace App\Filament\Resources\PendingTransactions\Pages;

use App\Filament\Resources\PendingTransactions\PendingTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePendingTransactions extends ManageRecords
{
    protected static string $resource = PendingTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
