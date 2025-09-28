<?php

namespace App\Filament\Resources\ExtraTopings\Pages;

use App\Filament\Resources\ExtraTopings\ExtraTopingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageExtraTopings extends ManageRecords
{
    protected static string $resource = ExtraTopingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth()->user()?->is_admin ?? false),
        ];
    }
}
