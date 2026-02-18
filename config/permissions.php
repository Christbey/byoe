<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Definitions
    |--------------------------------------------------------------------------
    |
    | Define all available roles in the marketplace application.
    |
    */
    'roles' => [
        'admin' => [
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Full access to all features and settings',
        ],
        'support' => [
            'name' => 'support',
            'display_name' => 'Support Staff',
            'description' => 'Can view and manage disputes and user issues',
        ],
        'ops' => [
            'name' => 'ops',
            'display_name' => 'Operations',
            'description' => 'Can manage bookings and operational tasks',
        ],
        'shop_owner' => [
            'name' => 'shop_owner',
            'display_name' => 'Shop Owner',
            'description' => 'Owns a coffee shop and can post service requests',
        ],
        'shop_manager' => [
            'name' => 'shop_manager',
            'display_name' => 'Shop Manager',
            'description' => 'Manages a coffee shop on behalf of owner',
        ],
        'provider' => [
            'name' => 'provider',
            'display_name' => 'Service Provider',
            'description' => 'Independent contractor providing services',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Definitions
    |--------------------------------------------------------------------------
    |
    | Define all available permissions in the marketplace application.
    |
    */
    'permissions' => [
        // Shop permissions
        'create_shop' => 'Create a new shop',
        'update_shop' => 'Update shop details',
        'delete_shop' => 'Delete a shop',
        'manage_locations' => 'Manage shop locations',
        'create_service_requests' => 'Create service requests',
        'cancel_service_requests' => 'Cancel service requests',

        // Provider permissions
        'create_provider_profile' => 'Create provider profile',
        'update_provider_profile' => 'Update provider profile',
        'accept_service_requests' => 'Accept service requests',
        'complete_bookings' => 'Mark bookings as complete',
        'submit_w9' => 'Submit W-9 tax form',

        // Booking permissions
        'view_all_bookings' => 'View all bookings',
        'cancel_bookings' => 'Cancel bookings',
        'rate_bookings' => 'Rate completed bookings',

        // Payment permissions
        'view_payments' => 'View payment history',
        'process_refunds' => 'Process payment refunds',

        // Admin permissions
        'manage_users' => 'Manage user accounts',
        'manage_disputes' => 'Manage dispute resolution',
        'view_audit_logs' => 'View audit logs',
        'manage_settings' => 'Manage platform settings',
        'impersonate_users' => 'Impersonate other users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-Permission Mapping
    |--------------------------------------------------------------------------
    |
    | Map permissions to roles.
    |
    */
    'role_permissions' => [
        'admin' => '*', // All permissions

        'support' => [
            'manage_disputes',
            'view_audit_logs',
            'view_all_bookings',
        ],

        'ops' => [
            'view_all_bookings',
            'cancel_bookings',
            'process_refunds',
            'view_audit_logs',
        ],

        'shop_owner' => [
            'create_shop',
            'update_shop',
            'manage_locations',
            'create_service_requests',
            'cancel_service_requests',
            'complete_bookings',
            'rate_bookings',
            'view_payments',
        ],

        'shop_manager' => [
            'create_service_requests',
            'cancel_service_requests',
            'complete_bookings',
            'rate_bookings',
            'view_payments',
        ],

        'provider' => [
            'create_provider_profile',
            'update_provider_profile',
            'accept_service_requests',
            'complete_bookings',
            'rate_bookings',
            'submit_w9',
            'view_payments',
        ],
    ],
];
