<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OccupancyResource\Pages;
use App\Filament\Resources\OccupancyResource\RelationManagers;
use App\Models\Occupancy;
use App\Models\Room;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OccupancyResource extends Resource
{
    protected static ?string $model = Occupancy::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Occupancies';

    protected static ?string $modelLabel = 'Occupancy';

    protected static ?string $pluralModelLabel = 'Occupancies';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Occupancy Details')
                    ->schema([
                        Forms\Components\Select::make('room_id')
                            ->label('Room')
                            ->options(Room::with('building')->get()->mapWithKeys(function ($room) {
                                return [$room->id => $room->building->name . ' - Room ' . $room->number];
                            }))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('user_id')
                            ->label('Occupant')
                            ->options(User::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->options(Occupancy::getStatusOptions())
                            ->required()
                            ->default(Occupancy::STATUS_DEPOSIT),

                        Forms\Components\TextInput::make('monthly_rent')
                            ->label('Monthly Rent')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Occupancy Period')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date (Optional)')
                            ->after('start_date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room.building.name')
                    ->label('Building')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('room.number')
                    ->label('Room')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Occupant')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'info' => Occupancy::STATUS_DEPOSIT,
                        'success' => Occupancy::STATUS_PAID,
                        'warning' => Occupancy::STATUS_UNPAID,
                        'danger' => Occupancy::STATUS_TERMINATED,
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('monthly_rent')
                    ->label('Monthly Rent')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('Ongoing'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Occupancy::getStatusOptions()),

                Tables\Filters\SelectFilter::make('room')
                    ->relationship('room', 'number')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('active_occupancies')
                    ->query(fn (Builder $query): Builder => $query->where('status', '!=', Occupancy::STATUS_TERMINATED))
                    ->label('Active Occupancies'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOccupancies::route('/'),
            'create' => Pages\CreateOccupancy::route('/create'),
            'edit' => Pages\EditOccupancy::route('/{record}/edit'),
        ];
    }
}
