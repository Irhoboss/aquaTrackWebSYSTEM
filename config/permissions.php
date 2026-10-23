<?php

/*
 | Role => abilities. '*' means every ability.
 | To change what a role can do, edit this file only. Routes use "can:<ability>" middleware,
 | and Blade uses @can('<ability>'), so there is nothing else to change.
 */
return [
    'roles' => [
        'admin' => ['*'],
        'owner' => ['*'],
        'manager' => [
            'dashboard.view', 'monitoring.view', 'alerts.view', 'alerts.manage',
            'inventory.view', 'inventory.manage', 'stock.move',
            'suppliers.manage',
            'containers.view', 'containers.manage', 'inspections.record',
            'reports.view', 'audit.view', 'support.chat', 'orders.manage',
        ],
        'staff' => [
            'dashboard.view', 'alerts.view',
            'inventory.view', 'stock.move',
            'containers.view', 'inspections.record', 'support.chat', 'orders.manage',
        ],
        'driver' => ['driver.access'],
        'customer' => ['shop.view', 'support.chat'],
    ],
    // Abilities only the Owner has (documented for clarity; owner has '*'):
    // users.manage, settings.manage, devices.manage, reports.sensitive, stock.negative
];
