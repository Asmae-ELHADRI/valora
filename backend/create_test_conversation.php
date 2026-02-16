<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;

// Find or create a client and a provider
$client = User::where('role', 'client')->first();
$provider = User::where('role', 'provider')->first();

if (!$client) {
    echo "No client found, creating one...\n";
    $client = User::create([
        'name' => 'Test Client',
        'email' => 'client@test.com',
        'password' => bcrypt('password'),
        'role' => 'client',
        'is_active' => true
    ]);
}

if (!$provider) {
    echo "No provider found, creating one...\n";
    $provider = User::create([
        'name' => 'Test Provider',
        'email' => 'provider@test.com',
        'password' => bcrypt('password'),
        'role' => 'provider',
        'is_active' => true
    ]);
}

echo "Client: {$client->name} (ID: {$client->id})\n";
echo "Provider: {$provider->name} (ID: {$provider->id})\n\n";

// Create a conversation if it doesn't exist
$conversation = Conversation::where('sender_id', $client->id)
    ->where('receiver_id', $provider->id)
    ->first();

if (!$conversation) {
    echo "Creating conversation...\n";
    $conversation = Conversation::create([
        'sender_id' => $client->id,
        'receiver_id' => $provider->id,
        'related_type' => 'profile',
        'related_id' => null
    ]);
    echo "Conversation created (ID: {$conversation->id})\n\n";
} else {
    echo "Conversation already exists (ID: {$conversation->id})\n\n";
}

// Create a test message
$messageExists = Message::where('conversation_id', $conversation->id)->exists();
if (!$messageExists) {
    echo "Creating test message...\n";
    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $client->id,
        'receiver_id' => $provider->id,
        'content' => 'Bonjour, j\'aimerais discuter de votre service de plomberie.',
    ]);
    echo "Message created.\n";
} else {
    echo "Messages already exist.\n";
}

echo "\nConversation setup complete!\n";
echo "Client can now see this conversation in Messages page.\n";
