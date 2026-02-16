<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with(['reporter', 'reported'])->latest();

        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('reported', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $reports = $query->paginate($request->per_page ?? 10);
        return response()->json($reports);
    }

    public function show($id)
    {
        $report = Report::with(['reporter', 'reported'])->findOrFail($id);
        return response()->json($report);
    }

    public function action(Request $request, $id)
    {
        $report = Report::findOrFail($id);
        $action = $request->action;

        switch ($action) {
            case 'ignore':
                $report->update(['status' => 'dismissed']);
                break;
                
            case 'warn':
                // Logic to send warning notification to user
                // Notification::send($report->reported, new WarningNotification($report));
                $report->update(['status' => 'resolved']);
                break;
                
            case 'ban':
                if ($report->reported) {
                    $report->reported->update(['is_banned' => true]); // Assuming user has is_banned column
                    // Or $report->reported->delete();
                }
                $report->update(['status' => 'resolved']);
                break;
                
            default:
                return response()->json(['message' => 'Invalid action'], 400);
        }

        return response()->json(['message' => 'Action performed successfully', 'report' => $report]);
    }
}
