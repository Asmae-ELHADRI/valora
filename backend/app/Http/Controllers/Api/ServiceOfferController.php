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
        $query = ServiceOffer::where('status', 'active')
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

        // Filter by date
        if ($request->has('desired_date')) {
            $query->whereDate('desired_date', $request->desired_date);
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

    /**
     * Get all categories for filtering.
     */
    public function categories()
    {
        return response()->json(ServiceCategory::all());
    }
}
