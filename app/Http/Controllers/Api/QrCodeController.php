<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrCodeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $qrCodes = QrCode::where('restaurant_id', $restaurant->id)->get();

        return response()->json([
            'status' => true,
            'data' => $qrCodes,
        ]);
    }

    public function generate(Request $request)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $codeHash = 'QR-' . strtoupper(Str::random(8));
        $targetUrl = "http://localhost:5173/menu/" . $restaurant->slug;

        $qrCode = QrCode::create([
            'restaurant_id' => $restaurant->id,
            'code_hash' => $codeHash,
            'target_url' => $targetUrl,
            'total_scans' => 0,
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'role_name' => 'admin',
            'action' => 'Generated Dynamic QR Code: ' . $codeHash,
            'entity_type' => 'QrCode',
            'entity_id' => $qrCode->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'New dynamic QR code generated successfully.',
            'data' => $qrCode,
        ], 201);
    }

    public function regenerate(Request $request, $id)
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        $qrCode = QrCode::where('restaurant_id', $restaurant->id)->findOrFail($id);
        $newHash = 'QR-' . strtoupper(Str::random(8));

        $qrCode->update([
            'code_hash' => $newHash,
            'target_url' => "http://localhost:5173/menu/" . $restaurant->slug,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'role_name' => 'admin',
            'action' => 'Regenerated QR Code: ' . $newHash,
            'entity_type' => 'QrCode',
            'entity_id' => $qrCode->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'QR Code regenerated successfully.',
            'data' => $qrCode,
        ]);
    }
}
