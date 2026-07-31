<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'Your account is ' . $user->status . '. Please contact support.',
            ], 403);
        }

        $userRole = $user->role ? $user->role->name : 'user';

        if (!in_array($userRole, $roles)) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: You do not have permission to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
