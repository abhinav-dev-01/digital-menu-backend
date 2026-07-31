<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        return response()->json([
            'status' => true,
            'data' => $restaurant,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'owner_name' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'google_map_url' => 'nullable|string',
            'working_hours' => 'nullable|array',
            'social_links' => 'nullable|array',
            'about' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $restaurant->update($request->only([
            'name', 'logo', 'cover_image', 'owner_name', 'contact_number',
            'email', 'address', 'google_map_url', 'working_hours', 'social_links', 'about'
        ]));

        AuditLog::create([
            'user_id' => $user->id,
            'role_name' => 'admin',
            'action' => 'Updated Restaurant Profile Information',
            'entity_type' => 'Restaurant',
            'entity_id' => $restaurant->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Restaurant profile updated successfully.',
            'data' => $restaurant->fresh(),
        ]);
    }
}
