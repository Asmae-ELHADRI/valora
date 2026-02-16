<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * Get all conversations for the authenticated user.
     */
    public function index()
    {
        $userId = Auth::id();

        $conversations = Conversation::where(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                  ->orWhere('receiver_id', $userId);
        })
        ->with([
            'sender:id,name,role,email', 
            'receiver:id,name,role,email', 
            'messages' => function ($query) {
                $query->latest()->limit(1);
            }
        ])
        ->get()
        ->sortByDesc(function ($conversation) {
            $lastMessage = $conversation->messages->first();
            return $lastMessage ? $lastMessage->created_at : $conversation->created_at;
        })
        ->map(function ($conversation) use ($userId) {
            $otherUser = $conversation->sender_id === $userId ? $conversation->receiver : $conversation->sender;
            $lastMessage = $conversation->messages->first();
            
            // Load prestataire if user is a provider
            if ($otherUser->role === 'provider') {
                $otherUser->load('prestataire:user_id,photo');
            }
            
            return [
                'id' => $conversation->id,
                'other_user' => $otherUser,
                'last_message' => $lastMessage ? $lastMessage->content : null,
                'last_message_time' => $lastMessage ? $lastMessage->created_at : $conversation->created_at,
                'unread_count' => $conversation->messages()->where('receiver_id', $userId)->whereNull('read_at')->count(),
                'related_type' => $conversation->related_type,
                'related_id' => $conversation->related_id,
                'is_blocked' => $conversation->is_blocked,
            ];
        })
        ->values();

        return response()->json(['data' => $conversations]);
    }

    /**
     * Get messages for a specific conversation.
     */
    public function show($id)
    {
        $userId = Auth::id();
        $conversation = Conversation::with([
            'sender:id,name,role,email', 
            'receiver:id,name,role,email'
        ])->findOrFail($id);

        // Security check: User must be a participant
        if ($conversation->sender_id !== $userId && $conversation->receiver_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Load prestataire for provider users
        if ($conversation->sender->role === 'provider') {
            $conversation->sender->load('prestataire:user_id,photo');
        }
        if ($conversation->receiver->role === 'provider') {
            $conversation->receiver->load('prestataire:user_id,photo');
        }

        // Mark messages as read
        Message::where('conversation_id', $conversation->id)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with(['sender:id,name,role'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                // Load prestataire for provider senders
                if ($message->sender->role === 'provider') {
                    $message->sender->load('prestataire:user_id,photo');
                }
                return $message;
            });

        return response()->json(['data' => $messages, 'conversation' => $conversation]);
    }

    /**
     * Mark conversation as read.
     */
    public function markAsRead($id)
    {
        $userId = Auth::id();
        $conversation = Conversation::findOrFail($id);

        if ($conversation->sender_id !== $userId && $conversation->receiver_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Message::where('conversation_id', $conversation->id)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * Start a new conversation or return existing one.
     */
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'related_type' => 'nullable|string',
            'related_id' => 'nullable|integer',
        ]);

        $senderId = Auth::id();
        $receiverId = $request->receiver_id;

        if ($senderId == $receiverId) {
            return response()->json(['message' => 'Cannot message yourself'], 422);
        }

        // Check if conversation already exists (context-aware optional)
        $conversation = Conversation::where(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $senderId)->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $receiverId)->where('receiver_id', $senderId);
        });

        // If context is provided, we can either scope it or just link general chat
        // For simplicity, let's treat it as a single thread per pair of users unless specific requirement
        // But user asked: "Linked to profile OR specific post".
        // If linked to a post, it might be separate.
        
        if ($request->related_type && $request->related_id) {
             $conversation->where('related_type', $request->related_type)
                          ->where('related_id', $request->related_id);
        }

        $conversation = $conversation->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'related_type' => $request->related_type,
                'related_id' => $request->related_id,
            ]);
        }

        return response()->json(['data' => $conversation]);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $userId = Auth::id();
        $conversation = Conversation::findOrFail($id);

        if ($conversation->is_blocked) {
             return response()->json(['message' => 'Conversation is blocked'], 403);
        }

        if ($conversation->sender_id !== $userId && $conversation->receiver_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $receiverId = ($conversation->sender_id === $userId) ? $conversation->receiver_id : $conversation->sender_id;

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userId,
            'receiver_id' => $receiverId,
            'content' => $request->content,
        ]);
        
        // Touch conversation to update timestamp
        $conversation->touch();

        // Broadcast event
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json(['data' => $message]);
    }
}
