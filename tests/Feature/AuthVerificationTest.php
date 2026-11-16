<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class AuthVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_email_otp_and_leaves_the_account_pending_admin_approval(): void
    {
        Mail::fake();
        $registration = [
            'name' => 'AquaTrack Buyer',
            'username' => 'aquatrack-buyer',
            'email' => 'buyer@example.test',
            'phone' => '09171234567',
            'password' => 'BuyerPass2026!',
            'password_confirmation' => 'BuyerPass2026!',
            'terms_accepted' => '1',
            'privacy_acknowledged' => '1',
            'age_confirmed' => '1',
        ];

        $this->post(route('register.store'), $registration)
            ->assertRedirect(route('register.verify', ['email' => $registration['email']]));
        $this->assertDatabaseMissing('users', ['email' => $registration['email']]);

        $code = $this->sentCode();
        $this->post(route('register.verify.submit'), ['email' => $registration['email'], 'code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('users', ['email' => $registration['email']]);

        $this->post(route('register.verify.submit'), ['email' => $registration['email'], 'code' => $code])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user = User::where('email', $registration['email'])->firstOrFail();
        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertNotNull($user->privacy_acknowledged_at);
        $this->assertNotNull($user->age_confirmed_at);
        $this->assertTrue(Hash::check($registration['password'], $user->password));
    }

    public function test_registration_requires_terms_privacy_and_age_acknowledgements(): void
    {
        Mail::fake();

        $this->post(route('register.store'), [
            'name' => 'Consent User',
            'username' => 'consent-user',
            'email' => 'consent@example.test',
            'password' => 'ConsentPass2026!',
            'password_confirmation' => 'ConsentPass2026!',
        ])->assertSessionHasErrors(['terms_accepted', 'privacy_acknowledged', 'age_confirmed']);

        Mail::assertNothingSent();
    }

    public function test_login_and_buyer_registration_are_options_on_the_same_auth_screen(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Choose sign-in or registration')
            ->assertSee(route('register'))
            ->assertSee('>Register</a>', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('<h2>Register</h2>', false)
            ->assertSee('Choose sign-in or registration')
            ->assertSee('aria-current="page"', false);
    }

    public function test_password_reset_requires_the_emailed_otp(): void
    {
        Mail::fake();
        $user = $this->createActiveUser('reset-user', 'reset@example.test');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.otp.form', ['email' => $user->email]));
        $code = $this->sentCode();

        $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'code' => '000000',
            'password' => 'ResetPass2026!',
            'password_confirmation' => 'ResetPass2026!',
        ])->assertSessionHasErrors('code');
        $this->assertFalse(Hash::check('ResetPass2026!', $user->fresh()->password));

        $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'code' => $code,
            'password' => 'ResetPass2026!',
            'password_confirmation' => 'ResetPass2026!',
        ])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('ResetPass2026!', $user->fresh()->password));
    }

    public function test_profile_password_change_requires_email_otp(): void
    {
        Mail::fake();
        $user = $this->createActiveUser('profile-user', 'profile@example.test');

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'ChangedPass2026!',
            'password_confirmation' => 'ChangedPass2026!',
        ])->assertRedirect();
        $this->assertFalse(Hash::check('ChangedPass2026!', $user->fresh()->password));

        $code = $this->sentCode();
        $this->actingAs($user->fresh())->post(route('profile.password.otp.verify'), ['code' => $code])
            ->assertRedirect();
        $this->assertTrue(Hash::check('ChangedPass2026!', $user->fresh()->password));
    }

    public function test_google_sign_in_only_authenticates_an_existing_active_account_with_verified_email(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);
        $user = $this->createActiveUser('google-user', 'google@example.test');
        Socialite::fake('google', GoogleUser::fake([
            'email' => $user->email,
            'email_verified' => true,
        ]));

        $this->get(route('google.callback'))
            ->assertRedirect(route('store.index'))
            ->assertSessionHas('signed_in', true);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_sign_in_can_access_the_system_without_changing_a_temporary_password(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);
        $user = User::create([
            'name' => 'Google User',
            'username' => 'google-temp-user',
            'email' => 'google-temp@example.test',
            'role' => 'customer',
            'is_active' => true,
            'must_change_password' => true,
            'password' => 'TemporaryPass2026!',
        ]);
        Socialite::fake('google', GoogleUser::fake([
            'email' => $user->email,
            'email_verified' => true,
        ]));

        $this->get(route('google.callback'))
            ->assertRedirect(route('store.index'))
            ->assertSessionHas('signed_in', true);
        $this->assertAuthenticatedAs($user);
        $this->get(route('store.index'))->assertOk();
        $this->assertTrue(Hash::check('TemporaryPass2026!', $user->fresh()->password));
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_google_sign_in_does_not_create_an_unregistered_account(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);
        Socialite::fake('google', GoogleUser::fake([
            'email' => 'new-google@example.test',
            'email_verified' => true,
        ]));

        $this->get(route('google.callback'))
            ->assertRedirect(route('register'))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('users', 0);
    }

    private function createActiveUser(string $username, string $email): User
    {
        return User::create([
            'name' => 'AquaTrack Test User',
            'username' => $username,
            'email' => $email,
            'role' => 'customer',
            'is_active' => true,
            'password' => 'password',
        ]);
    }

    private function sentCode(): string
    {
        $code = null;
        Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$code): bool {
            $code = $mail->code;
            return true;
        });

        $this->assertNotNull($code);
        return (string) $code;
    }
}
