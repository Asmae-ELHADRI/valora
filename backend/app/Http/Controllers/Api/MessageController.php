<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Get list of conversations.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // Get unique users involved in conversations with the current user
        $users = User::whereIn('id', function($query) use ($userId) {
            $query->select('sender_id')
                ->from('messages')
                ->where('receiver_id', $userId)
                ->union(
                    DB::table('messages')
                        ->select('receiver_id')
                        ->where('sender_id', $userId)
                );
        })->get();

        // Attach last message to each user
        $conversations = $users->map(function($user) use ($userId) {
            $lastMessage = Message::where(function($q) use ($userId, $user) {
                $q->where('sender_id', $userId)->where('receiver_id', $user->id);
            })->orWhere(function($q) use ($userId, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $userId);
            })->latest()->first();

            $unreadCount = Message::where('sender_id', $user->id)
                ->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->count();

            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_blocked_by' => $user->isBlockedBy($userId),
                    'has_blocked' => $user->hasBlocked($userId),
                ],
                'last_message' => $lastMessage,
                'unread_count' => $unreadCount
            ];
        })->sortByDesc(function($conv) {
            return $conv['last_message'] ? $conv['last_message']->created_at : 0;
        })->values();

        return response()->json($conversations);
    }

    /**
     * Get messages for a specific conversation.
     */
    public function show(Request $request, $otherUserId)
    {
        $userId = $request->user()->id;

        $messages = Message::where(function($q) use ($userId, $otherUserId) {
            $q->where('sender_id', $userId)->where('receiver_id', $otherUserId);
        })->orWhere(function($q) use ($userId, $otherUserId) {
            $q->where('sender_id', $otherUserId)->where('receiver_id', $userId);
        })->orderBy('created_at', 'desc')->paginate(50); // Get latest first for pagination, frontend needs to reverse

        // Mark as read
        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * Send a new message.
     */
    public function store(Request $request)
    {
        $receiver = User::findOrFail($request->receiver_id);
        if ($receiver->isBlockedBy($request->user()->id) || $request->user()->isBlockedBy($receiver->id)) {
            return response()->json(['message' => 'Communication impossible avec cet utilisateur.'], 403);
        }

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
        ]);

        return response()->json($message);
    }

    /**
     * Get total unread messages count for global notifications.
     */
    public function unreadCount(Request $request)
    {
        $count = Message::where('receiver_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
