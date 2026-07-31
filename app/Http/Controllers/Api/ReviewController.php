<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    // Admin list reviews for their restaurant
    public function index(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $query = Review::where('restaurant_id', $restaurant->id)->with(['user', 'menuItem']);

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reviews = $query->orderBy('created_at', 'desc')->get();

        $ratingOverview = [
            'average' => round($reviews->avg('rating'), 1) ?: 5.0,
            'total' => $reviews->count(),
            '5_star' => $reviews->where('rating', 5)->count(),
            '4_star' => $reviews->where('rating', 4)->count(),
            '3_star' => $reviews->where('rating', 3)->count(),
            '2_star' => $reviews->where('rating', 2)->count(),
            '1_star' => $reviews->where('rating', 1)->count(),
        ];

        return response()->json([
            'status' => true,
            'data' => [
                'rating_overview' => $ratingOverview,
                'reviews' => $reviews,
            ]
        ]);
    }

    // Admin reply to review
    public function reply(Request $request, $id)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;
        $review = Review::where('restaurant_id', $restaurant->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $review->update(['reply' => $request->reply]);

        return response()->json([
            'status' => true,
            'message' => 'Reply saved successfully.',
            'data' => $review->fresh(['user', 'menuItem']),
        ]);
    }

    // Admin toggle review status (visible/hidden)
    public function toggleStatus(Request $request, $id)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;
        $review = Review::where('restaurant_id', $restaurant->id)->findOrFail($id);

        $newStatus = $review->status === 'visible' ? 'hidden' : 'visible';
        $review->update(['status' => $newStatus]);

        return response()->json([
            'status' => true,
            'message' => 'Review status set to ' . $newStatus,
            'data' => $review,
        ]);
    }

    // Customer / Guest submit review
    public function storeCustomerReview(Request $request)
    {
        $user = $request->user('sanctum');

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,id',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
            'customer_name' => 'nullable|string',
            'guest_name' => 'nullable|string',
            'food_image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $userId = null;
        $customerName = $request->customer_name ?? $request->guest_name ?? null;

        if ($user) {
            $role = is_object($user->role) ? strtolower($user->role->name ?? '') : strtolower($user->role ?? '');
            if ($role === 'user' || $role === 'customer') {
                $userId = $user->id;
                if (empty($customerName)) {
                    $customerName = $user->name;
                }
            }
        }

        if (empty($customerName)) {
            $customerName = 'Anonymous';
        }

        $review = Review::create([
            'user_id' => $userId,
            'restaurant_id' => $request->restaurant_id,
            'menu_item_id' => $request->menu_item_id,
            'customer_name' => $customerName,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'food_image' => $request->food_image,
            'status' => 'visible',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Thank you for your review!',
            'data' => $review->load(['user', 'menuItem']),
        ], 201);
    }
}
