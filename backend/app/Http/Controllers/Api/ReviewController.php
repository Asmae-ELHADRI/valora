<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get reviews for the current authenticated user (assuming provider).
     */
    public function providerReviews(Request $request)
    {
        $userId = $request->user()->id;

        $reviews = Review::with(['reviewer:id,name', 'serviceOffer:id,title'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $averageRating = Review::where('user_id', $userId)->avg('rating') ?: 0;
        
        return response()->json([
            'reviews' => $reviews,
            'average_rating' => round($averageRating, 1),
            'total_reviews' => $reviews->count()
        ]);
    }

    /**
     * Get reviews left by the current authenticated user (client).
     */
    public function clientReviews(Request $request)
    {
        $userId = $request->user()->id;

        $reviews = Review::with(['reviewedUser:id,name', 'serviceOffer:id,title'])
            ->where('reviewer_id', $userId)
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    /**
     * Store a new review (typically called by a client).
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'service_offer_id' => 'required|exists:service_offers,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        // Check if already reviewed for this offer
        $exists = Review::where('reviewer_id', $request->user()->id)
            ->where('service_offer_id', $request->service_offer_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Vous avez déjà évalué cette mission'], 422);
        }

        $review = Review::create([
            'user_id' => $request->user_id,
            'reviewer_id' => $request->user()->id,
            'service_offer_id' => $request->service_offer_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $this->updateProviderRating($request->user_id);

        return response()->json([
            'message' => 'Évaluation enregistrée avec succès',
            'data' => $review
        ]);
    }

    /**
     * Update an existing review.
     */
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        if ($review->reviewer_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // 24 hour limit for edits
        if ($review->created_at->addDay()->isPast()) {
            return response()->json(['message' => 'La période de modification (24h) est expirée'], 422);
        }

        $request->validate([
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $review->update($request->only(['rating', 'comment']));

        $this->updateProviderRating($review->user_id);

        return response()->json([
            'message' => 'Évaluation mise à jour',
            'data' => $review
        ]);
    }

    /**
     * Delete a review.
     */
    public function destroy(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        if ($review->reviewer_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $providerId = $review->user_id;
        $review->delete();

        $this->updateProviderRating($providerId);

        return response()->json(['message' => 'Évaluation supprimée']);
    }

    protected function updateProviderRating($providerId)
    {
        $avg = Review::where('user_id', $providerId)->avg('rating') ?: 0;
        
        \App\Models\Prestataire::where('user_id', $providerId)->update([
            'rating' => round($avg, 1)
        ]);
    }
}
