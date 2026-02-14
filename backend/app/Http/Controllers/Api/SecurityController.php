<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\UserBlock;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    /**
     * Report a user.
     */
    public function report(Request $request)
    {
        $request->validate([
            'reported_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($request->reported_id == $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous signaler vous-même'], 422);
        }

        $complaint = Complaint::create([
            'reporter_id' => $request->user()->id,
            'reported_id' => $request->reported_id,
            'reason' => $request->reason,
            'description' => $request->description,
            'priority' => 'medium', // Default priority for user reports
            'status' => 'pending',
        ]);

        // Automatically block the user
        UserBlock::firstOrCreate([
            'blocker_id' => $request->user()->id,
            'blocked_id' => $request->reported_id,
        ]);

        return response()->json([
            'message' => 'Signalement envoyé avec succès. Nos administrateurs vont examiner votre demande.',
            'data' => $complaint
        ]);
    }

    /**
     * Block a user.
     */
    public function block(Request $request)
    {
        $request->validate([
            'blocked_id' => 'required|exists:users,id',
        ]);

        if ($request->blocked_id == $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous bloquer vous-même'], 422);
        }

        $block = UserBlock::firstOrCreate([
            'blocker_id' => $request->user()->id,
            'blocked_id' => $request->blocked_id,
        ]);

        return response()->json([
            'message' => 'Utilisateur bloqué.',
            'data' => $block
        ]);
    }

    /**
     * Unblock a user.
     */
    public function unblock(Request $request, $id)
    {
        UserBlock::where('blocker_id', $request->user()->id)
            ->where('blocked_id', $id)
            ->delete();

        return response()->json(['message' => 'Utilisateur débloqué.']);
    }

    /**
     * List blocked users.
     */
    public function blockedList(Request $request)
    {
        $blocked = UserBlock::with('blocked:id,name,email')
            ->where('blocker_id', $request->user()->id)
            ->get();

        return response()->json($blocked);
    }
}
