<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Prestataire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $query = User::role('provider')
            ->whereHas('prestataire', function ($q) {
                $q->where('is_visible', true);
            })
            ->with(['prestataire.category']);

        // Filter by keyword (name, description, skills)
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhereHas('prestataire', function ($pq) use ($keyword) {
                        $pq->where('skills', 'like', "%{$keyword}%")
                            ->orWhere('description', 'like', "%{$keyword}%");
                    });
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('prestataire', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Filter by location
        if ($request->has('location')) {
            $query->where('address', 'like', "%{$request->location}%");
        }

        // Filter by rating
        if ($request->has('min_rating')) {
            $query->whereHas('prestataire', function ($q) use ($request) {
                $q->where('rating', '>=', $request->min_rating);
            });
        }

        $providers = $query->paginate(12);

        return response()->json($providers);
    }

    public function show($id)
    {
        $provider = User::role('provider')
            ->with(['prestataire.category', 'receivedReviews.reviewer'])
            ->findOrFail($id);

        return response()->json($provider);
    }

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
            'category_id' => 'nullable|exists:service_categories,id',
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
            'category_id' => $request->category_id,
        ]);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user->load('prestataire.category'),
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
