<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Prestataire;
use App\Models\Client;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Get platform statistics.
     */
    public function stats()
    {
        return response()->json([
            'users_count' => User::count(),
            'providers_count' => User::where('role', 'provider')->count(),
            'clients_count' => User::where('role', 'client')->count(),
            'offers_count' => \App\Models\ServiceOffer::count(),
            'reports_count' => \App\Models\Complaint::where('status', 'pending')->count(),
        ]);
    }

    /**
     * List users with filters.
     */
    public function index(Request $request)
    {
        $query = User::with(['prestataire', 'client', 'roleModel.permissions']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->role) {
            $query->where('role', $request->role);
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Create user manually.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::find($request->role_id);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role->slug,
            'role_id' => $request->role_id,
            'is_active' => true,
        ]);

        if ($role->slug === 'provider') {
            Prestataire::create(['user_id' => $user->id]);
        } elseif ($role->slug === 'client') {
            Client::create(['user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user->load(['prestataire', 'client', 'roleModel'])
        ], 201);
    }

    /**
     * Update user basic info.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,'.$user->id,
            'role_id' => 'sometimes|required|exists:roles,id',
        ]);

        if ($request->role_id) {
            $role = Role::find($request->role_id);
            $user->role = $role->slug;
            $user->role_id = $request->role_id;
        }

        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->save();

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user->load(['prestataire', 'client', 'roleModel'])
        ]);
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Vous ne pouvez pas vous désactiver vous-même'], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message' => $user->is_active ? 'Compte activé' : 'Compte désactivé',
            'user' => $user
        ]);
    }

    /**
     * Delete user permanently.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte admin'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé définitivement']);
    }

    /**
     * List all complaints.
     */
    public function complaints(Request $request)
    {
        $query = \App\Models\Complaint::with(['reporter', 'reported']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Update complaint status.
     */
    public function updateComplaintStatus(Request $request, $id)
    {
        $complaint = \App\Models\Complaint::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:pending,resolved,ignored'
        ]);

        $complaint->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Statut du signalement mis à jour',
            'complaint' => $complaint->load(['reporter', 'reported'])
        ]);
    }

    /**
     * Get all roles and permissions.
     */
    public function roles()
    {
        return response()->json([
            'roles' => Role::with('permissions')->get(),
            'permissions' => Permission::all()
        ]);
    }

    /**
     * Update role permissions.
     */
    public function updateRolePermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role->permissions()->sync($request->permissions);

        return response()->json([
            'message' => 'Permissions du rôle mises à jour',
            'role' => $role->load('permissions')
        ]);
    }
}
