<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Flat list with optional search / status filter.
     */
    public function index(Request $request)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return response()->json(['status' => false, 'message' => 'No restaurant found'], 404);
        }

        $query = Category::where('restaurant_id', $restaurant->id)
            ->withCount('menuItems')
            ->with('parent');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id === 'null' ? null : $request->parent_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $categories = $query->orderBy('level', 'asc')->orderBy('display_order', 'asc')->get();

        return response()->json(['status' => true, 'data' => $categories]);
    }

    /**
     * Show single category.
     */
    public function show(Request $request, $id)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return response()->json(['status' => false, 'message' => 'No restaurant found'], 404);
        }

        $category = Category::where('restaurant_id', $restaurant->id)
            ->with(['parent', 'allChildren'])
            ->withCount('menuItems')
            ->findOrFail($id);

        return response()->json(['status' => true, 'data' => $category]);
    }

    /**
     * Full nested tree (recursive).
     */
    public function tree(Request $request)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return response()->json(['status' => false, 'message' => 'No restaurant found'], 404);
        }

        $roots = Category::where('restaurant_id', $restaurant->id)
            ->whereNull('parent_id')
            ->with('allChildren')
            ->withCount('menuItems')
            ->orderBy('display_order', 'asc')
            ->get();

        return response()->json(['status' => true, 'data' => $roots]);
    }

    /**
     * Create category (any level).
     */
    public function store(Request $request)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return response()->json(['status' => false, 'message' => 'No restaurant found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'parent_id'     => 'nullable|integer|exists:categories,id',
            'description'   => 'nullable|string',
            'image'         => 'nullable|string',
            'icon'          => 'nullable|string',
            'display_order' => 'nullable|integer',
            'is_active'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Calculate level
        $level = 0;
        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);
            $level  = $parent ? ($parent->level + 1) : 0;
        }

        // Auto display_order
        $maxOrder = Category::where('restaurant_id', $restaurant->id)
            ->where('parent_id', $request->parent_id ?? null)
            ->max('display_order');

        $slug = Str::slug($request->name);
        // Make slug unique within restaurant
        $slugCount = Category::where('restaurant_id', $restaurant->id)
            ->where('slug', 'like', $slug . '%')
            ->count();
        if ($slugCount > 0) {
            $slug = $slug . '-' . ($slugCount + 1);
        }

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'parent_id'     => $request->parent_id,
            'name'          => $request->name,
            'slug'          => $slug,
            'description'   => $request->description,
            'image'         => $request->image,
            'icon'          => $request->icon,
            'level'         => $level,
            'display_order' => $request->display_order ?? ($maxOrder + 1),
            'is_active'     => $request->input('is_active', true),
        ]);

        AuditLog::create([
            'user_id'     => $user->id,
            'role_name'   => 'admin',
            'action'      => 'Created Category: ' . $category->name . ' (Level ' . $level . ')',
            'entity_type' => 'Category',
            'entity_id'   => $category->id,
            'payload'     => ['parent_id' => $request->parent_id],
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Category created successfully.',
            'data'    => $category->load('parent'),
        ], 201);
    }

    /**
     * Update a category.
     */
    public function update(Request $request, $id)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;
        $category   = Category::where('restaurant_id', $restaurant->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'parent_id'     => 'nullable|integer|exists:categories,id',
            'description'   => 'nullable|string',
            'image'         => 'nullable|string',
            'icon'          => 'nullable|string',
            'display_order' => 'nullable|integer',
            'is_active'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Recalculate level if parent changed
        $level = $category->level;
        if ($request->has('parent_id')) {
            if ($request->parent_id) {
                $parent = Category::find($request->parent_id);
                $level  = $parent ? ($parent->level + 1) : 0;
            } else {
                $level = 0;
            }
        }

        $category->update([
            'parent_id'     => $request->input('parent_id', $category->parent_id),
            'name'          => $request->name,
            'slug'          => Str::slug($request->name),
            'description'   => $request->description,
            'image'         => $request->input('image', $category->image),
            'icon'          => $request->input('icon', $category->icon),
            'level'         => $level,
            'display_order' => $request->input('display_order', $category->display_order),
            'is_active'     => $request->input('is_active', $category->is_active),
        ]);

        AuditLog::create([
            'user_id'     => $user->id,
            'role_name'   => 'admin',
            'action'      => 'Updated Category: ' . $category->name,
            'entity_type' => 'Category',
            'entity_id'   => $category->id,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Category updated successfully.',
            'data'    => $category->fresh('parent'),
        ]);
    }

    /**
     * Soft delete a category.
     */
    public function destroy(Request $request, $id)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;
        $category   = Category::where('restaurant_id', $restaurant->id)->findOrFail($id);
        $name       = $category->name;
        $category->delete(); // soft delete

        AuditLog::create([
            'user_id'     => $user->id,
            'role_name'   => 'admin',
            'action'      => 'Deleted Category: ' . $name,
            'entity_type' => 'Category',
            'entity_id'   => $id,
        ]);

        return response()->json(['status' => true, 'message' => 'Category deleted successfully.']);
    }

    /**
     * Restore soft-deleted category.
     */
    public function restore(Request $request, $id)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;
        $category   = Category::onlyTrashed()
            ->where('restaurant_id', $restaurant->id)
            ->findOrFail($id);
        $category->restore();

        return response()->json(['status' => true, 'message' => 'Category restored successfully.', 'data' => $category]);
    }

    /**
     * Bulk update display_order for drag-and-drop sorting.
     */
    public function updateOrder(Request $request)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;

        $validator = Validator::make($request->all(), [
            'items'               => 'required|array',
            'items.*.id'          => 'required|integer',
            'items.*.display_order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        foreach ($request->items as $item) {
            Category::where('restaurant_id', $restaurant->id)
                ->where('id', $item['id'])
                ->update(['display_order' => $item['display_order']]);
        }

        return response()->json(['status' => true, 'message' => 'Display order updated.']);
    }

    /**
     * Bulk status update.
     */
    public function bulkStatus(Request $request)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;

        $validator = Validator::make($request->all(), [
            'ids'       => 'required|array',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        Category::where('restaurant_id', $restaurant->id)
            ->whereIn('id', $request->ids)
            ->update(['is_active' => $request->is_active]);

        return response()->json(['status' => true, 'message' => 'Bulk status updated.']);
    }

    /**
     * Bulk delete.
     */
    public function bulkDelete(Request $request)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        Category::where('restaurant_id', $restaurant->id)
            ->whereIn('id', $request->ids)
            ->delete();

        return response()->json(['status' => true, 'message' => 'Bulk delete completed.']);
    }

    /**
     * Stats for dashboard.
     */
    public function stats(Request $request)
    {
        $user       = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'total' => 0, 'active' => 0, 'inactive' => 0, 'trashed' => 0,
                    'by_level' => ['level_0' => 0, 'level_1' => 0, 'level_2' => 0, 'level_3_plus' => 0],
                ],
            ]);
        }

        $rid = $restaurant->id;

        return response()->json([
            'status' => true,
            'data'   => [
                'total'    => Category::where('restaurant_id', $rid)->count(),
                'active'   => Category::where('restaurant_id', $rid)->where('is_active', true)->count(),
                'inactive' => Category::where('restaurant_id', $rid)->where('is_active', false)->count(),
                'trashed'  => Category::withTrashed()->where('restaurant_id', $rid)->whereNotNull('deleted_at')->count(),
                'by_level' => [
                    'level_0'     => Category::where('restaurant_id', $rid)->where('level', 0)->count(),
                    'level_1'     => Category::where('restaurant_id', $rid)->where('level', 1)->count(),
                    'level_2'     => Category::where('restaurant_id', $rid)->where('level', 2)->count(),
                    'level_3_plus'=> Category::where('restaurant_id', $rid)->where('level', '>=', 3)->count(),
                ],
            ],
        ]);
    }
}
