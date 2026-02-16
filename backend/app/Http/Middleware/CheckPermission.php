<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user() || !$request->user()->hasPermission($permission)) {
            // If user is Admin, they might bypass checks, but RBAC strictly checks permission slug. 
            // Often Admins have all permissions assigned via Seeder, so hasPermission('users.view') is true.
            
            // Optional: Super Admin Bypass
            // if ($request->user()->role === 'admin') return $next($request);

            return response()->json([
                'message' => 'Unauthorized. You do not have the required permission: ' . $permission
            ], 403);
        }

        return $next($request);
    }
}
