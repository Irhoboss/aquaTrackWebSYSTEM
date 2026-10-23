<?php

use App\Http\Controllers\{AlertController, AuditController, AuthController, ContainerController, DashboardController,
    DriverController, InventoryController, MonitoringController, NotificationController, OrderManagementController, ProfileController, ReportController, StorefrontController, SupportChatController,
    SettingController, StockController, SupplierController, UserController};
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $products = \App\Models\Product::query()
        ->where('is_active', true)
        ->where('stock', '>', 0)
        ->orderBy('name')
        ->take(4)
        ->get();

    return view('landing', compact('products'));
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendReset'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/shop', [StorefrontController::class, 'index'])->middleware('can:shop.view')->name('store.index');
    Route::middleware('can:shop.view')->group(function () {
        Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart.index');
        Route::post('/cart/{product}', [StorefrontController::class, 'addToCart'])->name('cart.add');
        Route::patch('/cart/{product}', [StorefrontController::class, 'updateCart'])->name('cart.update');
        Route::delete('/cart/{product}', [StorefrontController::class, 'removeFromCart'])->name('cart.remove');
        Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout.index');
        Route::post('/checkout', [StorefrontController::class, 'placeOrder'])->name('checkout.store');
        Route::get('/my-orders', [StorefrontController::class, 'orders'])->name('orders.index');
        Route::get('/my-orders/{order}', [StorefrontController::class, 'showOrder'])->name('orders.show');
    });
    Route::get('/support-chat', [SupportChatController::class, 'index'])->name('support-chat.index');
    Route::get('/support-chat/updates', [SupportChatController::class, 'updates'])->name('support-chat.updates');
    Route::post('/support-chat', [SupportChatController::class, 'store'])->name('support-chat.store');

    Route::middleware('can:driver.access')->prefix('driver')->name('driver.')->group(function () {
        Route::get('/', [DriverController::class, 'index'])->name('dashboard');
        Route::get('/scan', [DriverController::class, 'scan'])->name('scan');
        Route::post('/scan/order', [DriverController::class, 'updateOrder'])->name('scan.order');
        Route::post('/scan/container', [DriverController::class, 'updateContainer'])->name('scan.container');
    });

    Route::middleware('can:orders.manage')->prefix('orders/manage')->name('operations.orders.')->group(function () {
        Route::get('/', [OrderManagementController::class, 'index'])->name('index');
        Route::patch('/{order}/status', [OrderManagementController::class, 'update'])->name('status');
    });

    // Profile (everyone)
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readall');
    Route::get('/notifications/summary', [NotificationController::class, 'summary'])->name('notifications.summary');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Dashboard
    Route::middleware('can:dashboard.view')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    });

    // pH monitoring
    Route::middleware('can:monitoring.view')->group(function () {
        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring/live', [MonitoringController::class, 'live'])->name('monitoring.live');
        Route::get('/readings', [MonitoringController::class, 'history'])->name('readings.index');
        Route::get('/readings/export', [MonitoringController::class, 'export'])->name('readings.export');
        Route::get('/readings/print', [MonitoringController::class, 'print'])->name('readings.print');
        Route::get('/readings/{reading}', [MonitoringController::class, 'show'])->name('readings.show');
    });

    // Alerts
    Route::middleware('can:alerts.view')->get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::middleware('can:alerts.manage')->group(function () {
        Route::post('/alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge'])->name('alerts.acknowledge');
        Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    });

    // Inventory
    Route::middleware('can:inventory.view')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory-export', [InventoryController::class, 'export'])->name('inventory.export');
        Route::get('/inventory-movements', [StockController::class, 'index'])->name('inventory.movements');
    });
    Route::middleware('can:inventory.manage')->group(function () {
        Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('/inventory/{item}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
        Route::post('/inventory/{item}/archive', [InventoryController::class, 'archive'])->name('inventory.archive');
        Route::post('/categories', [InventoryController::class, 'storeCategory'])->name('categories.store');
        Route::delete('/categories/{category}', [InventoryController::class, 'destroyCategory'])->name('categories.destroy');
    });
    Route::middleware('can:inventory.view')->get('/inventory/{item}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::middleware('can:stock.move')->post('/inventory/{item}/stock', [StockController::class, 'store'])->name('inventory.stock');

    // Suppliers
    Route::middleware('can:suppliers.manage')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    // Containers
    Route::middleware('can:containers.view')->group(function () {
        Route::get('/containers', [ContainerController::class, 'index'])->name('containers.index');
        Route::get('/containers-export', [ContainerController::class, 'export'])->name('containers.export');
    });
    Route::middleware('can:containers.manage')->group(function () {
        Route::get('/containers/create', [ContainerController::class, 'create'])->name('containers.create');
        Route::post('/containers', [ContainerController::class, 'store'])->name('containers.store');
        Route::get('/containers/{container}/edit', [ContainerController::class, 'edit'])->name('containers.edit');
        Route::put('/containers/{container}', [ContainerController::class, 'update'])->name('containers.update');
        Route::post('/containers/{container}/maintenance', [ContainerController::class, 'maintenance'])->name('containers.maintenance');
    });
    Route::middleware('can:containers.view')->get('/containers/{container}', [ContainerController::class, 'show'])->name('containers.show');
    Route::middleware('can:inspections.record')->post('/containers/{container}/inspections', [ContainerController::class, 'inspect'])->name('containers.inspect');

    // Reports & audit
    Route::middleware('can:reports.view')->get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::middleware('can:audit.view')->get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Owner only
    Route::middleware('can:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset');
    });
    Route::middleware('can:settings.manage')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
    Route::middleware('can:devices.manage')->group(function () {
        Route::post('/settings/devices', [SettingController::class, 'storeDevice'])->name('devices.store');
        Route::post('/settings/devices/{device}/regenerate', [SettingController::class, 'regenerate'])->name('devices.regenerate');
        Route::post('/settings/devices/{device}/toggle', [SettingController::class, 'toggle'])->name('devices.toggle');
    });
});
