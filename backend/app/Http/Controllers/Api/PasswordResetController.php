<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Send reset link (mock version for MVP if mail not configured, 
     * but using standard Laravel structure).
     */
    public function forgotSubmit(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // We return success even if user not found for security
            return response()->json(['message' => 'Si cet email existe, un lien a été envoyé.']);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // In a real app, send email here. 
        // For development, we might log it or return it (for testing only).
        // \Log::info("Password reset token for {$request->email}: {$token}");

        return response()->json([
            'message' => 'Lien de réinitialisation envoyé par email.',
            // 'debug_token' => $token // Only for testing 
        ]);
    }

    /**
     * Reset password with token.
     */
    public function resetSubmit(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            throw ValidationException::withMessages([
                'email' => ['Le lien de réinitialisation est invalide ou a expiré.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Votre mot de passe a été réinitialisé avec succès.',
        ]);
    }
}
