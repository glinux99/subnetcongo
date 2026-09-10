<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Region;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles', 'region']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->role));
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::orderBy('name')->get(['id', 'name']);
        $regions = Region::orderBy('designation')->get(['id', 'designation']);

        return Inertia::render('Configurations/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'regions' => $regions,
            'filters' => $request->only(['search', 'role', 'region_id']),
        ]);
    }

    public function show(User $user)
    {
        $user->load(['roles', 'region', 'tokens']);
        
        $regions = Region::orderBy('designation')->get(['id', 'designation']);
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Configurations/Users/Settings', [
            'user' => $user,
            'regions' => $regions,
            'roles' => $roles,
            'logins' => $user->tokens()->orderByDesc('created_at')->paginate(20),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'fonction' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:50',
            'region_id' => 'nullable|exists:regions,id',
            'pointure' => 'nullable|integer|min:30|max:50',
            'size' => 'nullable|integer|min:100|max:250',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user->fill($validated);
        
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return redirect()->back()->with('message', 'Utilisateur mis à jour avec succès.');
    }

    public function history(User $user)
    {
        $logins = $user->tokens()->orderByDesc('created_at')->paginate(20);

        return Inertia::render('Configurations/Users/Histories', [
            'user' => $user,
            'logins' => $logins,
        ]);
    }
}