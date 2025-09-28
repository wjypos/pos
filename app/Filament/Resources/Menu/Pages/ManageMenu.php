<?php

namespace App\Filament\Resources\Menu\Pages;

use App\Filament\Resources\Menu\MenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMenu extends ManageRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
             ->visible(fn () => auth()->user()?->is_admin ?? false),
        ];
    }
}
