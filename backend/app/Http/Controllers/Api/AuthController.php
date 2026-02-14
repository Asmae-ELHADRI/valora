<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Prestataire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:provider,client',
            // Prestataire specific fields
            'city' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric',
            'category_id' => 'nullable|exists:service_categories,id',
            'experience' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
        ]);

        if ($request->role === 'provider') {
            \App\Models\Prestataire::create([
                'user_id' => $user->id,
                'city' => $request->city,
                'hourly_rate' => $request->hourly_rate,
                'category_id' => $request->category_id,
                'experience' => $request->experience,
                'birth_date' => $request->birth_date,
                'description' => $request->description,
            ]);
        } else {
            \App\Models\Client::create([
                'user_id' => $user->id,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load(['prestataire', 'client']),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            \App\Models\ActivityLog::create([
                'action' => 'login_failed',
                'description' => "Tentative de connexion échouée pour l'email: " . $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if (!$user->is_active) {
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'login_failed',
                'description' => 'Tentative de connexion sur un compte désactivé.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Votre compte a été désactivé par un administrateur.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load(['prestataire', 'client']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        
        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'logout',
            'description' => 'Déconnexion réussie',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load(['prestataire', 'client']));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update([
            'password' => $request->password,
        ]);

        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'update_password',
            'description' => 'Mot de passe modifié',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        
        // Revoke tokens
        $user->tokens()->delete();
        
        // Delete user (cascade will handle prestataire/client if set in DB, 
        // but we'll do it explicitly if needed. In migrations we used onDelete('cascade'))
        $user->delete();

        return response()->json([
            'message' => 'Compte supprimé avec succès',
        ]);
    }

    public function getBasicInfo($id)
    {
        $user = User::with(['prestataire', 'client'])->findOrFail($id);
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'prestataire' => $user->prestataire, // Include specific data if needed
            'client' => $user->client,
        ]);
    }
}
