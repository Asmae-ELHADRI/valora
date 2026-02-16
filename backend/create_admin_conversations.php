<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;

$admin = User::where('role', 'admin')->first();

if (!$admin) {
    echo "Aucun administrateur trouvé!\n";
    exit(1);
}

// Get all non-admin users
$users = User::where('role', '!=', 'admin')->get();

echo "Création de conversations avec l'administrateur pour " . $users->count() . " utilisateurs...\n\n";

foreach ($users as $user) {
    // Check if conversation already exists (bidirectional check)
    $existingConv = Conversation::where(function($q) use ($user, $admin) {
        $q->where('sender_id', $user->id)->where('receiver_id', $admin->id);
    })->orWhere(function($q) use ($user, $admin) {
        $q->where('sender_id', $admin->id)->where('receiver_id', $user->id);
    })->first();

    if ($existingConv) {
        echo "✓ Conversation existe déjà pour {$user->name}\n";
        continue;
    }

    // Create new conversation
    $conversation = Conversation::create([
        'sender_id' => $user->id,
        'receiver_id' => $admin->id,
    ]);

    // Create welcome message from admin
    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $admin->id,
        'receiver_id' => $user->id,
        'content' => "Bonjour {$user->name}, bienvenue sur VALORA ! Je suis l'administrateur de la plateforme. N'hésitez pas à me contacter si vous avez des questions ou besoin d'aide. 😊"
    ]);

    echo "✓ Conversation créée pour {$user->name}\n";
}

echo "\n✅ Terminé! Toutes les conversations avec l'admin sont prêtes.\n";
