<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return response()->json(['status' => false, 'message' => 'No restaurant associated with this account.'], 404);
        }

        $images = GalleryImage::where('restaurant_id', $restaurant->id)->get();

        return response()->json([
            'status' => true,
            'data' => $images,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return response()->json(['status' => false, 'message' => 'No restaurant associated with this account.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image_url' => 'required|string',
            'title' => 'nullable|string',
            'album_name' => 'nullable|string',
            'type' => 'nullable|in:food,restaurant',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $image = GalleryImage::create([
            'restaurant_id' => $restaurant->id,
            'image_url' => $request->image_url,
            'title' => $request->title ?? 'Gallery Photo',
            'album_name' => $request->album_name ?? 'General',
            'type' => $request->type ?? 'food',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Image uploaded to gallery successfully.',
            'data' => $image,
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return response()->json(['status' => false, 'message' => 'No restaurant associated with this account.'], 404);
        }

        $image = GalleryImage::where('restaurant_id', $restaurant->id)->findOrFail($id);

        $image->delete();

        return response()->json([
            'status' => true,
            'message' => 'Gallery image deleted.',
        ]);
    }
}
