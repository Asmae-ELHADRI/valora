<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;

// Test the API endpoint manually
$user = User::first();
if (!$user) {
    echo "No users found\n";
    exit(1);
}

echo "Testing as user: {$user->name} (ID: {$user->id})\n\n";

try {
    $conversations = Conversation::where(function ($query) use ($user) {
        $query->where('sender_id', $user->id)
              ->orWhere('receiver_id', $user->id);
    })
    ->with([
        'sender:id,name,role,email', 
        'sender.prestataire:user_id,photo,badge_level',
        'receiver:id,name,role,email', 
        'receiver.prestataire:user_id,photo,badge_level',
        'messages' => function ($query) {
            $query->latest()->limit(1);
        }
    ])
    ->get();
    
    echo "Found " . $conversations->count() . " conversations\n\n";
    
    foreach ($conversations as $conv) {
        echo "Conversation #{$conv->id}:\n";
        echo "  Sender: {$conv->sender->name} (ID: {$conv->sender->id})\n";
        echo "  Receiver: {$conv->receiver->name} (ID: {$conv->receiver->id})\n";
        echo "  Messages: {$conv->messages->count()}\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
