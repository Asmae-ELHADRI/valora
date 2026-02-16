<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $query = Conversation::with([
            'sender:id,name,email,role', 
            'receiver:id,name,email,role'
        ])
        ->withCount('messages')
        ->latest();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('sender', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('receiver', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        $conversations = $query->paginate(15);
        
        // Load prestataire for providers
        $conversations->getCollection()->transform(function ($conversation) {
            if ($conversation->sender && $conversation->sender->role === 'provider') {
                $conversation->sender->load('prestataire:user_id,photo');
            }
            if ($conversation->receiver && $conversation->receiver->role === 'provider') {
                $conversation->receiver->load('prestataire:user_id,photo');
            }
            return $conversation;
        });
        
        return response()->json($conversations);
    }

    public function show($senderId, $receiverId)
    {
        // Find conversation between these two users
        // Note: AdminConversations.vue passes sender_id and receiver_id
        
        $conversation = Conversation::where(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $senderId)->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $receiverId)->where('receiver_id', $senderId);
        })->first();

        if (!$conversation) {
             return response()->json(['data' => []]);
        }

        $messages = $conversation->messages()
            ->latest() // Vue reverses it
            ->paginate(50); // Pagination supported in Vue

        return response()->json($messages);
    }
}
