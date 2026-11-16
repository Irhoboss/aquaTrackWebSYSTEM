<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrivacyAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_are_public_and_include_security_headers_and_cookie_notice(): void
    {
        foreach (['legal.privacy', 'legal.terms', 'legal.refunds', 'legal.cookies'] as $route) {
            $response = $this->get(route($route));
            $response->assertOk()
                ->assertSee('Business details')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Content-Security-Policy');
        }

        $response = $this->get(route('legal.privacy'));
        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' 'nonce-", $policy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
        preg_match("/script-src 'self' 'nonce-([^']+)'/", $policy, $policyNonce);
        preg_match('/<script nonce="([^"]+)"/', $response->getContent(), $viewNonce);
        $this->assertSame($policyNonce[1] ?? null, $viewNonce[1] ?? null);

        $this->get(route('home'))->assertOk()->assertSee('Essential cookies only');
        $this->get(route('login'))->assertOk()->assertSee('Cookie settings');
    }

    public function test_checkout_includes_the_configured_delivery_fee_and_stores_the_acknowledged_total(): void
    {
        $user = $this->customer('checkout-user', 'checkout@example.test');
        $product = Product::create([
            'name' => 'Refill',
            'price' => 100,
            'stock' => 5,
            'unit' => 'jug',
            'is_active' => true,
        ]);
        Setting::set('delivery_fee', 25.50);

        $this->actingAs($user)->withSession(['aq_cart' => [$product->id => 1]])
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('25.50');

        $payload = [
            'customer_name' => 'Test Buyer',
            'phone' => '09171234567',
            'delivery_address' => '1 Test Street',
            'delivery_fee_quote' => '25.50',
            'pricing_acknowledged' => '1',
        ];

        $this->actingAs($user)->withSession(['aq_cart' => [$product->id => 1]])
            ->post(route('checkout.store'), $payload)
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('25.50', $order->delivery_fee);
        $this->assertSame('125.50', $order->total);
    }

    public function test_customer_can_delete_account_and_order_identity_is_anonymized(): void
    {
        $user = $this->customer('delete-user', 'delete@example.test');
        $actor = User::create([
            'name' => 'Audit Actor',
            'username' => 'audit-actor',
            'email' => 'audit@example.test',
            'role' => 'admin',
            'is_active' => true,
            'password' => 'password',
        ]);
        DB::table('audit_logs')->insert([
            'user_id' => $actor->id,
            'action' => 'user.updated',
            'subject_type' => 'User',
            'subject_id' => $user->id,
            'subject_label' => $user->name,
            'details' => json_encode(['email' => $user->email]),
            'ip' => '127.0.0.1',
            'created_at' => now(),
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'AT-TEST-DELETE',
            'delivery_token' => Str::random(48),
            'status' => 'pending',
            'total' => 100,
            'delivery_fee' => 0,
            'customer_name' => $user->name,
            'phone' => '09171234567',
            'delivery_address' => '1 Private Street',
            'note' => 'Call me at my private number',
        ]);

        $this->actingAs($user)->delete(route('profile.account.destroy'), [
            'current_password' => 'password',
            'delete_confirmation' => 'DELETE',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $order->refresh();
        $this->assertSame('Deleted account', $order->customer_name);
        $this->assertSame('', $order->phone);
        $this->assertSame('', $order->delivery_address);
        $this->assertNull($order->note);
        $this->assertSame('100.00', $order->total);
        $this->assertDatabaseHas('users', ['id' => $order->user_id, 'is_active' => false]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'subject_type' => 'User',
            'subject_id' => null,
            'subject_label' => 'Deleted account',
            'details' => null,
        ]);
    }

    public function test_account_deletion_requires_the_current_password_and_exact_confirmation(): void
    {
        $user = $this->customer('keep-user', 'keep@example.test');

        $this->actingAs($user)->delete(route('profile.account.destroy'), [
            'current_password' => 'wrong-password',
            'delete_confirmation' => 'DELETE',
        ])->assertSessionHasErrors('current_password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    private function customer(string $username, string $email): User
    {
        return User::create([
            'name' => 'Test Customer',
            'username' => $username,
            'email' => $email,
            'role' => 'customer',
            'is_active' => true,
            'password' => 'password',
        ]);
    }
}
