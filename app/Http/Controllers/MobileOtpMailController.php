<?php

namespace App\Http\Controllers;

use App\Mail\MobileLoginOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MobileOtpMailController extends Controller
{
    // public function send(Request $request)
    // {
    //     $expectedSecret = (string) config('services.mobile_otp_mail.secret');
    //     $providedSecret = (string) $request->header('X-Internal-Secret', 'otp_bridge_92#Ksl@12');

    //     if ($expectedSecret === '' || ! hash_equals($expectedSecret, $providedSecret)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthorized mail bridge request.',
    //         ], 401);
    //     }

    //     $validated = $request->validate([
    //         'email' => 'required|email',
    //         'otp' => 'required|string|min:4|max:10',
    //         'role' => 'required|string|max:50',
    //         'purpose' => 'nullable|string|max:100',
    //     ]);

    //     Mail::to($validated['email'])->send(new MobileLoginOtpMail(
    //         $validated['otp'],
    //         $validated['role'],
    //         (string) ($validated['purpose'] ?? 'mobile-login')
    //     ));

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'OTP email queued successfully.',
    //     ]);
    // }

     public function send(Request $request)
    {
        // Step 2a: Get secret from config
        $expectedSecret = config('services.mobile_otp_mail.secret', '');

        // Step 2b: Get header from request
        $providedSecret = $request->header('X-Internal-Secret', '');

        // Step 2c: Reject if secret missing or doesn't match
        if (empty($providedSecret) || ! hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized mail bridge request.',
            ], 401);
        }

        // Step 2d: Validate request body
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|min:4|max:10',
            'role' => 'required|string|max:50',
            'purpose' => 'nullable|string|max:100',
        ]);

        // Step 2e: Send OTP email
        Mail::to($validated['email'])->send(new MobileLoginOtpMail(
            $validated['otp'],
            $validated['role'],
            $validated['purpose'] ?? 'mobile-login'
        ));

        return response()->json([
            'success' => true,
            'message' => 'OTP email queued successfully.',
        ]);
    }
}
