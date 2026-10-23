<?php

return [
    // Timestamps are STORED in UTC (config/app.php timezone = UTC) and DISPLAYED in this timezone.
    'display_tz' => env('APP_DISPLAY_TZ', 'Asia/Manila'),

    // Default values; the owner can change them in Settings (stored in system_settings).
    // NOTE: the pH range is a configurable starting point, NOT an official drinking-water standard.
    'defaults' => [
        'station_name'               => 'Philzen Water Refilling Station',
        'station_address'            => '',
        'station_contact'            => '',
        'logo'                       => '',
        'favicon'                    => '',
        'ph_min'                     => 6.5,
        'ph_max'                     => 8.5,
        'ph_valid_min'               => 0,
        'ph_valid_max'               => 14,
        'reporting_interval_seconds' => 60,
        'offline_minutes'            => 10,
        'default_min_stock'          => 5,
        'default_inspection_days'    => 30,
        'default_replacement_months' => 12,
        'registration_enabled'       => 1,
        'password_min_length'        => 8,
        'notify_ph_alerts'           => 1,
        'notify_inventory_alerts'    => 1,
        'notify_container_alerts'    => 1,
    ],

    // Which roles receive which alert types.
    'alert_roles' => [
        'ph_low'             => ['owner', 'manager'],
        'ph_high'            => ['owner', 'manager'],
        'sensor_offline'     => ['owner', 'manager'],
        'sensor_invalid'     => ['owner', 'manager'],
        'low_stock'          => ['owner', 'manager', 'staff'],
        'out_of_stock'       => ['owner', 'manager', 'staff'],
        'inspection_overdue' => ['owner', 'manager', 'staff'],
        'replacement_due'    => ['owner', 'manager', 'staff'],
    ],

    'alert_types' => [
        'ph_low' => 'pH below range', 'ph_high' => 'pH above range',
        'sensor_offline' => 'Sensor not reporting', 'sensor_invalid' => 'Invalid sensor reading',
        'low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock',
        'inspection_overdue' => 'Inspection overdue', 'replacement_due' => 'Replacement due',
    ],
];
