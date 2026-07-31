<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MenuItemController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return response()->json(['status' => false, 'message' => 'No restaurant found'], 404);
        }

        $query = MenuItem::where('restaurant_id', $restaurant->id)->with('category');

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Category & Subcategory Filter
        if ($request->filled('category_id')) {
            $catId = (int) $request->category_id;
            
            $getChildren = function ($parentIds) use (&$getChildren, $restaurant) {
                $childIds = Category::where('restaurant_id', $restaurant->id)
                    ->whereIn('parent_id', $parentIds)
                    ->pluck('id')
                    ->toArray();
                if (!empty($childIds)) {
                    return array_merge($childIds, $getChildren($childIds));
                }
                return [];
            };

            $allCategoryIds = array_unique(array_merge([$catId], $getChildren([$catId])));
            $query->whereIn('category_id', $allCategoryIds);
        }

        // Food Type Filter
        if ($request->filled('food_type')) {
            $query->where('food_type', $request->food_type);
        }

        // Veg Filter Legacy Compatibility
        if ($request->has('is_veg') && $request->is_veg !== '' && $request->is_veg !== null) {
            $query->where('is_veg', filter_var($request->is_veg, FILTER_VALIDATE_BOOLEAN));
        }

        // Availability Filter
        if ($request->has('is_available') && $request->is_available !== '' && $request->is_available !== null) {
            $query->where('is_available', filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN));
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'newest');
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'price_low_high':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high_low':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $items = $query->get();

        return response()->json([
            'status' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'image' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lte:price',
            'preparation_time' => 'nullable|integer',
            'is_veg' => 'nullable|boolean',
            'food_type' => 'nullable|string|in:Veg,Non Veg,Vegan',
            'spice_level' => 'nullable|string|in:Mild,Medium,Hot',
            'spicy_level' => 'nullable|integer|min:0|max:3',
            'badges' => 'nullable|array',
            'always_available' => 'nullable|boolean',
            'available_start_time' => 'nullable|string',
            'available_end_time' => 'nullable|string',
            'is_available' => 'nullable|boolean',
            'status' => 'nullable|string|in:Active,Inactive,Out Of Stock',
            'sizes' => 'nullable|array',
            'addons' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $foodType = $request->food_type ?? ($request->is_veg ? 'Veg' : 'Non Veg');

        $item = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(4),
            'image' => $request->image,
            'description' => $request->description,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'preparation_time' => $request->preparation_time ?? 15,
            'is_veg' => $foodType === 'Veg' || $foodType === 'Vegan',
            'food_type' => $foodType,
            'spicy_level' => $request->spicy_level ?? 0,
            'spice_level' => $request->spice_level ?? 'Mild',
            'always_available' => $request->input('always_available', true),
            'available_start_time' => $request->available_start_time,
            'available_end_time' => $request->available_end_time,
            'is_available' => $request->input('is_available', true),
            'is_bestseller' => is_array($request->badges) && in_array('Best Seller', $request->badges),
            'is_recommended' => is_array($request->badges) && in_array('Recommended', $request->badges),
            'badges' => $request->badges ?? [],
            'status' => $request->status ?? 'Active',
            'sizes' => $request->sizes ?? [],
            'addons' => $request->addons ?? [],
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'role_name' => 'admin',
            'action' => 'Created Menu Item: ' . $item->name,
            'entity_type' => 'MenuItem',
            'entity_id' => $item->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Menu item created successfully.',
            'data' => $item->load('category'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;
        $item = MenuItem::where('restaurant_id', $restaurant->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'image' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lte:price',
            'preparation_time' => 'nullable|integer',
            'is_veg' => 'nullable|boolean',
            'food_type' => 'nullable|string|in:Veg,Non Veg,Vegan',
            'spice_level' => 'nullable|string|in:Mild,Medium,Hot',
            'badges' => 'nullable|array',
            'always_available' => 'nullable|boolean',
            'available_start_time' => 'nullable|string',
            'available_end_time' => 'nullable|string',
            'is_available' => 'nullable|boolean',
            'status' => 'nullable|string|in:Active,Inactive,Out Of Stock',
            'sizes' => 'nullable|array',
            'addons' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $foodType = $request->food_type ?? $item->food_type;

        $item->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'image' => $request->image ?? $item->image,
            'description' => $request->description,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'preparation_time' => $request->preparation_time ?? $item->preparation_time,
            'is_veg' => $foodType === 'Veg' || $foodType === 'Vegan',
            'food_type' => $foodType,
            'spice_level' => $request->spice_level ?? $item->spice_level,
            'always_available' => $request->input('always_available', $item->always_available),
            'available_start_time' => $request->available_start_time ?? $item->available_start_time,
            'available_end_time' => $request->available_end_time ?? $item->available_end_time,
            'is_available' => $request->input('is_available', $item->is_available),
            'is_bestseller' => is_array($request->badges) ? in_array('Best Seller', $request->badges) : $item->is_bestseller,
            'is_recommended' => is_array($request->badges) ? in_array('Recommended', $request->badges) : $item->is_recommended,
            'badges' => $request->badges ?? $item->badges,
            'status' => $request->status ?? $item->status,
            'sizes' => $request->sizes ?? $item->sizes,
            'addons' => $request->addons ?? $item->addons,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'role_name' => 'admin',
            'action' => 'Updated Menu Item: ' . $item->name,
            'entity_type' => 'MenuItem',
            'entity_id' => $item->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Menu item updated successfully.',
            'data' => $item->load('category'),
        ]);
    }

    public function duplicate(Request $request, $id)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;
        $original = MenuItem::where('restaurant_id', $restaurant->id)->findOrFail($id);

        $newItem = $original->replicate();
        $newItem->name = $original->name . ' (Copy)';
        $newItem->slug = Str::slug($newItem->name) . '-' . Str::random(4);
        $newItem->save();

        AuditLog::create([
            'user_id' => $user->id,
            'role_name' => 'admin',
            'action' => 'Duplicated Menu Item: ' . $original->name,
            'entity_type' => 'MenuItem',
            'entity_id' => $newItem->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Menu item duplicated successfully.',
            'data' => $newItem->load('category'),
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;
        $item = MenuItem::where('restaurant_id', $restaurant->id)->findOrFail($id);
        $name = $item->name;

        $item->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'role_name' => 'admin',
            'action' => 'Deleted Menu Item: ' . $name,
            'entity_type' => 'MenuItem',
            'entity_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Menu item deleted successfully.',
        ]);
    }

    // Bulk Operations
    public function bulkDelete(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:menu_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        MenuItem::where('restaurant_id', $restaurant->id)->whereIn('id', $request->ids)->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'role_name' => 'admin',
            'action' => 'Bulk Deleted Menu Items (' . count($request->ids) . ' items)',
            'entity_type' => 'MenuItem',
        ]);

        return response()->json([
            'status' => true,
            'message' => count($request->ids) . ' menu items deleted successfully.',
        ]);
    }

    public function bulkAvailability(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'is_available' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        MenuItem::where('restaurant_id', $restaurant->id)
            ->whereIn('id', $request->ids)
            ->update(['is_available' => $request->is_available]);

        return response()->json([
            'status' => true,
            'message' => 'Availability updated for selected items.',
        ]);
    }

    public function bulkStatus(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'status' => 'required|string|in:Active,Inactive,Out Of Stock',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $isAvailable = $request->status === 'Active';

        MenuItem::where('restaurant_id', $restaurant->id)
            ->whereIn('id', $request->ids)
            ->update([
                'status' => $request->status,
                'is_available' => $isAvailable
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Status updated for selected items.',
        ]);
    }
}
