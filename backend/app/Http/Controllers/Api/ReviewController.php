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
        $totalReviews = $reviews->count();

        return response()->json([
            'reviews' => $reviews,
            'average_rating' => round($averageRating, 1),
            'total_reviews' => $totalReviews
        ]);
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

        $review = Review::create([
            'user_id' => $request->user_id,
            'reviewer_id' => $request->user()->id,
            'service_offer_id' => $request->service_offer_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Évaluation enregistrée avec succès',
            'data' => $review
        ]);
    }
}
