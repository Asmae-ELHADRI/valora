<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Roles
        $adminRole = Role::create(['name' => 'Administrateur', 'slug' => 'admin']);
        $providerRole = Role::create(['name' => 'Prestataire', 'slug' => 'provider']);
        $clientRole = Role::create(['name' => 'Client', 'slug' => 'client']);

        // 2. Create Permissions
        $permissions = [
            'manage_users' => 'Gérer les utilisateurs',
            'manage_roles' => 'Gérer les rôles et permissions',
            'manage_offers' => 'Gérer les offres de services',
            'manage_reports' => 'Gérer les signalements',
            'post_offers' => 'Publier des offres',
            'apply_to_offers' => 'Postuler aux offres',
            'send_messages' => 'Envoyer des messages',
            'view_dashboards' => 'Voir les tableaux de bord',
        ];

        $permissionModels = [];
        foreach ($permissions as $slug => $name) {
            $permissionModels[$slug] = Permission::create([
                'name' => $name,
                'slug' => $slug
            ]);
        }

        // 3. Assign Permissions to Roles
        $adminRole->permissions()->attach(array_values($permissionModels));
        
        $providerRole->permissions()->attach([
            $permissionModels['post_offers']->id,
            $permissionModels['send_messages']->id,
            $permissionModels['view_dashboards']->id,
        ]);

        $clientRole->permissions()->attach([
            $permissionModels['apply_to_offers']->id,
            $permissionModels['send_messages']->id,
            $permissionModels['view_dashboards']->id,
        ]);

        // 4. Migrating existing users
        User::where('role', 'admin')->update(['role_id' => $adminRole->id]);
        User::where('role', 'provider')->update(['role_id' => $providerRole->id]);
        User::where('role', 'client')->update(['role_id' => $clientRole->id]);
    }
}
