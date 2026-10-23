<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\Audit;
use App\Support\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.show', [
            'user' => $request->user(),
            'activity' => AuditLog::where('user_id', $request->user()->id)->latest('created_at')->paginate(8),
        ]);
    }

    /** Users may edit only their own basic details. Role and status are NOT accepted here. */
    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'username' => ['required', 'alpha_dash', 'max:40', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        $user->update($data);
        Audit::log('profile.updated', $user, ['fields' => array_keys($request->except(['avatar', '_token', '_method']))]);
        return back()->with('success', 'Profile updated.');
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();
        if ($user->avatar) Storage::disk('public')->delete($user->avatar);
        $user->update(['avatar' => null]);
        Audit::log('profile.avatar_removed', $user);
        return back()->with('success', 'Profile photo removed.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', 'different:current_password', Password::min((int) Setting::get('password_min_length'))->letters()->numbers()],
        ]);
        $request->user()->update(['password' => $request->password, 'must_change_password' => false]);
        Audit::log('password.changed', $request->user());
        return back()->with('success', 'Password changed.');
    }
}
