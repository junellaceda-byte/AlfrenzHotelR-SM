<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReceptionistAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Enforces Receptionist role authentication and 30-minute inactivity session timeout.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = session('role');
        $staffId = session('staff_id');

        if (! $staffId || $role !== 'receptionist') {
            if ($role === 'admin') {
                return redirect('/admindashboard')->with('error', 'Unauthorized access to Receptionist area.');
            }

            return redirect('/login')->withErrors(['username' => 'Please log in as Receptionist to access this page.']);
        }

        // 30-minute inactivity session timeout for Receptionist
        $lastActivity = session('receptionist_last_activity') ?? session('last_activity');
        $now = now()->timestamp;

        if ($lastActivity && ($now - $lastActivity) > (30 * 60)) {
            session()->flush();

            return redirect('/login')->with('timeout', 'Your session has expired due to 30 minutes of inactivity. Please log in again.');
        }

        session([
            'receptionist_last_activity' => $now,
            'last_activity' => $now,
        ]);

        return $next($request);
    }
}
