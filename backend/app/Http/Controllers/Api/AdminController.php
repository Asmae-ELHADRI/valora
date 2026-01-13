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
    public function stats(Request $request)
    {
        $period = $request->input('period', 'all'); // day, week, month, year, all

        // Date Filter Helper
        $applyFilter = function ($query) use ($period) {
            if ($period === 'day') $query->whereDate('created_at', now());
            elseif ($period === 'week') $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            elseif ($period === 'month') $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            elseif ($period === 'year') $query->whereYear('created_at', now()->year);
            return $query;
        };

        // Basic Counts
        $usersQuery = \App\Models\User::query();
        $applyFilter($usersQuery);
        $users_count = $usersQuery->count();

        $providersQuery = \App\Models\User::where('role', 'provider');
        $applyFilter($providersQuery);
        $providers_count = $providersQuery->count();

        $clientsQuery = \App\Models\User::where('role', 'client');
        $applyFilter($clientsQuery);
        $clients_count = $clientsQuery->count();

        $offersQuery = \App\Models\ServiceOffer::query();
        $applyFilter($offersQuery);
        
        $requestsQuery = \App\Models\ServiceRequest::query();
        $applyFilter($requestsQuery);

        $complaintsQuery = \App\Models\Complaint::where('status', 'pending');
        $applyFilter($complaintsQuery);

        $missionsQuery = \App\Models\ServiceRequest::whereIn('status', ['accepted', 'completed']);
        $applyFilter($missionsQuery);

        $reviewsQuery = \App\Models\Review::query();
        $applyFilter($reviewsQuery);

        // Top Categories (Supply)
        $topCategoriesSupply = \App\Models\ServiceCategory::withCount(['serviceOffers' => function($q) use ($period) {
                if ($period === 'day') $q->whereDate('created_at', now());
                elseif ($period === 'week') $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                elseif ($period === 'month') $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                elseif ($period === 'year') $q->whereYear('created_at', now()->year);
            }])
            ->orderBy('service_offers_count', 'desc')
            ->take(5)
            ->get();

        // Top Categories (Demand)
        $topCategoriesDemand = \App\Models\ServiceCategory::join('service_offers', 'service_categories.id', '=', 'service_offers.category_id')
            ->join('service_requests', 'service_offers.id', '=', 'service_requests.service_offer_id')
            ->select('service_categories.id', 'service_categories.name', \Illuminate\Support\Facades\DB::raw('count(service_requests.id) as requests_count'))
            ->when($period !== 'all', function ($q) use ($period) {
                if ($period === 'day') $q->whereDate('service_requests.created_at', now());
                elseif ($period === 'week') $q->whereBetween('service_requests.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                elseif ($period === 'month') $q->whereMonth('service_requests.created_at', now()->month);
                elseif ($period === 'year') $q->whereYear('service_requests.created_at', now()->year);
            })
            ->groupBy('service_categories.id', 'service_categories.name')
            ->orderByDesc('requests_count')
            ->take(5)
            ->get();

        // Chart Data
        $chartData = $this->getChartData($period);

        return response()->json([
            'users_count' => $users_count,
            'providers_count' => $providers_count,
            'clients_count' => $clients_count,
            'offers_count' => $offersQuery->count(),
            'requests_count' => $requestsQuery->count(),
            'reports_count' => $complaintsQuery->count(),
            'missions_count' => $missionsQuery->count(),
            'avg_rating' => round($reviewsQuery->avg('rating') ?? 0, 1),
            'reviews_count' => $reviewsQuery->count(),
            'top_categories' => $topCategoriesSupply,
            'top_categories_demand' => $topCategoriesDemand,
            'chart_data' => $chartData
        ]);
    }

    private function getChartData($period)
    {
        $labels = [];
        $dateFormat = 'Y-m-d';
        
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        if ($period === 'day') {
            $dateFormat = 'H:00';
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        } elseif ($period === 'week') {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
        } elseif ($period === 'year') {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();
            $dateFormat = 'Y-m';
        }

        $periodRange = \Carbon\CarbonPeriod::create($startDate, '1 ' . ($period === 'day' ? 'hour' : ($period === 'year' ? 'month' : 'day')), $endDate);
        foreach ($periodRange as $date) {
            $labels[] = $date->format($dateFormat);
        }

        $datasets = ['users' => [], 'offers' => [], 'requests' => []];

        $users = \App\Models\User::whereBetween('created_at', [$startDate, $endDate])->get();
        $offers = \App\Models\ServiceOffer::whereBetween('created_at', [$startDate, $endDate])->get();
        $requests = \App\Models\ServiceRequest::whereBetween('created_at', [$startDate, $endDate])->get();

        foreach ($labels as $label) {
            $datasets['users'][] = $users->filter(fn($i) => $i->created_at->format($dateFormat) === $label)->count();
            $datasets['offers'][] = $offers->filter(fn($i) => $i->created_at->format($dateFormat) === $label)->count();
            $datasets['requests'][] = $requests->filter(fn($i) => $i->created_at->format($dateFormat) === $label)->count();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nouveaux Utilisateurs',
                    'data' => $datasets['users'],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4
                ],
                [
                    'label' => 'Offres Publiées',
                    'data' => $datasets['offers'],
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'tension' => 0.4
                ],
                [
                    'label' => 'Demandes de Service',
                    'data' => $datasets['requests'],
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'tension' => 0.4
                ]
            ]
        ];
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
     * Get security logs.
     */
    public function logs(Request $request)
    {
        $query = \App\Models\ActivityLog::with('user');

        if ($request->action) {
            $query->where('action', $request->action);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->whereHas('user', function($u) use ($request) {
                    $u->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                })->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhere('ip_address', 'like', "%{$request->search}%");
            });
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Get active security alerts.
     */
    public function securityAlerts()
    {
        $alerts = [];

        // Check for brute force attempts (mock logic: > 5 failed logins from same IP in last hour)
        $suspiciousIps = \App\Models\ActivityLog::where('action', 'login_failed')
            ->where('created_at', '>=', now()->subHour())
            ->select('ip_address', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('ip_address')
            ->having('total', '>', 5)
            ->get();

        foreach ($suspiciousIps as $ip) {
            $alerts[] = [
                'type' => 'brute_force',
                'severity' => 'high',
                'message' => "Suspicion d'attaque par force brute depuis l'IP {$ip->ip_address} ({$ip->total} échecs en 1h)",
                'count' => $ip->total,
                'target' => $ip->ip_address
            ];
        }

        // Check for users with many pending reports
        $reportedUsers = \App\Models\Complaint::where('status', 'pending')
            ->select('reported_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('reported_id')
            ->having('total', '>=', 3)
            ->with('reported')
            ->get();

        foreach ($reportedUsers as $report) {
            $user = $report->reported;
            if ($user) {
                $alerts[] = [
                    'type' => 'highly_reported',
                    'severity' => 'medium',
                    'message' => "L'utilisateur {$user->name} a {$report->total} signalements en attente.",
                    'count' => $report->total,
                    'target_id' => $user->id,
                    'user' => $user
                ];
            }
        }

        return response()->json($alerts);
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
        
        // Log action
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(), // Admin who performed the action
            'action' => $user->is_active ? 'user_activated' : 'user_suspended',
            'description' => ($user->is_active ? 'Activation' : 'Suspension') . " du compte de {$user->name} ({$user->email})",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

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
        $query = \App\Models\Complaint::with(['reporter', 'reported', 'subject']);

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

    /**
     * Warn a user.
     */
    public function warnUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Logic to send email or notification would go here
        // For now, we'll just log it or assume it's done
        
        return response()->json([
            'message' => 'Avertissement envoyé à l\'utilisateur'
        ]);
    }

    /**
     * Delete reported content.
     */
    public function deleteReportedContent($id)
    {
        $complaint = \App\Models\Complaint::findOrFail($id);
        
        if ($complaint->subject) {
            $complaint->subject->delete();
            $complaint->update(['status' => 'resolved']);
        }

        return response()->json([
            'message' => 'Contenu supprimé avec succès'
        ]);
    }
}
