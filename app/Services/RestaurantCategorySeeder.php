<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

/**
 * Automatically seeds the full restaurant category hierarchy.
 * Called on restaurant creation (both via Super Admin and self-registration).
 */
class RestaurantCategorySeeder
{
    /**
     * Full default category hierarchy for any restaurant.
     * Unlimited nesting via 'children' arrays.
     */
    private static function hierarchy(): array
    {
        return [
            ['name' => 'Hot', 'icon' => '🔥', 'children' => [
                ['name' => 'Hot Drinks', 'icon' => '☕', 'children' => [
                    'Tea', 'Coffee', 'Hot Chocolate', 'Herbal Drinks',
                ]],
                ['name' => 'Soup', 'icon' => '🍲', 'children' => [
                    'Veg Soup', 'Chicken Soup', 'Seafood Soup', 'Mushroom Soup',
                ]],
                ['name' => 'Hot Snacks', 'icon' => '🍟', 'children' => [
                    'Veg Snacks', 'Chicken Snacks', 'Seafood Snacks',
                ]],
            ]],

            ['name' => 'Cool', 'icon' => '❄️', 'children' => [
                ['name' => 'Cold Drinks', 'icon' => '🥤', 'children' => [
                    'Fresh Juice', 'Milk Shake', 'Smoothies', 'Mojito',
                    'Mocktail', 'Cocktail', 'Soft Drinks', 'Energy Drinks',
                    'Iced Coffee', 'Iced Tea',
                ]],
                ['name' => 'Ice Cream', 'icon' => '🍦', 'children' => [
                    'Cup', 'Cone', 'Sundae', 'Family Pack',
                ]],
                ['name' => 'Cold Desserts', 'icon' => '🍮', 'children' => [
                    'Pudding', 'Mousse', 'Fruit Bowl',
                ]],
            ]],

            ['name' => 'Food', 'icon' => '🍽️', 'children' => [
                ['name' => 'Breakfast', 'icon' => '🌅', 'children' => [
                    'Kerala Breakfast', 'South Indian', 'North Indian', 'Continental',
                ]],
                ['name' => 'Meals', 'icon' => '🍱', 'children' => [
                    'Veg Meals', 'Chicken Meals', 'Fish Meals', 'Beef Meals',
                ]],
                ['name' => 'Rice', 'icon' => '🍚', 'children' => [
                    'Fried Rice', 'Ghee Rice', 'Jeera Rice', 'Steam Rice',
                ]],
                ['name' => 'Biriyani', 'icon' => '🍛', 'children' => [
                    'Chicken', 'Beef', 'Mutton', 'Fish', 'Prawn', 'Veg',
                ]],
                ['name' => 'Chinese', 'icon' => '🥢', 'children' => [
                    'Noodles', 'Fried Rice', 'Manchurian', 'Chilli', 'Schezwan',
                ]],
                ['name' => 'Arabic', 'icon' => '🥙', 'children' => [
                    'Al Faham', 'Shawarma', 'Mandi', 'Kabsa', 'Broasted Chicken',
                ]],
                ['name' => 'Seafood', 'icon' => '🦐', 'children' => [
                    'Fish', 'Prawns', 'Squid', 'Crab',
                ]],
                ['name' => 'Pizza', 'icon' => '🍕', 'children' => [
                    'Veg Pizza', 'Chicken Pizza', 'Cheese Pizza',
                ]],
                ['name' => 'Burger', 'icon' => '🍔', 'children' => [
                    'Veg Burger', 'Chicken Burger', 'Beef Burger',
                ]],
                ['name' => 'Sandwich', 'icon' => '🥪', 'children' => []],
                ['name' => 'Pasta', 'icon' => '🍝', 'children' => [
                    'White Sauce', 'Red Sauce', 'Alfredo', 'Arrabbiata',
                ]],
                ['name' => 'Wraps', 'icon' => '🌯', 'children' => []],
            ]],

            ['name' => 'Snacks', 'icon' => '🍿', 'children' => [
                ['name' => 'Kerala Snacks', 'icon' => '🫓', 'children' => [
                    'Pazhampori', 'Parippu Vada', 'Uzhunnu Vada', 'Unniyappam',
                ]],
                ['name' => 'Fast Food', 'icon' => '🍟', 'children' => [
                    'French Fries', 'Sandwich', 'Wrap', 'Roll',
                ]],
                ['name' => 'Fried Items', 'icon' => '🧆', 'children' => []],
                ['name' => 'Rolls', 'icon' => '🌯', 'children' => []],
                ['name' => 'Sandwiches', 'icon' => '🥪', 'children' => []],
            ]],

            ['name' => 'Bakery', 'icon' => '🧁', 'children' => [
                ['name' => 'Cakes', 'icon' => '🎂', 'children' => [
                    'Chocolate Cake', 'Red Velvet', 'Black Forest',
                ]],
                ['name' => 'Pastries', 'icon' => '🥐', 'children' => [
                    'Chocolate', 'Vanilla', 'Fruit',
                ]],
                ['name' => 'Cookies', 'icon' => '🍪', 'children' => [
                    'Butter', 'Chocolate', 'Oats',
                ]],
                ['name' => 'Muffins', 'icon' => '🧁', 'children' => []],
                ['name' => 'Bread', 'icon' => '🍞', 'children' => []],
                ['name' => 'Puffs', 'icon' => '🥐', 'children' => [
                    'Veg Puff', 'Egg Puff', 'Chicken Puff',
                ]],
            ]],

            ['name' => 'Desserts', 'icon' => '🍰', 'children' => [
                ['name' => 'Ice Cream', 'icon' => '🍦', 'children' => [
                    'Cup', 'Cone', 'Sundae',
                ]],
                ['name' => 'Traditional', 'icon' => '🍯', 'children' => [
                    'Payasam', 'Gulab Jamun', 'Halwa', 'Rasmalai',
                ]],
                ['name' => 'Pudding', 'icon' => '🍮', 'children' => []],
                ['name' => 'Brownie', 'icon' => '🍫', 'children' => []],
                ['name' => 'Cheesecake', 'icon' => '🎂', 'children' => []],
                ['name' => 'Cakes', 'icon' => '🎂', 'children' => []],
            ]],

            ['name' => 'Combo Meals', 'icon' => '🎁', 'children' => [
                'Breakfast Combo', 'Lunch Combo', 'Dinner Combo',
                'Family Combo', 'Couple Combo', 'Kids Combo',
            ]],

            ['name' => 'Special Menu', 'icon' => '⭐', 'children' => [
                "Chef Special", "Today's Special", 'Weekend Special',
                'Festival Special', 'Seasonal Menu', 'Bestseller',
                'Recommended', 'New Arrival',
            ]],
        ];
    }

    /**
     * Seed the full hierarchy for a given restaurant.
     *
     * @param int $restaurantId
     * @return void
     */
    public static function seedForRestaurant(int $restaurantId): void
    {
        $order = 0;
        self::createTree($restaurantId, self::hierarchy(), null, 0, $order);
    }

    /**
     * Recursively create categories.
     */
    private static function createTree(int $restaurantId, array $items, ?int $parentId, int $level, int &$order): void
    {
        foreach ($items as $item) {
            $order++;

            if (is_string($item)) {
                // Leaf node without children
                $name = $item;
                $icon = '';
                $children = [];
            } else {
                $name     = $item['name'];
                $icon     = $item['icon'] ?? '';
                $children = $item['children'] ?? [];
            }

            $baseSlug = Str::slug($name);
            // Ensure unique slug within restaurant
            $existing = Category::where('restaurant_id', $restaurantId)
                ->where('slug', 'like', $baseSlug . '%')
                ->count();
            $slug = $existing > 0 ? $baseSlug . '-' . ($existing + 1) : $baseSlug;

            $category = Category::create([
                'restaurant_id' => $restaurantId,
                'parent_id'     => $parentId,
                'name'          => $name,
                'slug'          => $slug,
                'icon'          => $icon,
                'level'         => $level,
                'display_order' => $order,
                'is_active'     => true,
            ]);

            if (!empty($children)) {
                $childOrder = 0;
                self::createTree($restaurantId, $children, $category->id, $level + 1, $childOrder);
            }
        }
    }
}
