<?php

// The settings of the automatic tasks. Each one can be changed in the .env file.
return [

    // Smart dispatch: gives a Booked shipment a driver and a vehicle
    'auto_assign' => [
        'enabled' => env('AUTOMATION_AUTO_ASSIGN', true),
        'assign_on_booking' => env('AUTOMATION_ASSIGN_ON_BOOKING', true),
        'wait_minutes' => (int) env('AUTOMATION_ASSIGN_WAIT_MINUTES', 0),   // 0 = assign right away, or N min for admin to assign first
        'max_active_per_driver' => (int) env('AUTOMATION_MAX_ACTIVE_PER_DRIVER', 5),
    ],

    // Delivered shipments become Closed after this many days
    'close_delivered' => [
        'enabled' => env('AUTOMATION_CLOSE_DELIVERED', true),
        'after_days' => (int) env('AUTOMATION_CLOSE_AFTER_DAYS', 3),
    ],

    // Alerts for shipments that are stuck or unassigned for too long
    'stuck' => [
        'enabled' => env('AUTOMATION_STUCK_ALERTS', true),
        'unassigned_hours' => (int) env('AUTOMATION_UNASSIGNED_HOURS', 24), // Late shipment alert after 24h
        'not_picked_up_hours' => (int) env('AUTOMATION_NOT_PICKED_UP_HOURS', 12),
        'in_transit_hours' => (int) env('AUTOMATION_IN_TRANSIT_HOURS', 48),
        'notify_admin' => env('AUTOMATION_STUCK_NOTIFY_ADMIN', true),
    ],

    // Payment reminders for unpaid shipments
    'payment_reminders' => [
        'enabled' => env('AUTOMATION_PAYMENT_REMINDERS', true),
        'after_days' => (int) env('AUTOMATION_PAYMENT_REMINDER_DAYS', 2), // Remind after 2 days
    ],

    // Auto-cancel bookings that stay unpaid and unconfirmed
    'auto_cancel' => [
        'enabled' => env('AUTOMATION_AUTO_CANCEL', true),
        'after_days' => (int) env('AUTOMATION_AUTO_CANCEL_DAYS', 7), // Auto-cancel after 7 days
    ],

    // A summary of yesterday, sent to the admin
    'daily_report' => [
        'enabled' => env('AUTOMATION_DAILY_REPORT', true),
    ],
];
