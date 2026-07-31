<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\QrCode;
use App\Models\Review;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return response()->json([
                'status' => false,
                'message' => 'No restaurant associated with this admin account.',
            ], 404);
        }

        $totalCategories = Category::where('restaurant_id', $restaurant->id)->count();
        $totalItems      = MenuItem::where('restaurant_id', $restaurant->id)->count();
        $availableItems  = MenuItem::where('restaurant_id', $restaurant->id)->where('is_available', true)->count();
        $popularItems    = MenuItem::where('restaurant_id', $restaurant->id)->where('is_bestseller', true)->get();
        $totalQrScans    = QrCode::where('restaurant_id', $restaurant->id)->sum('total_scans');
        $activeCustomers = Review::where('restaurant_id', $restaurant->id)->distinct('user_id')->count();

        $scanTrends = [
            ['day' => 'Mon', 'scans' => 45],
            ['day' => 'Tue', 'scans' => 52],
            ['day' => 'Wed', 'scans' => 61],
            ['day' => 'Thu', 'scans' => 78],
            ['day' => 'Fri', 'scans' => 110],
            ['day' => 'Sat', 'scans' => 145],
            ['day' => 'Sun', 'scans' => 160],
        ];

        $recentActivity = AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'stats' => [
                    'qr_scans_today' => rand(40, 95),
                    'total_qr_scans' => $totalQrScans,
                    'active_customers' => $activeCustomers,
                    'total_categories' => $totalCategories,
                    'total_items' => $totalItems,
                    'available_items' => $availableItems,
                ],
                'popular_items' => $popularItems,
                'scan_trends' => $scanTrends,
                'recent_activity' => $recentActivity,
            ]
        ]);
    }
}
