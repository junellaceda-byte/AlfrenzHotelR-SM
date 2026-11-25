<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class Staff extends Model
{
    use HasFactory;

    // Explicitly name the table to match your validation rules
    protected $table = 'staff';

    // Allow mass assignment for these fields
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'gmail',
        'contact_number',
        'username',
        'password',
        'is_online',
        'last_seen_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get the employee email address.
     */
    public function getEmailAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['gmail'] ?? null;
    }

    /**
     * Set the employee email address and keep gmail attribute synchronized.
     */
    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value;
        $this->attributes['gmail'] = $value;
    }

    /**
     * Get the employee gmail address (legacy compatibility).
     */
    public function getGmailAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['email'] ?? null;
    }

    /**
     * Set the employee gmail address and keep email attribute synchronized.
     */
    public function setGmailAttribute(?string $value): void
    {
        $this->attributes['gmail'] = $value;
        $this->attributes['email'] = $value;
    }

    /**
     * Get the decrypted plain text password.
     */
    public function getPasswordAttribute(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // Never expose bcrypt or argon hashes in plain text fields
            if (str_starts_with($value, '$2y$') || str_starts_with($value, '$2a$') || str_starts_with($value, '$2b$') || str_starts_with($value, '$argon2')) {
                return '';
            }

            return (string) $value;
        }
    }

    /**
     * Set and securely encrypt the password before storing in the database.
     */
    public function setPasswordAttribute(?string $value): void
    {
        if (empty($value)) {
            return;
        }

        $this->attributes['password'] = Crypt::encryptString($value);
    }

    /**
     * Determine if the staff member is currently online.
     */
    public function getIsOnlineAttribute($value): bool
    {
        if (! (bool) $value) {
            return false;
        }

        // Session timeout safety check (30 minutes)
        if ($this->last_seen_at && $this->last_seen_at->diffInMinutes(now()) > 30) {
            return false;
        }

        return true;
    }

    /**
     * Helper to check online status.
     */
    public function isOnline(): bool
    {
        return (bool) $this->is_online;
    }

    /**
     * Relationship to the staff member's activity logs.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'staff_id');
    }

    /**
     * Get the latest recorded activity for this staff member.
     */
    public function latestActivity(): HasOne
    {
        return $this->hasOne(ActivityLog::class, 'staff_id')->latestOfMany();
    }
}
