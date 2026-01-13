<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\ServiceOffer;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    /**
     * Provider applies to an offer.
     */
    public function apply(Request $request)
    {
        $request->validate([
            'service_offer_id' => 'required|exists:service_offers,id',
            'message' => 'nullable|string',
        ]);

        $user = $request->user();

        // Check if already applied
        $existing = ServiceRequest::where('service_offer_id', $request->service_offer_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Vous avez déjà postulé à cette offre'], 400);
        }

        $serviceRequest = ServiceRequest::create([
            'service_offer_id' => $request->service_offer_id,
            'user_id' => $user->id,
            'created_by_id' => $user->id,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Candidature envoyée avec succès',
            'data' => $serviceRequest
        ]);
    }

    /**
     * List current user's applications (as provider).
     */
    public function providerIndex(Request $request)
    {
        $user = $request->user();
        $requests = ServiceRequest::with(['serviceOffer.user', 'serviceOffer.category'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json($requests);
    }

    /**
     * Update status (Accept/Reject for provider if client invited them, or Finish).
     * In this basic version, we'll allow status updates.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected,completed'
        ]);

        $user = $request->user();
        $serviceRequest = ServiceRequest::findOrFail($id);

        // Authorization: logic depends on who can change status
        // For now, let's keep it simple: the provider can accept/reject if they were invited,
        // or the client can accept/reject if the provider applied.
        // And either can complete if it's accepted.
        
        $serviceRequest->update(['status' => $request->status]);

        // If completed, maybe update the offer status too
        if ($request->status === 'completed') {
            $serviceRequest->serviceOffer->update(['status' => 'completed']);
        }

        return response()->json([
            'message' => 'Statut mis à jour',
            'data' => $serviceRequest
        ]);
    }
}
