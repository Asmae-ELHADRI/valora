<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Validation\Rule;

class AdminRoleController extends Controller
{
    public function index()
    {
        // Return roles with their permissions
        $roles = Role::with('permissions')->get();
        return response()->json([
            'roles' => $roles
        ]);
    }

    public function getPermissions()
    {
        // Return all available permissions
        $permissions = Permission::all();
        // Optionally group them by module (start of slug)
        $grouped = $permissions->groupBy(function ($item) {
            return explode('.', $item->slug)[0];
        });

        return response()->json([
            'permissions' => $permissions, // Flat list
            'grouped' => $grouped // Grouped for UI
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->slug)
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json([
            'message' => 'Rôle créé avec succès.',
            'role' => $role->load('permissions')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // Prevent modifying critical roles if needed (e.g. admin slug)
        // if ($role->slug === 'admin') { return response()->json(['message' => 'Impossible de modifier le rôle Admin global.'], 403); }

        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json([
            'message' => 'Rôle mis à jour avec succès.',
            'role' => $role->load('permissions')
        ]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if (in_array($role->slug, ['admin', 'client', 'provider', 'prestataire'])) {
            return response()->json(['message' => 'Impossible de supprimer les rôles système protégés.'], 403);
        }

        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer un rôle assigné à des utilisateurs.'], 400);
        }

        $role->delete();

        return response()->json(['message' => 'Rôle supprimé avec succès.']);
    }
}
