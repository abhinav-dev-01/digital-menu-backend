<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\AuditLog;

class SubscriptionController extends Controller
{
    public function getPlans()
    {
        $plans = [
            [
                'id' => 'starter',
                'name' => 'Starter',
                'price' => 0,
                'billing' => 'monthly',
                'description' => 'Ideal for single cafes and food trucks.',
                'features' => [
                    '1 Restaurant Location',
                    'Unlimited Categories & Menu Items',
                    'Table QR Code Generator',
                    'Customer Ratings & Reviews'
                ]
            ],
            [
                'id' => 'business',
                'name' => 'Business',
                'price' => 49,
                'billing' => 'monthly',
                'is_popular' => true,
                'description' => 'Perfect for growing restaurants & bistros.',
                'features' => [
                    'Everything in Starter',
                    'Time-Based Availability Schedules',
                    'Restaurant Photo Gallery',
                    'Advanced Analytics Reports',
                    'Priority 24/7 Support'
                ]
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'price' => 99,
                'billing' => 'monthly',
                'description' => 'For multi-branch chains & restaurant groups.',
                'features' => [
                    'Unlimited Restaurant Locations',
                    'White Label Branding',
                    'Custom Domain Support',
                    'Dedicated Account Manager'
                ]
            ]
        ];

        return response()->json([
            'status' => true,
            'data' => $plans,
        ]);
    }

    public function upgrade(Request $request)
    {
        $user = $request->user();

        if (!$user->restaurant) {
            return response()->json([
                'status' => false,
                'message' => 'No restaurant associated with this account.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'plan_name' => 'required|string|in:Starter,Business,Enterprise',
            'payment_method' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $restaurant = $user->restaurant;
        $restaurant->update([
            'account_status' => 'active',
            'plan_name' => $request->plan_name,
            'plan_status' => 'active',
            'status' => 'active',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'role_name' => 'admin',
            'action' => "Upgraded Subscription to {$request->plan_name}",
            'entity_type' => 'Restaurant',
            'entity_id' => $restaurant->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Welcome back! Your restaurant dashboard has been reactivated.',
            'data' => [
                'accountStatus' => 'Active',
                'planStatus' => 'Paid',
                'planName' => $request->plan_name,
                'unlockAllFeatures' => true,
                'restorePreviousData' => true,
                'restaurant' => $restaurant->fresh(),
            ]
        ]);
    }
}
