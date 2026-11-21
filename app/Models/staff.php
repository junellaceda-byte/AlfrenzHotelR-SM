<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
