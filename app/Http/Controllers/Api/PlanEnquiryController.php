<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanEnquiryController extends Controller
{
    /**
     * Handle Customer Plan Enquiry submission
     * Stores enquiry in MySQL database and generates secure WhatsApp URL from .env
     */
    public function store(Request $request)
    {
        $data = $request->isJson() ? $request->json()->all() : $request->all();

        $validator = Validator::make($data, [
            'restaurant_name' => 'required|string|max:255',
            'owner_name'      => 'required|string|max:255',
            'phone'           => 'required|string|max:30',
            'email'           => 'required|email|max:255',
            'city'            => 'required|string|max:255',
            'selected_plan'   => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'success' => false,
                'errors'  => $validator->errors(),
                'message' => 'Please fill in all required fields correctly.'
            ], 422);
        }

        // 1. Insert enquiry into plan_enquiries table
        $enquiry = PlanEnquiry::create([
            'restaurant_name' => trim($data['restaurant_name']),
            'owner_name'      => trim($data['owner_name']),
            'phone'           => trim($data['phone']),
            'email'           => trim($data['email']),
            'city'            => trim($data['city']),
            'selected_plan'   => trim($data['selected_plan']),
            'status'          => 'Pending',
        ]);

        // 2. Read WhatsApp number from config/services.php (loaded from .env)
        $whatsappNumber = config('services.whatsapp.number', '919778033362');
        // Sanitize phone number (strip non-digit characters)
        $whatsappNumber = preg_replace('/[^0-9]/', '', $whatsappNumber);

        // 3. Generate WhatsApp message template
        $messageTemplate = "Hello Digital menu team,\n\n"
            . "I am interested in the {$enquiry->selected_plan} Plan.\n\n"
            . "Restaurant Name: {$enquiry->restaurant_name}\n"
            . "Owner Name: {$enquiry->owner_name}\n"
            . "Mobile Number: {$enquiry->phone}\n"
            . "Email Address: {$enquiry->email}\n"
            . "City: {$enquiry->city}\n\n"
            . "Please contact me regarding the subscription.\n\n"
            . "Thank You.";

        // 4. Encode message & generate WhatsApp URL
        $encodedMessage = rawurlencode($messageTemplate);
        $whatsappUrl = "https://wa.me/{$whatsappNumber}?text={$encodedMessage}";

        // 5. Return generated URL to frontend
        return response()->json([
            'status'       => true,
            'success'      => true,
            'message'      => 'Plan enquiry recorded successfully.',
            'whatsapp_url' => $whatsappUrl,
            'data'         => $enquiry,
        ], 201);
    }

    /**
     * List all Plan Enquiries (for Super Admin dashboard)
     */
    public function index(Request $request)
    {
        $query = PlanEnquiry::query();

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function($q) use ($s) {
                $q->where('restaurant_name', 'like', "%{$s}%")
                  ->orWhere('owner_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $enquiries = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'data'   => $enquiries,
        ]);
    }

    /**
     * Update Enquiry status
     */
    public function updateStatus(Request $request, $id)
    {
        $enquiry = PlanEnquiry::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Pending,Contacted,Converted,Cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $enquiry->update(['status' => $request->status]);

        return response()->json([
            'status'  => true,
            'message' => 'Plan enquiry status updated successfully.',
            'data'    => $enquiry,
        ]);
    }
}
