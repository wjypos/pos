<?php

namespace App\Filament\Resources\TransactionHistoryManagement\Pages;

use App\Filament\Resources\TransactionHistoryManagement\TransactionHistoryManagementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTransactionHistoryManagement extends ManageRecords
{
    protected static string $resource = TransactionHistoryManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
