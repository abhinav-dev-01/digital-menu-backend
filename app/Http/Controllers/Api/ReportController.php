<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\QrCode;
use App\Models\Review;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function analyticsReport(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $qrScans = QrCode::where('restaurant_id', $restaurant->id)->get();
        $popularItems = MenuItem::where('restaurant_id', $restaurant->id)
            ->where('is_bestseller', true)
            ->with('category')
            ->get();
        $reviewsSummary = Review::where('restaurant_id', $restaurant->id)->get();

        return response()->json([
            'status' => true,
            'data' => [
                'restaurant_name' => $restaurant->name,
                'total_qr_scans' => $qrScans->sum('total_scans'),
                'scans_by_code' => $qrScans,
                'popular_menu_items' => $popularItems,
                'total_reviews' => $reviewsSummary->count(),
                'average_rating' => round($reviewsSummary->avg('rating'), 2) ?: 5.0,
                'monthly_growth' => '+24.8%',
            ]
        ]);
    }
}
