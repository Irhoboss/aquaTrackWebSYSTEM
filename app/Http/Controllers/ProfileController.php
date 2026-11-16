<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AuthOtp;
use App\Models\User;
use App\Support\Audit;
use App\Support\EmailOtp;
use App\Support\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.show', [
            'user' => $request->user(),
            'activity' => AuditLog::where('user_id', $request->user()->id)->latest('created_at')->paginate(8),
            'passwordOtpPending' => AuthOtp::query()
                ->where('user_id', $request->user()->id)
                ->where('purpose', EmailOtp::PASSWORD_CHANGE)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->exists(),
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

    public function destroyAccount(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'customer', 403, 'Contact the account owner to request deletion of a staff account.');

        $request->validate([
            'current_password' => 'required|current_password',
            'delete_confirmation' => 'required|in:DELETE',
        ]);

        $avatar = $user->avatar;

        DB::transaction(function () use ($user) {
            $orders = DB::table('orders')->where('user_id', $user->id);
            if ($orders->exists()) {
                $deletedAccount = User::create([
                    'name' => 'Deleted account',
                    'username' => 'deleted-'.$user->id.'-'.Str::lower(Str::random(8)),
                    'email' => 'deleted+'.$user->id.'+'.Str::lower(Str::random(12)).'@example.invalid',
                    'role' => 'customer',
                    'is_active' => false,
                    'password' => Str::random(64),
                ]);

                $orders->update([
                    'user_id' => $deletedAccount->id,
                    'customer_name' => 'Deleted account',
                    'phone' => '',
                    'delivery_address' => '',
                    'note' => null,
                ]);
            }
            DB::table('auth_otps')->where('user_id', $user->id)->orWhere('email', $user->email)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('audit_logs')->where('user_id', $user->id)->delete();
            DB::table('audit_logs')
                ->where('subject_type', 'User')
                ->where('subject_id', $user->id)
                ->update(['subject_id' => null, 'subject_label' => 'Deleted account', 'details' => null]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('feedback')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        if ($avatar) {
            Storage::disk('public')->delete($avatar);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account was deleted. Order records were retained without your contact details.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', 'different:current_password', Password::min((int) Setting::get('password_min_length'))->letters()->numbers()],
        ]);

        EmailOtp::issue($request->user()->email, EmailOtp::PASSWORD_CHANGE, $request->user()->id, [
            'password_hash' => Hash::make($data['password']),
        ]);

        return back()->with('status', 'A verification code has been sent to your email. Enter it below to finish changing your password.');
    }

    public function verifyPasswordOtp(Request $request)
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $user = $request->user();
        $payload = EmailOtp::verify($user->email, EmailOtp::PASSWORD_CHANGE, $data['code'], $user->id);
        $passwordHash = $payload['password_hash'] ?? null;

        if (!is_string($passwordHash) || strlen($passwordHash) < 50) {
            throw ValidationException::withMessages(['code' => 'The password change request has expired. Start again to request a new code.']);
        }

        $user->forceFill(['password' => $passwordHash, 'must_change_password' => false])
            ->setRememberToken(Str::random(60));
        $user->save();
        Audit::log('password.changed', $user);

        return back()->with('success', 'Password changed after email verification.');
    }

    public function resendPasswordOtp(Request $request)
    {
        $user = $request->user();
        EmailOtp::resend($user->email, EmailOtp::PASSWORD_CHANGE, $user->id);

        return back()->with('status', 'A new verification code has been sent.');
    }
}
