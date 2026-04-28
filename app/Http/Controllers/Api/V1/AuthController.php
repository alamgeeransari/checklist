<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = auth()->factory()->getTTL();
        $jwt = auth()->attempt($credentials);

        return response()->json([
            'access_token' => $jwt,
            'token_type' => 'bearer',
            'expires_in' => $token * 60,
        ]);
    }

    public function refresh(): JsonResponse
    {
        return response()->json([
            'access_token' => auth()->refresh(),
            'token_type' => 'bearer',
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $payload['email'])->firstOrFail();
        $otp = (string) random_int(100000, 999999);

        EmailOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
        ]);

        // In production, dispatch queued mailable/notification here.
        return response()->json([
            'message' => 'OTP sent successfully.',
            'debug_otp' => app()->environment('local') ? $otp : Str::mask($otp, '*', 0, 4),
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $payload['email'])->firstOrFail();

        $record = EmailOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest('id')
            ->firstOrFail();

        if ($record->expires_at->isPast()) {
            return response()->json(['message' => 'OTP expired.'], 422);
        }

        if (! Hash::check($payload['otp'], $record->otp_hash)) {
            $record->increment('attempts');

            return response()->json(['message' => 'Invalid OTP.'], 422);
        }

        $record->update(['verified_at' => now()]);

        return response()->json(['message' => 'OTP verified successfully.']);
    }
}
