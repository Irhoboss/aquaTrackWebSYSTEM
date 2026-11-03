<?php

namespace Tests\Feature;

use App\Models\Container;
use App\Models\Device;
use App\Models\Alert;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_main_admin_pages_render(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);

        foreach ([
            '/', '/dashboard', '/alerts', '/audit', '/attendance', '/inventory', '/inventory-movements',
            '/monitoring', '/monitoring/live', '/readings', '/readings/print', '/reports', '/settings',
            '/users', '/suppliers', '/containers', '/orders/manage', '/notifications', '/support-chat', '/profile',
        ] as $path) {
            $this->get($path)->assertSuccessful();
        }

        $this->get('/inventory-export')->assertOk();
        $this->get('/containers-export')->assertOk();
        $this->get('/readings/export')->assertOk();
    }

    public function test_customer_can_register_and_complete_order_lifecycle(): void
    {
        Setting::set('registration_enabled', 1);
        $this->get('/register')->assertOk();
        $this->post('/register', [
            'name' => 'Aqua Customer', 'username' => 'aquacustomer', 'email' => 'customer@example.test',
            'password' => 'FreshPass2026', 'password_confirmation' => 'FreshPass2026',
        ])->assertRedirect('/login');
        $customer = User::where('username', 'aquacustomer')->firstOrFail();

        $product = Product::create(['name' => 'Test water jug', 'price' => 45, 'stock' => 5, 'unit' => 'jug', 'is_active' => true]);
        $this->actingAs($customer)->get('/shop')->assertOk();
        $this->post("/cart/{$product->id}", ['quantity' => 2])->assertRedirect();
        $this->get('/cart')->assertOk();
        $this->get('/checkout')->assertOk();
        $this->post('/checkout', [
            'customer_name' => $customer->name, 'phone' => '09171234567', 'delivery_address' => 'Test address',
        ])->assertRedirect();

        $order = Order::with('items')->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(1, $order->items->count());
        $this->get('/my-orders')->assertOk();
        $this->get("/my-orders/{$order->id}")->assertOk();

        $admin = $this->makeUser('admin');
        $this->actingAs($admin);
        foreach (['confirmed', 'preparing', 'out_for_delivery'] as $status) {
            $this->patch("/orders/manage/{$order->id}/status", ['status' => $status])->assertRedirect();
        }

        $driver = $this->makeUser('driver');
        $this->actingAs($driver)->get('/driver')->assertOk();
        $this->get('/driver/scan')->assertOk();
        $this->post('/driver/scan/order', ['token' => $order->delivery_token, 'status' => 'delivery_failed'])->assertRedirect();
        $this->assertSame('delivery_failed', $order->fresh()->status);
        $this->post('/driver/scan/order', ['token' => $order->delivery_token, 'status' => 'out_for_delivery'])->assertRedirect();
        $this->post('/driver/scan/order', ['token' => $order->delivery_token, 'status' => 'delivered'])->assertRedirect();
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame($driver->id, $order->fresh()->delivered_by);

        $this->actingAs($customer)->post("/cart/{$product->id}", ['quantity' => 1])->assertRedirect();
        $this->post('/checkout', [
            'customer_name' => $customer->name, 'phone' => '09171234567', 'delivery_address' => 'Test address',
        ])->assertRedirect();
        $cancelledOrder = Order::latest('id')->firstOrFail();
        $this->assertSame(2, $product->fresh()->stock);
        $this->actingAs($admin)->patch("/orders/manage/{$cancelledOrder->id}/status", ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_admin_inventory_and_supplier_workflow_preserves_stock_ledger(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);
        $this->get('/suppliers/create')->assertOk();
        $this->get('/inventory/create')->assertOk();
        $this->post('/categories', ['name' => 'Bottles'])->assertRedirect();
        $category = InventoryCategory::where('name', 'Bottles')->firstOrFail();
        $this->post('/suppliers', [
            'name' => 'Test supplier', 'status' => 'active', 'email' => 'supplier@example.test',
        ])->assertRedirect();
        $supplier = Supplier::where('name', 'Test supplier')->firstOrFail();

        $this->post('/inventory', [
            'sku' => 'TEST-001', 'name' => 'Test bottle', 'category_id' => $category->id, 'unit' => 'pcs',
            'quantity' => 10, 'min_stock' => 2, 'max_stock' => 50, 'supplier_id' => $supplier->id,
            'unit_cost' => 5, 'location' => 'Shelf A',
        ])->assertRedirect();
        $item = InventoryItem::where('sku', 'TEST-001')->firstOrFail();
        $this->assertSame(10.0, $item->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', ['inventory_item_id' => $item->id, 'quantity_after' => 10]);

        $this->post("/inventory/{$item->id}/stock", ['type' => 'out', 'quantity' => 3, 'reason' => 'Usage'])->assertRedirect();
        $this->assertSame(7.0, $item->fresh()->quantity);
        $this->post("/inventory/{$item->id}/stock", ['type' => 'out', 'quantity' => 99, 'reason' => 'Overdraw'])
            ->assertSessionHasErrors('quantity');
        $this->assertSame(7.0, $item->fresh()->quantity);
        $this->post("/inventory/{$item->id}/archive")->assertRedirect();
        $this->assertSame('archived', $item->fresh()->status);
        $this->delete("/inventory/{$item->id}")->assertSessionHas('error');

        $this->put("/suppliers/{$supplier->id}", ['name' => 'Test supplier updated', 'status' => 'inactive'])
            ->assertRedirect();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Test supplier updated']);
        $this->get('/suppliers')->assertOk();
        $this->get('/inventory-movements')->assertOk();
    }

    public function test_container_inspection_and_driver_container_status_flow(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get('/containers/create')->assertOk();
        $this->post('/containers', [
            'code' => 'JUG-TEST-01', 'type' => '20L jug', 'status' => 'available', 'condition' => 'good',
            'inspection_frequency_days' => 30,
        ])->assertRedirect();
        $container = Container::where('code', 'JUG-TEST-01')->firstOrFail();
        $this->get("/containers/{$container->id}")->assertOk();
        $this->post("/containers/{$container->id}/inspections", [
            'inspected_at' => today()->toDateString(), 'result' => 'pass', 'condition' => 'good',
        ])->assertRedirect();
        $this->assertDatabaseHas('container_inspections', ['container_id' => $container->id, 'result' => 'pass']);

        $this->post("/containers/{$container->id}/inspections", [
            'inspected_at' => today()->toDateString(), 'result' => 'fail', 'condition' => 'damaged',
            'corrective_action' => 'Repair the seal',
        ])->assertRedirect();
        $this->assertSame('under_maintenance', $container->fresh()->status);

        $driver = $this->makeUser('driver');
        $this->actingAs($driver)->get('/driver/scan?type=container&code=JUG-TEST-01')->assertOk();
        $this->post('/driver/scan/container', ['code' => 'JUG-TEST-01', 'status' => 'in_use'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->post("/containers/{$container->id}/maintenance", [
            'type' => 'repair', 'performed_at' => today()->toDateString(), 'description' => 'Seal repaired', 'cost' => 25,
        ])->assertRedirect();
        $this->assertSame('available', $container->fresh()->status);
        $this->actingAs($driver)->post('/driver/scan/container', ['code' => 'JUG-TEST-01', 'status' => 'in_use'])->assertRedirect();
        $this->assertSame('in_use', $container->fresh()->status);
    }

    public function test_sensor_api_auth_ingestion_duplicate_and_settings_update(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);
        $this->put('/settings', [
            'station_name' => 'Test station', 'station_address' => '', 'station_contact' => '',
            'ph_min' => 6.5, 'ph_max' => 8.5, 'reporting_interval_seconds' => 60, 'offline_minutes' => 15,
            'default_min_stock' => 2, 'default_inspection_days' => 30, 'default_replacement_months' => 12,
            'password_min_length' => 8,
        ])->assertRedirect();

        $device = Device::create(['device_id' => 'TEST-ESP32', 'name' => 'Test sensor', 'is_active' => true]);
        $token = $device->issueToken();
        $this->postJson('/api/v1/heartbeat', [], ['Authorization' => "Bearer {$token}"])->assertOk();
        $payload = ['device_id' => 'TEST-ESP32', 'ph_value' => 7.2, 'raw_voltage_mv' => 1500, 'measured_at' => now()->toIso8601String()];
        $this->postJson('/api/v1/readings', $payload, ['Authorization' => "Bearer {$token}"])->assertCreated();
        $this->postJson('/api/v1/readings', $payload, ['Authorization' => "Bearer {$token}"])->assertOk();
        $this->assertDatabaseCount('readings', 1);
        $this->postJson('/api/v1/heartbeat', [], ['Authorization' => 'Bearer invalid'])->assertUnauthorized();
        $this->post("/settings/devices/{$device->id}/regenerate")->assertRedirect();
        $newToken = session('new_token.token');
        $this->postJson('/api/v1/heartbeat', [], ['Authorization' => "Bearer {$token}"])->assertUnauthorized();
        $this->postJson('/api/v1/heartbeat', [], ['Authorization' => "Bearer {$newToken}"])->assertOk();
        $this->post("/settings/devices/{$device->id}/toggle")->assertRedirect();
        $this->postJson('/api/v1/heartbeat', [], ['Authorization' => "Bearer {$newToken}"])->assertUnauthorized();
        $this->actingAs($admin)->get('/monitoring/live')->assertOk();
    }

    public function test_role_boundaries_alerts_notifications_and_support_chat(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $driver = $this->makeUser('driver');
        $staff = $this->makeUser('staff');

        $this->actingAs($customer)->get('/shop')->assertOk();
        $this->get('/dashboard')->assertForbidden();
        $this->get('/support-chat')->assertOk();
        $this->post('/support-chat', ['body' => 'Please help with my order.'])->assertRedirect();
        $this->assertDatabaseHas('support_messages', ['user_id' => $customer->id, 'is_staff_reply' => false]);
        $this->get('/support-chat/updates')->assertOk()->assertJsonCount(1);

        $notification = UserNotification::create(['user_id' => $customer->id, 'title' => 'Test notice', 'body' => 'Hello']);
        $this->get('/notifications/summary')->assertOk()->assertJsonPath('unread_count', 1);
        $this->post("/notifications/{$notification->id}/read")->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($staff)->get('/attendance')->assertOk();
        $this->get('/monitoring')->assertForbidden();
        $this->actingAs($driver)->get('/driver')->assertOk();
        $this->get('/inventory')->assertForbidden();

        $alert = Alert::create([
            'type' => 'ph_low', 'severity' => 'warning', 'title' => 'Low pH test',
            'description' => 'Test alert', 'dedupe_key' => 'test-alert', 'status' => 'new',
        ]);
        $this->actingAs($admin)->post("/alerts/{$alert->id}/acknowledge")->assertRedirect();
        $this->assertSame('acknowledged', $alert->fresh()->status);
        $this->post("/alerts/{$alert->id}/resolve", ['resolution_notes' => 'Resolved during test'])->assertRedirect();
        $this->assertSame('resolved', $alert->fresh()->status);
        $this->get('/reports?type=user_activity')->assertOk();
    }

    public function test_admin_user_creation_and_self_protection(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);
        $this->get('/users/create')->assertOk();
        $this->post('/users', [
            'name' => 'New staff', 'username' => 'new-staff', 'email' => 'new-staff@example.test', 'role' => 'staff',
        ])->assertRedirect();
        $created = User::where('username', 'new-staff')->firstOrFail();
        $this->assertTrue($created->must_change_password);
        $this->assertNotEmpty(session('temp_password'));

        $this->put("/users/{$admin->id}", [
            'name' => $admin->name, 'username' => $admin->username, 'email' => $admin->email,
            'role' => 'staff', 'is_active' => 0,
        ])->assertSessionHas('error');
        $this->assertSame('admin', $admin->fresh()->role);

        $this->post("/users/{$created->id}/reset-password")->assertRedirect();
        $this->assertTrue($created->fresh()->must_change_password);
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create([
            'name' => ucfirst($role).' Test', 'username' => $role.'-'.uniqid(),
            'email' => $role.'-'.uniqid().'@example.test', 'role' => $role,
            'is_active' => true, 'must_change_password' => false,
        ]);
    }
}
