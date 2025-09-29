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
    public const STATUS_TERMINATED = 'terminated';

    protected $fillable = [
        'room_id',
        'occupant_id',
        'start_date',
        'end_date',
        'status',
        'monthly_rent',
        'last_payment_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'last_payment_date' => 'date',
        'monthly_rent' => 'decimal:2',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function occupant(): BelongsTo
    {
        return $this->belongsTo(Occupant::class);
    }

    /**
     * Get all available status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DEPOSIT => 'Deposit',
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
     * Check if payment is current (last payment within 30 days)
     */
    public function isPaid(): bool
    {
        if (!$this->last_payment_date) {
            return false;
        }
        
        return $this->last_payment_date->diffInDays(now()) <= 30;
    }

    /**
     * Check if payment is overdue (last payment over 30 days ago)
     */
    public function isUnpaid(): bool
    {
        if (!$this->last_payment_date) {
            return true;
        }
        
        return $this->last_payment_date->diffInDays(now()) > 30;
    }

    /**
     * Get the computed payment status
     */
    public function getPaymentStatus(): string
    {
        if ($this->status === self::STATUS_TERMINATED) {
            return 'terminated';
        }
        
        if ($this->status === self::STATUS_DEPOSIT) {
            return 'deposit';
        }
        
        return $this->isPaid() ? 'paid' : 'unpaid';
    }

    /**
     * Get the display label for payment status
     */
    public function getPaymentStatusLabel(): string
    {
        // Show actual status for deposit and terminated
        if ($this->status === self::STATUS_DEPOSIT) {
            return 'Deposit';
        }
        if ($this->status === self::STATUS_TERMINATED) {
            return 'Terminated';
        }
        
        // For active occupancies, show paid/unpaid based on 30-day rule
        if (!$this->last_payment_date) {
            // No payment date but more than 30 days since start = unpaid
            return $this->start_date->diffInDays(now()) > 30 ? 'Unpaid' : 'Paid';
        }
        
        // Check if last payment was within 30 days
        return $this->last_payment_date->diffInDays(now()) <= 30 ? 'Paid' : 'Unpaid';
    }

    /**
     * Update the last payment date to today
     */
    public function recordPayment(): bool
    {
        return $this->update(['last_payment_date' => now()]);
    }

    /**
     * Get days since last payment
     */
    public function getDaysSinceLastPayment(): ?int
    {
        if (!$this->last_payment_date) {
            return null;
        }
        
        return $this->last_payment_date->diffInDays(now());
    }

    /**
     * Get detailed payment status with days information
     */
    public function getDetailedPaymentStatus(): string
    {
        if ($this->status === self::STATUS_DEPOSIT) {
            return 'Deposit';
        }
        if ($this->status === self::STATUS_TERMINATED) {
            return 'Terminated';
        }
        
        if (!$this->last_payment_date) {
            $daysSinceStart = $this->start_date->diffInDays(now());
            return $daysSinceStart > 30 ? "Unpaid ({$daysSinceStart} days since start)" : "Paid (New occupancy)";
        }
        
        $daysSincePayment = $this->last_payment_date->diffInDays(now());
        return $daysSincePayment <= 30 ? "Paid ({$daysSincePayment} days ago)" : "Unpaid ({$daysSincePayment} days overdue)";
    }

    /**
     * Check if payment is overdue (more than 30 days since last payment)
     */
    public function isPaymentOverdue(): bool
    {
        if (!$this->last_payment_date) {
            // If no payment recorded, check if more than 30 days since start
            return $this->start_date->diffInDays(now()) > 30;
        }
        
        return $this->last_payment_date->diffInDays(now()) > 30;
    }

    /**
     * Get days since last payment
     */
    public function daysSinceLastPayment(): int
    {
        if (!$this->last_payment_date) {
            return $this->start_date->diffInDays(now());
        }
        
        return $this->last_payment_date->diffInDays(now());
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColor(): string
    {
        return match($this->getPaymentStatus()) {
            'deposit' => 'info',
            'paid' => 'success',
            'unpaid' => 'warning',
            'terminated' => 'danger',
            default => 'secondary',
        };
    }
}
