<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                    'last_seen_at' => $user->last_seen_at,
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
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'nullable|string',
            'attachment' => 'nullable|file|max:20480', // 20MB max
            'image' => 'nullable|image|max:10240', // Backward compatibility / specific image field
        ]);

        if ($request->receiver_id == $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous envoyer de message à vous-même.'], 422);
        }

        if (!$request->content && !$request->hasFile('image') && !$request->hasFile('attachment')) {
            return response()->json(['message' => 'Le message ne peut pas être vide.'], 422);
        }

        $receiver = User::findOrFail($request->receiver_id);
        if ($receiver->isBlockedBy($request->user()->id) || $request->user()->isBlockedBy($receiver->id)) {
            return response()->json(['message' => 'Communication impossible avec cet utilisateur.'], 403);
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chat_attachments', 'local');
            $attachmentPath = $path;
            $attachmentType = 'image';
        } elseif ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat_attachments', 'local');
            $attachmentPath = $path;
            
            $mime = $file->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                $attachmentType = 'image';
            } elseif (str_starts_with($mime, 'video/')) {
                $attachmentType = 'video';
            } elseif (str_starts_with($mime, 'audio/')) {
                $attachmentType = 'audio';
            } else {
                $attachmentType = 'file';
            }
        }

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->content ?? '',
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        $message->load('sender');

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }

    /**
     * Download/View an attachment securely.
     */
    public function downloadAttachment(Request $request, $messageId)
    {
        $message = Message::findOrFail($messageId);
        $userId = $request->user()->id;

        // Authorization: only sender or receiver
        if ($message->sender_id !== $userId && $message->receiver_id !== $userId) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        if (!$message->attachment_path || !Storage::disk('local')->exists($message->attachment_path)) {
            return response()->json(['message' => 'Fichier introuvable.'], 404);
        }

        return Storage::disk('local')->download($message->attachment_path);
    }

    /**
     * Mark all messages from a specific user as read.
     */
    public function markAsRead(Request $request, $senderId)
    {
        Message::where('sender_id', $senderId)
            ->where('receiver_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
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
