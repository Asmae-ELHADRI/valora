<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        // 1. Reset cached roles and permissions
        // app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions(); // If using Spatie
        // Since we use custom, we just truncate if needed, or updateOrCreate
        
        // Define Permissions
        $permissions = [
            // User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            
            // Roles & Permissions
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            
            // Content (Service Requests / Offers)
            'requests.view',
            'requests.create',
            'requests.edit',
            'requests.delete',
            
            // Profile
            'profile.view',
            'profile.edit',
            
            // Admin Global
            'dashboard.view',
            'admin.access',
            
            // Moderation
            'reports.view',
            'reports.manage',
            'users.block',
        ];

        foreach ($permissions as $slug) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                ['name' => ucwords(str_replace('.', ' ', $slug))]
            );
        }

        // 2. Define Roles and Assign Permissions
        
        // --- ADMIN ---
        $adminRole = Role::updateOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $allPermissions = Permission::all();
        $adminRole->permissions()->sync($allPermissions);

        // --- PRESTATAIRE (Provider) ---
        $providerRole = Role::updateOrCreate(['slug' => 'provider'], ['name' => 'Prestataire']);
        $providerPermissions = Permission::whereIn('slug', [
            'profile.view',
            'profile.edit',
            'requests.view', // Can view requests to apply
            // 'requests.create', // Depends if providers can create requests? Usually clients do.
        ])->get();
        $providerRole->permissions()->sync($providerPermissions);

        // --- CLIENT ---
        $clientRole = Role::updateOrCreate(['slug' => 'client'], ['name' => 'Client']);
        $clientPermissions = Permission::whereIn('slug', [
            'profile.view',
            'profile.edit',
            'requests.create',
            'requests.view', // Own requests
            'requests.edit',
        ])->get();
        $clientRole->permissions()->sync($clientPermissions);
        
        // --- MODERATOR (Example custom role) ---
        $modRole = Role::updateOrCreate(['slug' => 'moderator'], ['name' => 'Modérateur']);
        $modPermissions = Permission::whereIn('slug', [
            'dashboard.view',
            'admin.access',
            'users.view',
            'reports.view',
            'reports.manage',
            'users.block',
        ])->get();
        $modRole->permissions()->sync($modPermissions);

        echo "Roles and Permissions Seeded Successfully.\n";
    }
}
