<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\QrCode;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicMenuController extends Controller
{
    /**
     * Get Public Menu dynamically by Restaurant ID, Slug, or QR Code Token
     */
    public function getPublicMenu(Request $request, $identifier = null)
    {
        $target = $identifier 
            ?? $request->query('restaurant') 
            ?? $request->query('slug') 
            ?? $request->query('qr') 
            ?? $request->query('qr_token') 
            ?? $request->query('id');

        $restaurant = null;

        if ($target) {
            // 1. Resolve by QR code token/hash
            $qr = QrCode::where('code_hash', $target)->first();
            if ($qr) {
                $qr->increment('total_scans');
                $restaurant = Restaurant::where('id', $qr->restaurant_id)->first();
            }

            // 2. Resolve by Slug
            if (!$restaurant) {
                $restaurant = Restaurant::where('slug', $target)->first();
            }

            // 3. Resolve by ID if numeric
            if (!$restaurant && is_numeric($target)) {
                $restaurant = Restaurant::where('id', (int)$target)->first();
            }
        }

        // 4. Fallback: default to the first active or first available restaurant so /menu works seamlessly
        if (!$restaurant) {
            $restaurant = Restaurant::where('status', 'active')->first() ?? Restaurant::first();
        }

        if (!$restaurant) {
            return response()->json([
                'status' => false,
                'message' => 'No restaurant available.'
            ], 404);
        }

        // Increment scan count if explicit qr_code query parameter present
        if ($request->filled('qr_code') || $request->filled('qr')) {
            $qrCodeHash = $request->qr_code ?? $request->qr;
            QrCode::where('restaurant_id', $restaurant->id)
                ->where('code_hash', $qrCodeHash)
                ->increment('total_scans');
        }

        // All active categories
        $allCategories = Category::where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->orderBy('level', 'asc')
            ->orderBy('display_order', 'asc')
            ->get();

        // Root (Level 0) categories
        $rootCategories = Category::where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('parent_id')->orWhere('level', 0);
            })
            ->with('allChildren')
            ->orderBy('display_order', 'asc')
            ->get();

        $query = MenuItem::where('restaurant_id', $restaurant->id)->with('category');

        // Search Filter (Search item name, description, category name, ingredients & allergens)
        if ($request->has('search') && $request->search !== null && trim($request->search) !== '') {
            $s = trim($request->search);
            $driver = DB::connection()->getDriverName();
            $query->where(function($q) use ($s, $driver) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhereHas('category', function($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%");
                  });
                
                if ($driver === 'sqlite') {
                    $q->orWhere('ingredients', 'like', "%{$s}%")
                      ->orWhere('allergens', 'like', "%{$s}%");
                } elseif ($driver === 'pgsql') {
                    $q->orWhereRaw("CAST(ingredients AS TEXT) LIKE ?", ["%{$s}%"])
                      ->orWhereRaw("CAST(allergens AS TEXT) LIKE ?", ["%{$s}%"]);
                } else {
                    $q->orWhereRaw("CAST(ingredients AS CHAR) LIKE ?", ["%{$s}%"])
                      ->orWhereRaw("CAST(allergens AS CHAR) LIKE ?", ["%{$s}%"]);
                }
            });
        }

        // Category Filter with descendant categories inclusion
        if ($request->filled('category_id')) {
            $targetCatId = (int)$request->category_id;
            
            $getDescendants = function($parentId) use (&$getDescendants, $allCategories) {
                $ids = [$parentId];
                $children = $allCategories->where('parent_id', $parentId);
                foreach ($children as $child) {
                    $ids = array_merge($ids, $getDescendants($child->id));
                }
                return $ids;
            };

            $catIds = array_unique($getDescendants($targetCatId));
            $query->whereIn('category_id', $catIds);
        }

        // Veg / Non-Veg Filter
        if ($request->has('is_veg') && $request->is_veg !== '' && $request->is_veg !== 'null' && $request->is_veg !== null && $request->is_veg !== 'undefined') {
            $isVeg = filter_var($request->is_veg, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_veg', $isVeg);
        }

        // Available Items Only Filter
        if ($request->filled('is_available') && filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_available', true);
        }

        // Bestseller Filter
        if ($request->filled('is_bestseller') && filter_var($request->is_bestseller, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_bestseller', true);
        }

        // Recommended Filter
        if ($request->filled('is_recommended') && filter_var($request->is_recommended, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_recommended', true);
        }

        // Price & Prep Time Sorting
        if ($request->filled('sort_by')) {
            switch ($request->sort_by) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'prep_time':
                case 'prep_time_asc':
                    $query->orderBy('preparation_time', 'asc');
                    break;
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                default:
                    $query->orderBy('id', 'desc');
                    break;
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $items = $query->get();

        $bestsellers = MenuItem::where('restaurant_id', $restaurant->id)
            ->where('is_bestseller', true)
            ->where('is_available', true)
            ->take(8)
            ->get();

        $recommended = MenuItem::where('restaurant_id', $restaurant->id)
            ->where('is_recommended', true)
            ->where('is_available', true)
            ->take(8)
            ->get();

        $reviews = Review::where('restaurant_id', $restaurant->id)
            ->where('status', 'visible')
            ->with(['user', 'menuItem'])
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        $ratingAvg = round(Review::where('restaurant_id', $restaurant->id)->where('status', 'visible')->avg('rating'), 1) ?: 4.8;
        $totalReviews = Review::where('restaurant_id', $restaurant->id)->where('status', 'visible')->count();

        return response()->json([
            'status' => true,
            'data' => [
                'restaurant' => $restaurant,
                'rating_summary' => [
                    'average' => $ratingAvg,
                    'total_reviews' => $totalReviews,
                ],
                'categories' => $allCategories,
                'root_categories' => $rootCategories,
                'bestsellers' => $bestsellers,
                'recommended' => $recommended,
                'items' => $items,
                'reviews' => $reviews,
            ]
        ]);
    }

    public function getMenuBySlug(Request $request, $slug)
    {
        return $this->getPublicMenu($request, $slug);
    }

    public function getMenuItemDetails($restaurantSlug, $itemSlug)
    {
        $restaurant = Restaurant::where('slug', $restaurantSlug)
            ->orWhere('id', is_numeric($restaurantSlug) ? $restaurantSlug : null)
            ->firstOrFail();

        $item = MenuItem::where('restaurant_id', $restaurant->id)
            ->where(function($q) use ($itemSlug) {
                $q->where('slug', $itemSlug);
                if (is_numeric($itemSlug)) {
                    $q->orWhere('id', (int)$itemSlug);
                }
            })
            ->with(['category', 'images', 'reviews.user'])
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => $item,
        ]);
    }

    public function resolveQrCode($codeHash)
    {
        $qr = QrCode::with('restaurant')->where('code_hash', $codeHash)->firstOrFail();
        $qr->increment('total_scans');

        return response()->json([
            'status' => true,
            'data' => [
                'restaurant_slug' => $qr->restaurant->slug,
                'target_url' => $qr->target_url,
                'restaurant' => $qr->restaurant,
            ]
        ]);
    }
}

