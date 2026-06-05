<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'areas'])->orderBy('name')->paginate(25);
        return view('users.index', compact('users'));
    }

    private function validRoles()
    {
        return Role::whereIn('name', ['super_admin', 'admin', 'area_admin', 'viewer'])
            ->orderByRaw("FIELD(name,'super_admin','admin','area_admin','viewer')")
            ->get();
    }

    public function create()
    {
        $roles = $this->validRoles();
        $areas = Area::where('is_active', true)->orderBy('name')->get();
        return view('users.create', compact('roles', 'areas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'pis_number' => 'required|string|max:20|unique:users,pis_number',
            'email'      => 'required|email|unique:users,email',
            'mobile_no'  => 'nullable|string|max:15',
            'password'   => 'required|string|min:8|confirmed',
            'role'       => 'required|in:super_admin,admin,area_admin,viewer',
            'areas'      => 'nullable|array',
            'areas.*'    => 'exists:areas,id',
        ]);

        $user = User::create([
            'name'       => $request->name,
            'pis_number' => strtoupper($request->pis_number),
            'email'      => $request->email,
            'mobile_no'  => $request->mobile_no,
            'password'   => Hash::make($request->password),
            'is_active'  => $request->boolean('is_active', true),
        ]);

        $user->assignRole($request->role);

        if (in_array($request->role, ['area_admin', 'viewer']) && $request->filled('areas')) {
            $user->areas()->sync($request->areas);
        }

        ActivityLogger::log('CREATE', "User created: {$user->name} (PIS: {$user->pis_number}) — Role: {$request->role}", [
            'model_type' => 'User', 'model_id' => $user->id,
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles       = $this->validRoles();
        $areas       = Area::where('is_active', true)->orderBy('name')->get();
        $currentRole = $user->roles->first()?->name;
        $assignedAreas = $user->areas->pluck('id')->toArray();
        return view('users.edit', compact('user', 'roles', 'areas', 'currentRole', 'assignedAreas'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'pis_number' => 'required|string|max:20|unique:users,pis_number,' . $user->id,
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'mobile_no'  => 'nullable|string|max:15',
            'password'   => 'nullable|string|min:8|confirmed',
            'role'       => 'required|in:super_admin,admin,area_admin,viewer',
            'areas'      => 'nullable|array',
            'areas.*'    => 'exists:areas,id',
        ]);

        $changes = [];
        foreach (['name','pis_number','email','mobile_no'] as $f) {
            if ($user->$f !== $request->$f) {
                $changes[$f] = ['old' => $user->$f, 'new' => $request->$f];
            }
        }

        $user->update([
            'name'       => $request->name,
            'pis_number' => strtoupper($request->pis_number),
            'email'      => $request->email,
            'mobile_no'  => $request->mobile_no,
            'is_active'  => $request->boolean('is_active'),
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
            $changes['password'] = ['old' => '***', 'new' => '*** (changed)'];
        }

        $oldRole = $user->roles->first()?->name;
        $user->syncRoles([$request->role]);
        if ($oldRole !== $request->role) {
            $changes['role'] = ['old' => $oldRole, 'new' => $request->role];
        }

        if (in_array($request->role, ['area_admin', 'viewer'])) {
            $user->areas()->sync($request->areas ?? []);
        } else {
            $user->areas()->detach();
        }

        if (!empty($changes)) {
            ActivityLogger::log('UPDATE', "User updated: {$user->name} (PIS: {$user->pis_number})", [
                'model_type' => 'User', 'model_id' => $user->id, 'changes' => $changes,
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        ActivityLogger::log('DELETE', "User deleted: {$user->name} (PIS: {$user->pis_number})", [
            'model_type' => 'User', 'model_id' => $user->id,
        ]);
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted.');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        ActivityLogger::log('UPDATE', "User {$status}: {$user->name}", ['model_type'=>'User','model_id'=>$user->id]);
        return back()->with('success', "User {$status} successfully.");
    }
}
