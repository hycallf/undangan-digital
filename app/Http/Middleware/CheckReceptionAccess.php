<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckReceptionAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userRole = $request->user()->role;
        // Izinkan jika rolenya 'admin' ATAU 'resepsionis'
        if ($userRole === 'admin' || $userRole === 'resepsionis') {
            return $next($request);
        }
        abort(403, 'AKSES DITOLAK.');
    }
}
