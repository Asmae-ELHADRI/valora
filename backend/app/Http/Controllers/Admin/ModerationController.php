<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModerationController extends Controller
{
    /**
     * Get all reports with pagination and filters
     */
    public function index(Request $request)
    {
        $query = Report::with(['reporter:id,name,email,role', 'reported:id,name,email,role'])
            ->orderByDesc('created_at');

        // Filter by admin action status
        if ($request->has('admin_action')) {
            $query->where('admin_action', $request->admin_action);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(20);

        return response()->json($reports);
    }

    /**
     * Get a single report with full details
     */
    public function show($id)
    {
        $report = Report::with(['reporter', 'reported'])->findOrFail($id);

        return response()->json(['data' => $report]);
    }

    /**
     * Ignore a report
     */
    public function ignore(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);

        $report = Report::findOrFail($id);
        
        $report->update([
            'admin_action' => 'ignored',
            'admin_action_at' => now(),
            'admin_notes' => $request->notes,
            'status' => 'resolved'
        ]);

        return response()->json([
            'message' => 'Signalement ignoré',
            'data' => $report
        ]);
    }

    /**
     * Warn the reported user
     */
    public function warn(Request $request, $id)
    {
        $request->validate([
            'warning_message' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000'
        ]);

        $report = Report::findOrFail($id);
        
        DB::transaction(function() use ($report, $request) {
            $report->update([
                'admin_action' => 'warned',
                'admin_action_at' => now(),
                'admin_notes' => $request->notes,
                'status' => 'resolved'
            ]);

            // You can add email notification logic here
            // Mail::to($report->reported->email)->send(new WarningNotification($request->warning_message));
        });

        return response()->json([
            'message' => 'Utilisateur averti avec succès',
            'data' => $report
        ]);
    }

    /**
     * Suspend the reported user account
     */
    public function suspend(Request $request, $id)
    {
        $request->validate([
            'suspension_days' => 'required|integer|min:1|max:365',
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000'
        ]);

        $report = Report::findOrFail($id);
        
        DB::transaction(function() use ($report, $request) {
            $report->update([
                'admin_action' => 'suspended',
                'admin_action_at' => now(),
                'admin_notes' => $request->notes,
                'status' => 'resolved'
            ]);

            // Suspend the user account
            $report->reported->update([
                'is_active' => false,
                // You might want to add a 'suspended_until' field to users table
            ]);
        });

        return response()->json([
            'message' => "Compte suspendu pour {$request->suspension_days} jours",
            'data' => $report
        ]);
    }

    /**
     * Delete the reported user account
     */
    public function deleteAccount(Request $request, $id)
    {
        $request->validate([
            'confirmation' => 'required|in:DELETE_ACCOUNT',
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000'
        ]);

        $report = Report::findOrFail($id);
        
        DB::transaction(function() use ($report, $request) {
            $reportedUser = $report->reported;

            $report->update([
                'admin_action' => 'deleted',
                'admin_action_at' => now(),
                'admin_notes' => $request->notes,
                'status' => 'resolved'
            ]);

            // Soft delete or permanently delete the user
            // You might want to archive the user data first
            $reportedUser->delete();
        });

        return response()->json([
            'message' => 'Compte supprimé avec succès',
            'data' => $report
        ]);
    }
}
