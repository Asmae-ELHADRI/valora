<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prestataire;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'provider') {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $prestataire = $user->prestataire;
        if (!$prestataire || !$prestataire->is_certified) {
            return response()->json(['message' => 'Vous n\'êtes pas encore éligible au certificat officiel'], 403);
        }

        return response()->json([
            'prestataire' => $prestataire->load(['user', 'category']),
            'certified_at' => $prestataire->certified_at ?? $prestataire->created_at, // Fallback if not specifically timestamped
            'certificate_id' => 'VAL-' . str_pad($prestataire->id, 6, '0', STR_PAD_LEFT),
        ]);
    }
}
