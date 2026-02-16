<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class PreventAdminReport
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to report creation requests
        if ($request->isMethod('post') && str_contains($request->path(), 'reports')) {
            $blockedUserId = $request->input('blocked_user_id');
            
            if ($blockedUserId) {
                $targetUser = User::find($blockedUserId);
                
                if ($targetUser && $targetUser->role === 'admin') {
                    // Log the attempt for security audit
                    Log::warning('Attempted admin report blocked', [
                        'reporter_id' => auth()->id(),
                        'reporter_email' => auth()->user()?->email,
                        'attempted_target_id' => $blockedUserId,
                        'ip' => $request->ip()
                    ]);
                    
                    return response()->json([
                        'message' => 'Vous ne pouvez pas signaler un administrateur.',
                        'error' => 'ADMIN_REPORT_FORBIDDEN'
                    ], 403);
                }
            }
        }
        
        return $next($request);
    }
}
