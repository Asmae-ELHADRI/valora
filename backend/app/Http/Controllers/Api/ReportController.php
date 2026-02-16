<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'reported_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:1000',
        ]);

        $reporterId = Auth::id();
        $reportedId = $request->reported_id;

        if ($reporterId == $reportedId) {
            return response()->json(['message' => 'You cannot report yourself.'], 422);
        }

        $reportedUser = User::findOrFail($reportedId);
        
        // Security: Prevent reporting Admin
        // Assuming role check via relationship or simple column
        if ($reportedUser->role === 'admin' || $reportedUser->hasRole('admin')) {
             return response()->json(['message' => 'Administrators cannot be reported.'], 403);
        }

        $report = Report::create([
            'reporter_id' => $reporterId,
            'reported_id' => $reportedId,
            'reason' => $request->reason,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Report submitted successfully.', 'data' => $report], 201);
    }
}
