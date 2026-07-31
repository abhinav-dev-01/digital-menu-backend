<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $favorites = Favorite::where('user_id', $user->id)
            ->with(['menuItem.category', 'menuItem.restaurant'])
            ->get();

        return response()->json([
            'status' => true,
            'data' => $favorites,
        ]);
    }

    public function toggle(Request $request, $menuItemId)
    {
        $user = $request->user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('menu_item_id', $menuItemId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json([
                'status' => true,
                'is_favorite' => false,
                'message' => 'Removed from favorites.',
            ]);
        }

        $fav = Favorite::create([
            'user_id' => $user->id,
            'menu_item_id' => $menuItemId,
        ]);

        return response()->json([
            'status' => true,
            'is_favorite' => true,
            'message' => 'Added to favorites.',
            'data' => $fav->load('menuItem'),
        ], 201);
    }
}
