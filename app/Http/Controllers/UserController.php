<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('username', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->orderBy('name')->paginate(15)->withQueryString();
        return view('users.index', compact('users'));
    }

    public function create() { return view('users.form', ['user' => new User(['role' => 'staff', 'is_active' => true])]); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'username' => 'required|alpha_dash|max:40|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => ['nullable', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);
        $temp = Str::password(12, symbols: false);
        $user = User::create($data + ['password' => $temp, 'is_active' => true, 'must_change_password' => true]);
        Audit::log('user.created', $user, ['role' => $user->role]);
        return redirect()->route('users.show', $user)->with('success', 'Account created.')->with('temp_password', $temp);
    }

    public function show(User $user)
    {
        return view('users.show', ['user' => $user, 'activity' => AuditLog::where('user_id', $user->id)->latest('created_at')->paginate(10)]);
    }

    public function edit(User $user) { return view('users.form', compact('user')); }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'username' => ['required', 'alpha_dash', 'max:40', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $self = $user->id === $request->user()->id;

        // Nobody can elevate / demote / deactivate themselves.
        if ($self && ($data['role'] !== $user->role || !$data['is_active'])) {
            return back()->withInput()->with('error', 'You cannot change your own role or deactivate your own account.');
        }
        // Always keep at least one active owner.
        $losesOwner = $user->role === 'owner' && $user->is_active && ($data['role'] !== 'owner' || !$data['is_active']);
        if ($losesOwner && User::where('role', 'owner')->where('is_active', true)->where('id', '!=', $user->id)->doesntExist()) {
            return back()->withInput()->with('error', 'This is the last active owner. Create or activate another owner first.');
        }

        $roleChanged = $data['role'] !== $user->role;
        $statusChanged = $data['is_active'] !== $user->is_active;
        $user->update($data);
        if ($roleChanged) Audit::log('user.role_changed', $user, ['role' => $user->role]);
        if ($statusChanged) Audit::log($user->is_active ? 'user.activated' : 'user.deactivated', $user);
        Audit::log('user.updated', $user);
        return redirect()->route('users.show', $user)->with('success', 'Account updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $temp = Str::password(12, symbols: false);
        $user->update(['password' => $temp, 'must_change_password' => true]);
        Audit::log('user.password_reset', $user); // the temporary password itself is never logged
        return back()->with('success', 'Password reset. Give the temporary password to the user.')->with('temp_password', $temp);
    }
}
