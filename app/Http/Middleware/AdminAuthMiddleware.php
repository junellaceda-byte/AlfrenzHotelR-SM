<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Enforces Administrator role authentication and 15-minute inactivity session timeout.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = session('role');
        $adminId = session('admin_id');

        if (! $adminId || $role !== 'admin') {
            if ($role === 'receptionist') {
                return redirect('/dashboardreceptionist')->with('error', 'Unauthorized access to Administrator area.');
            }

            return redirect('/login')->withErrors(['username' => 'Please log in as Administrator to access this page.']);
        }

        // 15-minute inactivity session timeout for Administrator
        $lastActivity = session('admin_last_activity') ?? session('last_activity');
        $now = now()->timestamp;

        if ($lastActivity && ($now - $lastActivity) > (15 * 60)) {
            session()->flush();

            return redirect('/login')->with('timeout', 'Your session has expired due to 15 minutes of inactivity. Please log in again.');
        }

        session([
            'admin_last_activity' => $now,
            'last_activity' => $now,
        ]);

        return $next($request);
    }
}
