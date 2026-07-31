<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTrialStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin() && $user->restaurant) {
            $restaurant = $user->restaurant;

            // Check if trial is expired
            if ($restaurant->is_trial_expired) {
                if ($restaurant->account_status !== 'expired') {
                    $restaurant->update([
                        'account_status' => 'expired',
                        'plan_status' => 'inactive',
                    ]);
                }

                // Allowed routes for expired trial owners
                $allowedPaths = [
                    'api/subscription/plans',
                    'api/subscription/upgrade',
                    'api/auth/me',
                    'api/auth/update-profile',
                    'api/auth/logout',
                    'api/auth/change-password',
                ];

                $currentPath = $request->path();

                $isAllowed = false;
                foreach ($allowedPaths as $path) {
                    if (str_contains($currentPath, $path)) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (!$isAllowed) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Your free trial has expired. Please upgrade your subscription.',
                        'is_trial_expired' => true,
                        'allowed_features' => [
                            'View Subscription Plans',
                            'Upgrade Plan',
                            'View Profile',
                            'Logout'
                        ],
                        'disabled_features' => [
                            'Dashboard Management',
                            'Category Management',
                            'Menu Management',
                            'Menu Availability',
                            'Generate QR Code',
                            'Download QR Code',
                            'Restaurant Settings',
                            'Profile Customization',
                            'Analytics',
                            'Staff Management',
                            'All CRUD Operations'
                        ]
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
