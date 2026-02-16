<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$admin = User::where('role', 'admin')->first();

if ($admin) {
    echo "Admin trouvé:\n";
    echo "ID: " . $admin->id . "\n";
    echo "Nom: " . $admin->name . "\n";
    echo "Email: " . $admin->email . "\n";
} else {
    echo "Aucun administrateur trouvé. Création d'un compte admin...\n";
    $admin = User::create([
        'name' => 'Administrateur VALORA',
        'email' => 'admin@valora.ma',
        'password' => bcrypt('admin123'),
        'role' => 'admin'
    ]);
    echo "Admin créé avec succès!\n";
    echo "ID: " . $admin->id . "\n";
    echo "Email: admin@valora.ma\n";
    echo "Mot de passe: admin123\n";
}
