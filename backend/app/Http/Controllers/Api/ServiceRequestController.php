<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\ServiceOffer;
use App\Services\BadgeService;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    protected $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
    }
    /**
     * List applications/requests for the client.
     * Includes applications to their offers AND direct invitations they sent.
     */
    public function clientIndex(Request $request)
    {
        $user = $request->user();
        
        $requests = ServiceRequest::with(['serviceOffer.category', 'provider', 'creator'])
            ->where(function ($query) use ($user) {
                $query->whereHas('serviceOffer', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->orWhere('created_by_id', $user->id);
            })
            ->latest()
            ->paginate(15);

        // Mark as read for the client (where they are either offer owner or invited? No, simply if they see them)
        // Actually, only mark as read if they didn't create it? No, client sees applications to their offers.
        ServiceRequest::whereHas('serviceOffer', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('created_by_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($requests);
    }

    /**
     * Get unread notifications counts.
     */
    public function unreadCounts(Request $request)
    {
        $user = $request->user();
        
        $candidaturesCount = ServiceRequest::whereHas('serviceOffer', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('created_by_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();

        // Providers also get notifications for direct invites
        $invitationsCount = ServiceRequest::where('user_id', $user->id)
            ->where('created_by_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'candidatures_count' => $candidaturesCount,
            'invitations_count' => $invitationsCount
        ]);
    }

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
        $offer = ServiceOffer::findOrFail($request->service_offer_id);

        // Block check
        if ($offer->user->isBlockedBy($user->id) || $user->isBlockedBy($offer->user_id)) {
            return response()->json(['message' => 'Action impossible avec cet utilisateur'], 403);
        }

        // Check if already applied
        $existing = ServiceRequest::where('service_offer_id', $request->service_offer_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Vous avez déjà postulé à cette offre'], 400);
        }

        // Automated Availability Check (US-P04/US-P06)
        if ($offer->desired_date) {
            $date = \Carbon\Carbon::parse($offer->desired_date);
            $dayOfWeek = strtolower($date->format('l')); // e.g., 'monday'
            
            $availabilities = $user->prestataire?->availabilities ?? [];
            $dayAvailability = $availabilities[$dayOfWeek] ?? null;

            if (!$dayAvailability || ($dayAvailability['active'] ?? false) === false) {
                return response()->json([
                    'message' => "Vous n'êtes pas marqué comme disponible le " . $date->translatedFormat('l d F') . ". Veuillez mettre à jour votre calendrier dans votre profil."
                ], 422);
            }
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
     * Client invites a provider for a specific offer.
     */
    public function invite(Request $request)
    {
        $request->validate([
            'service_offer_id' => 'required|exists:service_offers,id',
            'provider_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
        ]);

        $user = $request->user();
        $provider = \App\Models\User::findOrFail($request->provider_id);

        if ($provider->isBlockedBy($user->id) || $user->isBlockedBy($provider->id)) {
            return response()->json(['message' => 'Action impossible avec cet utilisateur'], 403);
        }

        // Ensure the offer belongs to the client
        $offer = ServiceOffer::where('id', $request->service_offer_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $serviceRequest = ServiceRequest::create([
            'service_offer_id' => $request->service_offer_id,
            'user_id' => $request->provider_id,
            'created_by_id' => $user->id,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Invitation envoyée avec succès',
            'data' => $serviceRequest
        ]);
    }

    /**
     * List current user's applications (as provider).
     */
    public function providerIndex(Request $request)
    {
        $user = $request->user();
        $requests = ServiceRequest::with(['serviceOffer.user', 'serviceOffer.category', 'creator'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        // Mark invitations as read
        ServiceRequest::where('user_id', $user->id)
            ->where('created_by_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($requests);
    }

    /**
     * Update status (Accept/Reject/Complete/Cancel).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected,completed,cancelled'
        ]);

        $serviceRequest = ServiceRequest::with('serviceOffer')->findOrFail($id);
        $this->authorize('updateStatus', [$serviceRequest, $request->status]);

        $serviceRequest->update(['status' => $request->status]);

        // Side effects
        if ($request->status === 'accepted') {
            // When an application is accepted, the offer status changes to in_progress
            $serviceRequest->serviceOffer->update(['status' => 'in_progress']);
            
            // Optionally: reject all other pending applications for this offer? 
            // Let's keep it simple for now and allow multiple if needed, or user can manually reject.
        }

        if ($request->status === 'completed' || $request->status === 'cancelled') {
            // If mission is finished or cancelled, but no other mission is active, 
            // we could revert offer to open? Or just keep it as is.
            // Usually, if a mission starts, the offer is "filled".
            if ($request->status === 'completed') {
                $serviceRequest->serviceOffer->update(['status' => 'completed']);
                
                // Trigger Badge Sync
                $prestataire = \App\Models\Prestataire::where('user_id', $serviceRequest->user_id)->first();
                if ($prestataire) {
                    $this->badgeService->syncBadges($prestataire);
                }
            }
        }

        return response()->json([
            'message' => 'Statut mis à jour',
            'data' => $serviceRequest->load(['serviceOffer', 'provider', 'creator'])
        ]);
    }
}
