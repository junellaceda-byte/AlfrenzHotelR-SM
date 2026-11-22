<?php

namespace Tests;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authenticate test session as Administrator.
     */
    protected function actingAsAdmin(?User $admin = null): self
    {
        $admin = $admin ?? User::first() ?? User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'user_id' => 'admin',
            'email' => 'admin@hotel.com',
            'password' => Hash::make('admin123'),
        ]);

        $this->withSession([
            'admin_id' => $admin->id,
            'role' => 'admin',
            'user_id' => $admin->username,
        ]);

        return $this;
    }

    /**
     * Authenticate test session as Receptionist.
     */
    protected function actingAsReceptionist(?Staff $staff = null): self
    {
        $staff = $staff ?? Staff::first() ?? Staff::create([
            'first_name' => 'Default',
            'last_name' => 'Receptionist',
            'username' => 'receptionist',
            'email' => 'receptionist@hotel.com',
            'password' => Hash::make('password123'),
        ]);

        $this->withSession([
            'staff_id' => $staff->id,
            'staff_name' => trim($staff->first_name.' '.$staff->last_name),
            'staff_username' => $staff->username,
            'role' => 'receptionist',
        ]);

        return $this;
    }
}
