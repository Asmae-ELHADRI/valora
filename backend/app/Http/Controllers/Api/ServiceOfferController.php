<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceOffer;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceOfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ServiceOffer::where('status', 'open')
            ->with(['user:id,name', 'category:id,name,icon,slug']);

        // Filter by keyword (title or description)
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        // Filter by budget
        if ($request->has('min_budget')) {
            $query->where('budget', '>=', $request->min_budget);
        }
        if ($request->has('max_budget')) {
            $query->where('budget', '<=', $request->max_budget);
        }

        // Filter by date (on or after)
        if ($request->has('desired_date')) {
            $query->whereDate('desired_date', '>=', $request->desired_date);
        }

        $offers = $query->latest()->paginate(10);

        return response()->json($offers);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $offer = ServiceOffer::with(['user:id,name', 'category:id,name,icon,slug'])
            ->findOrFail($id);

        return response()->json($offer);
    }

    public function myOffers(Request $request)
    {
        $offers = ServiceOffer::where('user_id', $request->user()->id)
            ->with('category:id,name,icon,slug')
            ->latest()
            ->paginate(10);

        return response()->json($offers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'nature_of_need' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'estimated_duration' => 'nullable|string',
            'material_required' => 'nullable|string',
            'budget' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'desired_date' => 'nullable|date',
        ]);

        $offer = ServiceOffer::create([
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'nature_of_need' => $request->nature_of_need,
            'title' => $request->title,
            'description' => $request->description,
            'requirements' => $request->requirements,
            'estimated_duration' => $request->estimated_duration,
            'material_required' => $request->material_required,
            'budget' => $request->budget,
            'location' => $request->location,
            'desired_date' => $request->desired_date,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Offre publiée avec succès',
            'offer' => $offer,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $offer = ServiceOffer::findOrFail($id);
        $this->authorize('update', $offer);

        $request->validate([
            'category_id' => 'sometimes|required|exists:service_categories,id',
            'nature_of_need' => 'nullable|string|max:255',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'requirements' => 'nullable|string',
            'estimated_duration' => 'nullable|string',
            'material_required' => 'nullable|string',
            'budget' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'desired_date' => 'nullable|date',
            'status' => 'sometimes|required|string|in:open,in_progress,completed,cancelled',
        ]);

        $offer->update($request->all());

        return response()->json([
            'message' => 'Offre mise à jour avec succès',
            'offer' => $offer,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $offer = ServiceOffer::findOrFail($id);
        $this->authorize('delete', $offer);
        $offer->delete();

        return response()->json([
            'message' => 'Offre supprimée avec succès',
        ]);
    }

    /**
     * Get all categories for filtering.
     */
    public function categories()
    {
        return response()->json(ServiceCategory::all());
    }
}
