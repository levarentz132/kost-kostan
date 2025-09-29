<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Occupant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'job',
        'email',
        'address',
        'date_of_birth',
        'gender',
        'national_id',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Get all occupancies for this occupant
     */
    public function occupancies(): HasMany
    {
        return $this->hasMany(Occupancy::class);
    }

    /**
     * Get the current active occupancy
     */
    public function currentOccupancy()
    {
        return $this->occupancies()
            ->whereIn('status', [Occupancy::STATUS_DEPOSIT, Occupancy::STATUS_PAID, Occupancy::STATUS_UNPAID])
            ->first();
    }

    /**
     * Get gender options
     */
    public static function getGenderOptions(): array
    {
        return [
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
        ];
    }
}
