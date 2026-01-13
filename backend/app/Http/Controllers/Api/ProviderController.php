<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Prestataire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProviderController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'skills' => 'nullable|string',
            'description' => 'nullable|string',
            'experience' => 'nullable|string',
            'diplomas' => 'nullable|string',
        ]);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        $prestataire = $user->prestataire;
        if (!$prestataire) {
            $prestataire = Prestataire::create(['user_id' => $user->id]);
        }

        $prestataire->update([
            'skills' => $request->skills,
            'description' => $request->description,
            'experience' => $request->experience,
            'diplomas' => $request->diplomas,
        ]);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user->load('prestataire'),
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();
        $prestataire = $user->prestataire;

        if ($request->hasFile('photo')) {
            if ($prestataire->photo) {
                Storage::delete($prestataire->photo);
            }
            $path = $request->file('photo')->store('profile_photos', 'public');
            $prestataire->update(['photo' => $path]);
        }

        return response()->json([
            'message' => 'Photo de profil mise à jour',
            'photo_url' => Storage::url($prestataire->photo),
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $request->validate([
            'availabilities' => 'required|array',
        ]);

        $user = $request->user();
        $user->prestataire->update([
            'availabilities' => $request->availabilities,
        ]);

        return response()->json([
            'message' => 'Disponibilités mises à jour',
        ]);
    }

    public function toggleVisibility(Request $request)
    {
        $user = $request->user();
        $user->prestataire->update([
            'is_visible' => !$user->prestataire->is_visible,
        ]);

        return response()->json([
            'message' => 'Statut de visibilité mis à jour',
            'is_visible' => $user->prestataire->is_visible,
        ]);
    }
}
