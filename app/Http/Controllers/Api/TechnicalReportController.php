<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TechnicalReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TechnicalReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $reports = TechnicalReport::where('admin_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $reports,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:bug_report,system_error,feature_request,technical_issue',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $report = TechnicalReport::create([
            'admin_id' => $user->id,
            'type' => $request->type,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority ?? 'medium',
            'status' => 'open',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Technical report submitted to Super Admin successfully.',
            'data' => $report,
        ], 201);
    }
}
