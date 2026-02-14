<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::where('name', 'like', '%dalal%')->get();

if ($users->isEmpty()) {
    echo "User 'dalal' not found\n";
    $user = App\Models\User::first();
} else {
    $user = $users->first();
    echo "Found user: " . $user->name . " (" . $user->email . ")\n";
}

echo "User found: " . $user->id . "\n";
$user->load('prestataire.categories');

if ($user->prestataire) {
    echo "Prestataire found\n";
    echo "Availabilities type: " . gettype($user->prestataire->availabilities) . "\n";
    print_r($user->prestataire->availabilities);
} else {
    echo "Prestataire NOT found\n";
}
