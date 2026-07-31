<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySchedule;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $schedules = AvailabilitySchedule::where('restaurant_id', $restaurant->id)
            ->with('menuItem')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $schedules,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $validator = Validator::make($request->all(), [
            'schedule_type' => 'required|string',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'days_of_week' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $schedule = AvailabilitySchedule::create([
            'restaurant_id' => $restaurant->id,
            'menu_item_id' => $request->menu_item_id,
            'schedule_type' => $request->schedule_type,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'days_of_week' => $request->days_of_week ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'is_active' => $request->input('is_active', true),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Availability schedule created successfully.',
            'data' => $schedule->load('menuItem'),
        ], 201);
    }

    public function toggleSchedule(Request $request, $id)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;
        $schedule = AvailabilitySchedule::where('restaurant_id', $restaurant->id)->findOrFail($id);

        $schedule->update(['is_active' => !$schedule->is_active]);

        return response()->json([
            'status' => true,
            'message' => 'Schedule toggled successfully.',
            'data' => $schedule,
        ]);
    }
}
