<?php

// The settings of the automatic tasks. Each one can be changed in the .env file.
return [

    // Smart dispatch: gives a Booked shipment a driver and a vehicle
    'auto_assign' => [
        'enabled'               => env('AUTOMATION_AUTO_ASSIGN', true),
        'wait_minutes'          => (int) env('AUTOMATION_ASSIGN_WAIT_MINUTES', 10),   // time the admin has to assign by hand first
        'max_active_per_driver' => (int) env('AUTOMATION_MAX_ACTIVE_PER_DRIVER', 5),
    ],

    // Delivered shipments become Closed after this many days
    'close_delivered' => [
        'enabled'    => env('AUTOMATION_CLOSE_DELIVERED', true),
        'after_days' => (int) env('AUTOMATION_CLOSE_AFTER_DAYS', 3),
    ],

    // Alerts for shipments that are stuck
    'stuck' => [
        'enabled'             => env('AUTOMATION_STUCK_ALERTS', true),
        'unassigned_hours'    => (int) env('AUTOMATION_UNASSIGNED_HOURS', 2),
        'not_picked_up_hours' => (int) env('AUTOMATION_NOT_PICKED_UP_HOURS', 12),
        'in_transit_hours'    => (int) env('AUTOMATION_IN_TRANSIT_HOURS', 48),
    ],

    // A summary of yesterday, sent to the admin
    'daily_report' => [
        'enabled' => env('AUTOMATION_DAILY_REPORT', true),
    ],
];