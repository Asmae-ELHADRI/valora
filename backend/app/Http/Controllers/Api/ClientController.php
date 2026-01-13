<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'type' => 'nullable|string|in:individual,company',
        ]);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        $client = $user->client;
        if (!$client) {
            $client = Client::create(['user_id' => $user->id]);
        }

        $client->update([
            'type' => $request->type,
        ]);

        return response()->json([
            'message' => 'Profil client mis à jour avec succès',
            'user' => $user->load('client'),
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            $client = Client::create(['user_id' => $user->id]);
        }

        if ($request->hasFile('photo')) {
            if ($client->photo) {
                Storage::delete($client->photo);
            }
            $path = $request->file('photo')->store('client_photos', 'public');
            $client->update(['photo' => $path]);
        }

        return response()->json([
            'message' => 'Logo/Photo mis à jour',
            'photo_url' => Storage::url($client->photo),
        ]);
    }
}
