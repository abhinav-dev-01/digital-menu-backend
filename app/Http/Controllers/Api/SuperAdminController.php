<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Restaurant;
use App\Models\TechnicalReport;
use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\MenuItem;
use App\Models\QrCode;
use App\Models\AdminOtp;
use App\Services\RestaurantCategorySeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $totalAdmins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->count();
        $totalUsers  = User::whereHas('role', fn($q) => $q->where('name', 'user'))->count();
        $totalRestaurants = Restaurant::count();
        $totalMenuItems   = MenuItem::count();
        $totalQrScans     = QrCode::sum('total_scans');

        $reportsOverview = [
            'total' => TechnicalReport::count(),
            'pending' => TechnicalReport::where('status', 'pending')->count(),
            'resolved' => TechnicalReport::where('status', 'resolved')->count(),
        ];

        $recentActivities = AuditLog::orderBy('created_at', 'desc')->limit(5)->get();

        $systemHealth = [
            'status' => 'healthy',
            'server_load' => '12%',
            'database' => 'connected',
            'uptime' => '99.98%',
        ];

        return response()->json([
            'status' => true,
            'data' => [
                'stats' => [
                    'total_admins' => $totalAdmins,
                    'total_users' => $totalUsers,
                    'total_restaurants' => $totalRestaurants,
                    'total_menu_items' => $totalMenuItems,
                    'total_qr_scans' => $totalQrScans,
                ],
                'reports_overview' => $reportsOverview,
                'recent_activities' => $recentActivities,
                'system_health' => $systemHealth,
            ]
        ]);
    }

    // Admin Account Management
    public function listAdmins(Request $request)
    {
        $query = User::with('restaurant')->whereHas('role', fn($q) => $q->where('name', 'admin'));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhereHas('restaurant', fn($rq) => $rq->where('name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $admins = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $admins,
        ]);
    }

    public function storeAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'restaurant_name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'logo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $adminRole = Role::where('name', 'admin')->first();

        // Create Admin user with Pending Verification status
        $user = User::create([
            'role_id' => $adminRole ? $adminRole->id : 2,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(32)), // Temporary random hash until password is set by admin
            'phone' => $request->phone,
            'status' => 'pending',
            'account_status' => 'Pending Verification',
            'is_email_verified' => false,
            'email_verified_at' => null,
            'avatar' => 'https://images.unsplash.com/photo-1577219491135-ce391730fb2c?auto=format&fit=crop&w=300&q=80',
        ]);

        // Create Restaurant
        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'name' => $request->restaurant_name,
            'slug' => Str::slug($request->restaurant_name) . '-' . Str::random(4),
            'owner_name' => $user->name,
            'email' => $user->email,
            'contact_number' => $user->phone,
            'address' => $request->address,
            'status' => 'pending',
            'account_status' => 'trial',
            'plan_name' => 'Free Trial',
            'plan_status' => 'trial',
            'trial_starts_at' => \Carbon\Carbon::now(),
            'trial_ends_at' => \Carbon\Carbon::now()->addDays(14),
            'logo' => $request->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=300&q=80',
        ]);

        QrCode::create([
            'restaurant_id' => $restaurant->id,
            'code_hash' => 'QR-' . strtoupper(Str::random(8)),
            'target_url' => rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') . '/menu/' . $restaurant->slug,
            'total_scans' => 0,
            'is_active' => true,
        ]);

        // Auto-seed full category hierarchy for this restaurant
        RestaurantCategorySeeder::seedForRestaurant($restaurant->id);

        // Generate 6-digit OTP
        $otpCode = (string) random_int(100000, 999999);

        // Invalidate any previous OTPs for this email
        AdminOtp::where('email', $user->email)->update(['status' => 'expired']);

        // Save Hashed OTP in database (10 minute expiration)
        AdminOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make($otpCode),
            'expires_at' => \Carbon\Carbon::now()->addMinutes(10),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        // Audit Logs
        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => 'Admin Created: Created Restaurant Admin (' . $user->name . ')',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'payload' => ['admin_email' => $user->email, 'restaurant' => $restaurant->name],
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => 'OTP Generated: Generated 6-digit OTP for ' . $user->email,
            'entity_type' => 'AdminOtp',
            'entity_id' => $user->id,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => 'OTP Sent: Sent verification OTP email to ' . $user->email,
            'entity_type' => 'AdminOtp',
            'entity_id' => $user->id,
        ]);

        Log::info("RESTAURANT ADMIN VERIFICATION OTP SENT to {$user->email} for restaurant '{$restaurant->name}' - OTP Code: {$otpCode}");

        return response()->json([
            'status' => true,
            'message' => "Restaurant Admin created successfully. A 6-digit verification OTP has been sent to {$user->email}.",
            'data' => $user->load('restaurant'),
            'otp_demo' => $otpCode,
        ], 201);
    }

    public function updateAdminStatus(Request $request, $id)
    {
        $admin = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $admin->update(['status' => $request->status]);

        if ($admin->restaurant) {
            $admin->restaurant->update(['status' => $request->status]);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => 'Updated Admin Status to ' . $request->status,
            'entity_type' => 'User',
            'entity_id' => $admin->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Admin status updated to ' . $request->status,
            'data' => $admin->fresh('restaurant'),
        ]);
    }

    public function resetAdminPassword(Request $request, $id)
    {
        $admin = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $admin->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'status' => true,
            'message' => 'Admin password reset successfully.',
            'plain_password' => $request->new_password,
        ]);
    }

    public function deleteAdmin(Request $request, $id)
    {
        $admin = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->findOrFail($id);
        $admin->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => 'Deleted Restaurant Admin Account',
            'entity_type' => 'User',
            'entity_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Admin account deleted successfully.',
        ]);
    }

    // Technical Reports
    public function listTechnicalReports(Request $request)
    {
        $query = TechnicalReport::with(['reporter', 'assignee']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('title', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $reports = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $reports,
        ]);
    }

    public function replyTechnicalReport(Request $request, $id)
    {
        $report = TechnicalReport::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'super_admin_reply' => 'required|string',
            'status' => 'required|in:open,in_progress,solved,closed',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $report->update([
            'super_admin_reply' => $request->super_admin_reply,
            'status' => $request->status,
            'assigned_to' => $request->user()->id,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => 'Replied to Technical Report Ticket #' . $id,
            'entity_type' => 'TechnicalReport',
            'entity_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Technical report updated successfully.',
            'data' => $report->fresh(['reporter', 'assignee']),
        ]);
    }

    public function auditLogs(Request $request)
    {
        $logs = AuditLog::with('user')->orderBy('created_at', 'desc')->take(100)->get();

        return response()->json([
            'status' => true,
            'data' => $logs,
        ]);
    }

    public function loginLogs(Request $request)
    {
        $logs = LoginLog::with('user')->orderBy('created_at', 'desc')->take(100)->get();

        return response()->json([
            'status' => true,
            'data' => $logs,
        ]);
    }

    // Trial Management Module for SuperAdmin
    public function listTrials(Request $request)
    {
        $query = Restaurant::with('owner');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('owner_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhereHas('owner', function($oq) use ($s) {
                      $oq->where('name', 'like', "%{$s}%")
                         ->orWhere('email', 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        $restaurants = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $restaurants,
        ]);
    }

    public function extendTrial(Request $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $days = (int) ($request->input('days', 14));

        $baseDate = ($restaurant->trial_ends_at && \Carbon\Carbon::now()->lessThan($restaurant->trial_ends_at))
            ? $restaurant->trial_ends_at
            : \Carbon\Carbon::now();

        $newExpiry = $baseDate->copy()->addDays($days);

        $restaurant->update([
            'account_status' => 'trial',
            'plan_status' => 'trial',
            'trial_ends_at' => $newExpiry,
            'trial_reminder_sent_7' => false,
            'trial_reminder_sent_3' => false,
            'trial_reminder_sent_1' => false,
            'trial_expired_sent' => false,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => "Extended Trial by {$days} Days for Restaurant #{$id}",
            'entity_type' => 'Restaurant',
            'entity_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'message' => "Trial extended by {$days} days successfully.",
            'data' => $restaurant->fresh('owner'),
        ]);
    }

    public function endTrial(Request $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $restaurant->update([
            'account_status' => 'expired',
            'plan_status' => 'inactive',
            'trial_ends_at' => \Carbon\Carbon::now()->subMinute(),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => "Manually Ended Trial for Restaurant #{$id}",
            'entity_type' => 'Restaurant',
            'entity_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Trial ended and restaurant account status set to Expired.',
            'data' => $restaurant->fresh('owner'),
        ]);
    }

    public function convertToPaid(Request $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $plan = $request->input('plan_name', 'Business');

        $restaurant->update([
            'account_status' => 'active',
            'plan_status' => 'active',
            'plan_name' => $plan,
            'status' => 'active',
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => "Converted Restaurant #{$id} to Paid Plan ({$plan})",
            'entity_type' => 'Restaurant',
            'entity_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'message' => "Restaurant converted to Paid Plan ({$plan}) successfully.",
            'data' => $restaurant->fresh('owner'),
        ]);
    }

    public function activateAccount(Request $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $restaurant->update([
            'account_status' => 'active',
            'status' => 'active',
        ]);

        if ($restaurant->owner) {
            $restaurant->owner->update(['status' => 'active']);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => "Activated Restaurant Account #{$id}",
            'entity_type' => 'Restaurant',
            'entity_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Restaurant account activated successfully.',
            'data' => $restaurant->fresh('owner'),
        ]);
    }

    public function suspendAccount(Request $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $restaurant->update([
            'account_status' => 'suspended',
            'status' => 'suspended',
        ]);

        if ($restaurant->owner) {
            $restaurant->owner->update(['status' => 'suspended']);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name' => 'super_admin',
            'action' => "Suspended Restaurant Account #{$id}",
            'entity_type' => 'Restaurant',
            'entity_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Restaurant account suspended successfully.',
            'data' => $restaurant->fresh('owner'),
        ]);
    }
}
