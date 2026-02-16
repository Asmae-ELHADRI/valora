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
        $query = Conversation::with(['sender:id,name,photo', 'receiver:id,name,photo'])
            ->latest();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('sender', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('receiver', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        $conversations = $query->paginate(15);
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
