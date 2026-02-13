<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\User;
use App\Models\UserBlock;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $priority = $request->query('priority');
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $query = Complaint::with(['reporter', 'reported', 'subject'])
            ->where('status', '!=', 'resolved')
            ->orderBy('created_at', 'desc');

        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                // Search by ID/Reference
                $q->where('id', 'like', "%{$search}%")
                  // Or by reported user name
                  ->orWhereHas('reported', function($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        return response()->json($query->paginate($perPage));
    }

    public function show($id)
    {
        return response()->json(Complaint::with(['reporter', 'reported', 'subject'])->findOrFail($id));
    }

    public function action(Request $request, $id)
    {
        $complaint = Complaint::findOrFail($id);
        $action = $request->input('action'); // ignore, warn, ban

        if ($action === 'ignore') {
            $complaint->status = 'ignored';
            $complaint->save();
        } elseif ($action === 'warn') {
            // Logic to send warning notification (mocked for now)
            $complaint->status = 'warned';
            $complaint->save();
        } elseif ($action === 'ban') {
            // Ban logic
            UserBlock::create([
                'user_id' => $complaint->reported_id,
                'reason' => 'Banned via Admin Moderation',
                'blocked_by' => auth()->id() ?? 1 // Fallback for dev
            ]);
            
            // Optionally update user status
            $user = User::find($complaint->reported_id);
            if ($user) {
                $user->status = 'banned';
                $user->save();
            }

            $complaint->status = 'banned';
            $complaint->save();
        }

        return response()->json(['message' => 'Action executed successfully', 'complaint' => $complaint]);
    }
}
