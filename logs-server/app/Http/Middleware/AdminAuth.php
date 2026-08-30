<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Staff;

class AdminAuth
{
    /**
     * Handle an incoming request.
     * Supports both Admin and Staff Sanctum tokens
     */
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - No token provided',
            ], 401);
        }

        $token = substr($authHeader, 7);

        // Check if it's a Sanctum token (admin or staff)
        $sanctumToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if ($sanctumToken) {
            $user = $sanctumToken->tokenable;
            
            // Check if it's an Admin
            if ($user && $user instanceof Admin) {
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });
                $request->attributes->set('admin_type', 'admin');
                $request->attributes->set('user_role', 'admin');
                return $next($request);
            }
            
            // Check if it's a Staff
            if ($user && $user instanceof Staff) {
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });
                $request->attributes->set('admin_type', 'staff');
                $request->attributes->set('user_role', 'staff');
                return $next($request);
            }
        }

        // Invalid token
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized - Invalid or expired token',
        ], 401);
    }
}
