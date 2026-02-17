<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMessageController extends Controller
{
    /**
     * Get all conversations where admin is a participant
     */
    public function index()
    {
        $adminId = Auth::id();

        $conversations = Conversation::where(function ($query) use ($adminId) {
            $query->where('sender_id', $adminId)
                  ->orWhere('receiver_id', $adminId);
        })
        ->with([
            'sender:id,name,email,role', 
            'receiver:id,name,email,role',
            'messages' => function ($query) {
                $query->latest()->limit(1);
            }
        ])
        ->withCount('messages')
        ->get()
        ->sortByDesc(function ($conversation) {
            $lastMessage = $conversation->messages->first();
            return $lastMessage ? $lastMessage->created_at : $conversation->created_at;
        })
        ->map(function ($conversation) use ($adminId) {
            $otherUser = $conversation->sender_id === $adminId 
                ? $conversation->receiver 
                : $conversation->sender;
            
            // Load prestataire if user is provider
            if ($otherUser && $otherUser->role === 'provider') {
                $otherUser->load('prestataire:user_id,photo');
            }
            
            $lastMessage = $conversation->messages->first();
            
            return [
                'id' => $conversation->id,
                'other_user' => $otherUser,
                'last_message' => $lastMessage ? $lastMessage->content : null,
                'last_message_time' => $lastMessage ? $lastMessage->created_at : $conversation->created_at,
                'unread_count' => $conversation->messages()
                    ->where('receiver_id', $adminId)
                    ->whereNull('read_at')
                    ->count(),
                'messages_count' => $conversation->messages_count,
                'related_type' => $conversation->related_type,
                'related_id' => $conversation->related_id,
            ];
        })
        ->values();

        return response()->json(['data' => $conversations]);
    }

    /**
     * Get a specific conversation where admin is participant
     */
    public function show($id)
    {
        $adminId = Auth::id();
        
        $conversation = Conversation::with([
            'sender:id,name,role,email', 
            'receiver:id,name,role,email'
        ])->findOrFail($id);

        // Security: Admin must be participant
        if ($conversation->sender_id !== $adminId && $conversation->receiver_id !== $adminId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Load prestataire for providers
        if ($conversation->sender && $conversation->sender->role === 'provider') {
            $conversation->sender->load('prestataire:user_id,photo');
        }
        if ($conversation->receiver && $conversation->receiver->role === 'provider') {
            $conversation->receiver->load('prestataire:user_id,photo');
        }

        // Mark messages as read
        Message::where('conversation_id', $conversation->id)
            ->where('receiver_id', $adminId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with(['sender:id,name,role'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                if ($message->sender && $message->sender->role === 'provider') {
                    $message->sender->load('prestataire:user_id,photo');
                }
                return $message;
            });

        return response()->json([
            'data' => [
                'conversation' => $conversation,
                'messages' => $messages
            ]
        ]);
    }

    /**
     * Send a message in admin conversation
     */
    public function sendMessage(Request $request, $id)
    {
        $adminId = Auth::id();
        
        $conversation = Conversation::findOrFail($id);

        // Security: Admin must be participant
        if ($conversation->sender_id !== $adminId && $conversation->receiver_id !== $adminId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $receiverId = $conversation->sender_id === $adminId 
            ? $conversation->receiver_id 
            : $conversation->sender_id;

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $adminId,
            'receiver_id' => $receiverId,
            'content' => $request->content,
        ]);

        $message->load('sender:id,name,role');

        return response()->json(['data' => $message], 201);
    }

    /**
     * Start a new conversation with a user
     */
    public function startConversation(Request $request)
    {
        $adminId = Auth::id();

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'initial_message' => 'nullable|string|max:5000',
        ]);

        // Prevent self-conversation
        if ($request->user_id == $adminId) {
            return response()->json(['error' => 'Cannot start conversation with yourself'], 422);
        }

        // Check if conversation already exists
        $existingConversation = Conversation::where(function ($query) use ($adminId, $request) {
            $query->where('sender_id', $adminId)
                  ->where('receiver_id', $request->user_id);
        })->orWhere(function ($query) use ($adminId, $request) {
            $query->where('sender_id', $request->user_id)
                  ->where('receiver_id', $adminId);
        })->first();

        if ($existingConversation) {
            return response()->json([
                'data' => $existingConversation,
                'message' => 'Conversation already exists'
            ]);
        }

        // Create new conversation
        $conversation = Conversation::create([
            'sender_id' => $adminId,
            'receiver_id' => $request->user_id,
            'related_type' => 'support',
            'related_id' => null,
        ]);

        // Send initial message if provided
        if ($request->initial_message) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $adminId,
                'receiver_id' => $request->user_id,
                'content' => $request->initial_message,
            ]);
        }

        $conversation->load(['sender:id,name,role', 'receiver:id,name,role']);

        return response()->json(['data' => $conversation], 201);
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead($id)
    {
        $adminId = Auth::id();
        
        $conversation = Conversation::findOrFail($id);

        // Security: Admin must be participant
        if ($conversation->sender_id !== $adminId && $conversation->receiver_id !== $adminId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Message::where('conversation_id', $conversation->id)
            ->where('receiver_id', $adminId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * Get unread message count for admin
     */
    public function unreadCount()
    {
        $adminId = Auth::id();
        
        $count = Message::where('receiver_id', $adminId)
            ->whereNull('read_at')
            ->count();
        
        return response()->json(['count' => $count]);
    }
}
