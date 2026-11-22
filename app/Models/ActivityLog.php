<?php

namespace App\Models;

use App\Services\SystemFormatService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'staff_id',
        'activity_type',
        'action',
        'subject_type',
        'subject_id',
        'guest_name',
        'room_number',
        'booking_type',
        'amount',
        'payment_method',
        'reference_number',
        'description',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to the receptionist/staff member responsible for the activity.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Polymorphic relation to the underlying model (Booking, Reservation, PaymentLog, etc.)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Human-readable actor name (Receptionist full name or "System Generated").
     */
    public function getActorNameAttribute(): string
    {
        if ($this->staff) {
            return trim($this->staff->first_name.' '.$this->staff->last_name);
        }

        return 'System Generated';
    }

    /**
     * Actor username if performed by a staff member.
     */
    public function getActorUsernameAttribute(): ?string
    {
        return $this->staff?->username;
    }

    /**
     * Formatted date using system-configured date format.
     */
    public function getFormattedDateAttribute(): string
    {
        return SystemFormatService::formatDate($this->created_at);
    }

    /**
     * Formatted time using system-configured time format.
     */
    public function getFormattedTimeAttribute(): string
    {
        return SystemFormatService::formatTime($this->created_at);
    }

    /**
     * Formatted datetime string.
     */
    public function getFormattedDateTimeAttribute(): string
    {
        return SystemFormatService::formatDateTime($this->created_at);
    }

    /**
     * Badge configuration for UI display.
     */
    public function getTypeBadgeAttribute(): array
    {
        return match ($this->activity_type) {
            'payment' => [
                'label' => 'Payment',
                'class' => 'bg-success text-white',
                'icon' => 'credit-card',
            ],
            'cancellation' => [
                'label' => 'Cancellation',
                'class' => 'bg-danger text-white',
                'icon' => 'x-circle',
            ],
            'overdue' => [
                'label' => 'Overdue',
                'class' => 'bg-warning text-dark',
                'icon' => 'exclamation-triangle',
            ],
            'login' => [
                'label' => 'Login',
                'class' => 'bg-secondary text-white',
                'icon' => 'person-check',
            ],
            'booking' => [
                'label' => 'Booking',
                'class' => 'bg-primary text-white',
                'icon' => 'calendar-check',
            ],
            default => [
                'label' => ucfirst($this->activity_type),
                'class' => 'bg-dark text-white',
                'icon' => 'activity',
            ],
        };
    }

    /**
     * Scope to filter by activity type (payment, cancellation, overdue, login, booking).
     */
    public function scopeForType(Builder $query, ?string $type): Builder
    {
        if (empty($type) || strtolower($type) === 'all' || strtolower($type) === 'all activity') {
            return $query;
        }

        $type = strtolower(trim($type));

        // Map singular/plural variants
        $mapped = match ($type) {
            'payments', 'payment' => 'payment',
            'cancellations', 'cancellation' => 'cancellation',
            'overdues', 'overdue' => 'overdue',
            'logins', 'login' => 'login',
            'bookings', 'booking' => 'booking',
            default => $type,
        };

        return $query->where('activity_type', $mapped);
    }

    /**
     * Scope to filter by responsible user/who (staff_id, 'system', or 'receptionist').
     */
    public function scopeForWho(Builder $query, ?string $who): Builder
    {
        if (empty($who) || strtolower($who) === 'all' || strtolower($who) === 'everyone') {
            return $query;
        }

        $whoLower = strtolower(trim($who));

        if ($whoLower === 'system' || $whoLower === 'system-generated' || $whoLower === 'system generated') {
            return $query->whereNull('staff_id');
        }

        if ($whoLower === 'receptionist' || $whoLower === 'receptionists') {
            return $query->whereNotNull('staff_id');
        }

        if (is_numeric($who)) {
            return $query->where('staff_id', (int) $who);
        }

        // Match staff username
        return $query->whereHas('staff', function (Builder $staffQuery) use ($who) {
            $staffQuery->where('username', $who)
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$who}%"]);
        });
    }

    /**
     * Scope to filter by date (Asia/Manila).
     */
    public function scopeForDate(Builder $query, ?string $date): Builder
    {
        if (empty($date)) {
            return $query;
        }

        try {
            $parsedDate = Carbon::parse($date, 'Asia/Manila')->toDateString();

            return $query->whereDate('created_at', $parsedDate);
        } catch (\Throwable $e) {
            return $query;
        }
    }

    /**
     * Scope to search by guest, room, transaction, username, etc.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('guest_name', 'like', "%{$term}%")
                ->orWhere('room_number', 'like', "%{$term}%")
                ->orWhere('reference_number', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('action', 'like', "%{$term}%")
                ->orWhere('booking_type', 'like', "%{$term}%")
                ->orWhereHas('staff', function (Builder $staffQuery) use ($term) {
                    $staffQuery->where('username', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                });
        });
    }
}
