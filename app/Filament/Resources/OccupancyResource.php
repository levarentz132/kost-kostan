<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OccupancyResource\Pages;
use App\Filament\Resources\OccupancyResource\RelationManagers;
use App\Models\Occupancy;
use App\Models\Occupant;
use App\Models\Room;
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

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    
    protected static ?string $navigationGroup = 'Management';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Room Assignment')
                    ->schema([
                        Forms\Components\Select::make('room_id')
                            ->relationship('room', 'number', function ($query) {
                                return $query->with('building');
                            })
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return $record->building->name . ' - Room ' . $record->number;
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('occupant_id')
                            ->relationship('occupant', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('phone_number')
                                    ->required()
                                    ->tel(),
                                Forms\Components\TextInput::make('job'),
                                Forms\Components\TextInput::make('email')
                                    ->email(),
                            ]),

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

                        Forms\Components\DatePicker::make('last_payment_date')
                            ->label('Last Payment Date')
                            ->placeholder('Select last payment date'),
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

                Tables\Columns\TextColumn::make('occupant.name')
                    ->label('Occupant')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('occupant.phone_number')
                    ->label('Phone')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Payment Status')
                    ->getStateUsing(function (Occupancy $record): string {
                        return $record->getPaymentStatusLabel();
                    })
                    ->colors([
                        'info' => fn ($state): bool => $state === 'Deposit',
                        'success' => fn ($state): bool => $state === 'Paid',
                        'warning' => fn ($state): bool => $state === 'Unpaid',
                        'danger' => fn ($state): bool => $state === 'Terminated',
                    ]),

                Tables\Columns\TextColumn::make('monthly_rent')
                    ->label('Monthly Rent')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_payment_date')
                    ->label('Last Payment')
                    ->date()
                    ->sortable()
                    ->placeholder('Not set'),

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

                Tables\Filters\Filter::make('active_occupancies')
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->where('status', '!=', Occupancy::STATUS_TERMINATED))
                    ->toggle(),

                Tables\Filters\Filter::make('paid_up')
                    ->label('Paid Up (Last 30 days)')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->whereNotNull('last_payment_date')
                            ->where('last_payment_date', '>=', now()->subDays(30))
                            ->where('status', '!=', Occupancy::STATUS_TERMINATED);
                    }))
                    ->toggle(),

                Tables\Filters\Filter::make('overdue_payments')
                    ->label('Overdue Payments (30+ days)')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->where('status', '!=', Occupancy::STATUS_TERMINATED)
                            ->where(function ($subQ) {
                                $subQ->where(function ($innerQ) {
                                    // No last payment date and more than 30 days since start
                                    $innerQ->whereNull('last_payment_date')
                                        ->where('start_date', '<', now()->subDays(30));
                                })->orWhere(function ($innerQ) {
                                    // Last payment more than 30 days ago
                                    $innerQ->whereNotNull('last_payment_date')
                                        ->where('last_payment_date', '<', now()->subDays(30));
                                });
                            });
                    }))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
