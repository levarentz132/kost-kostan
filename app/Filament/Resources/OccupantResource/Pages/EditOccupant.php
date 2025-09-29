<?php

namespace App\Filament\Resources\OccupantResource\Pages;

use App\Filament\Resources\OccupantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOccupant extends EditRecord
{
    protected static string $resource = OccupantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
