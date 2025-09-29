<?php

namespace App\Filament\Resources\OccupancyResource\Pages;

use App\Filament\Resources\OccupancyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOccupancies extends ListRecords
{
    protected static string $resource = OccupancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
