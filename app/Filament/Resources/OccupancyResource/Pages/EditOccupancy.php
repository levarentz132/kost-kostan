<?php

namespace App\Filament\Resources\OccupancyResource\Pages;

use App\Filament\Resources\OccupancyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOccupancy extends EditRecord
{
    protected static string $resource = OccupancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
