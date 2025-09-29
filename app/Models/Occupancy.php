<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Occupancy extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_DEPOSIT = 'deposit';
    public const STATUS_PAID = 'paid';
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_TERMINATED = 'terminated';

    protected $fillable = [
        'room_id',
        'user_id',
        'start_date',
        'end_date',
        'status',
        'monthly_rent',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function occupant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all available status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DEPOSIT => 'Deposit',
            self::STATUS_PAID => 'Paid',
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_TERMINATED => 'Terminated',
        ];
    }

    /**
     * Check if occupancy is active (not terminated)
     */
    public function isActive(): bool
    {
        return $this->status !== self::STATUS_TERMINATED;
    }

    /**
     * Check if payment is current
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DEPOSIT => 'info',
            self::STATUS_PAID => 'success',
            self::STATUS_UNPAID => 'warning',
            self::STATUS_TERMINATED => 'danger',
            default => 'secondary',
        };
    }
}
