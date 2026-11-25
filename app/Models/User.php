<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'user_id',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Ensure username and user_id stay synced.
     */
    public function getUsernameAttribute(?string $value): string
    {
        return $value ?? $this->attributes['user_id'] ?? 'admin';
    }

    public function setUsernameAttribute(?string $value): void
    {
        $this->attributes['username'] = $value;
        if (empty($this->attributes['user_id'])) {
            $this->attributes['user_id'] = $value;
        }
    }
}
