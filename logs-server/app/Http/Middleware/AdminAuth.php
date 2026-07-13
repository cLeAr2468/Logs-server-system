<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Staff;

class AdminAuth
{
    /**
     * Handle an incoming request.
     * Supports both default admin token and Sanctum staff tokens
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

        // Check if it's the default admin token (Base64 encoded)
        $decoded = @base64_decode($token, true);
        if ($decoded && strpos($decoded, 'admin@nwssu.edu.ph') === 0) {
            // Valid default admin token
            // Set a pseudo-user for the request
            $request->attributes->set('admin_type', 'default');
            return $next($request);
        }

        // Check if it's a Sanctum token (staff)
        $sanctumToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if ($sanctumToken) {
            $staff = $sanctumToken->tokenable;
            
            if ($staff && $staff instanceof Staff) {
                // Valid staff token
                $request->setUserResolver(function () use ($staff) {
                    return $staff;
                });
                $request->attributes->set('admin_type', 'staff');
                return $next($request);
            }
        }

        // Invalid token
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized - Invalid token',
        ], 401);
    }
}
