<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        $user = $request->user();
        if (!$user || $user->role !== $role) {
            return response()->json([
                'message' => 'Forbidden. Admins only.'
            ], Response::HTTP_FORBIDDEN);
        }
        return $next($request);
    }
}
