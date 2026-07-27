<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SmsQueue;
use Illuminate\Support\Facades\DB;

class SmsGatewayController extends Controller
{
    // Texting Tablets will call this endpoint every few seconds
    public function fetchPending(Request $request)
    {
        $deviceId = $request->header('X-DEVICE-ID');

        // We use a database transaction to prevent two tablets from grabbing the same message
        return DB::transaction(function () use ($deviceId) {
            
            // Find the oldest pending SMS and lock the row for updating
            $pendingSms = SmsQueue::where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$pendingSms) {
                return response()->json(['success' => false, 'message' => 'Queue empty']);
            }

            // Immediately mark it as processing so other tablets ignore it
            $pendingSms->update([
                'status' => 'processing',
                'device_id' => $deviceId
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'queue_id' => $pendingSms->id,
                    'phone_number' => $pendingSms->phone_number,
                    'message' => $pendingSms->message,
                ]
            ]);
        });
    }

    // Texting Tablets will call this endpoint after the hardware confirms the SMS was sent
    public function updateStatus(Request $request)
    {
        $request->validate([
            'queue_id' => 'required|exists:sms_queues,id',
            'status' => 'required|in:sent,failed'
        ]);

        $sms = SmsQueue::find($request->queue_id);
        
        $sms->update([
            'status' => $request->status
        ]);

        return response()->json(['success' => true]);
    }
}