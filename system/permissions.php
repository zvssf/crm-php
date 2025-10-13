<?php

return [
    'director' => [
        'text'  => 'Директор',
        'pages' => [
            'notfound', 'profile', 'dashboard', 'customers', 'finance', 'new-customer', 'edit-customer',
            'settings-centers', 'settings-countries', 'settings-cities', 'new-city', 'edit-city',
            'settings-inputs', 'clients', 'settings-categories', 'new-client', 'edit-client',
        ],
        'forms' => [
            'profile-edit', 'profile-new-password', 'new-customer', 'edit-info-customer', 'edit-customer-new-password',
            'restore-customer', 'del-customer', 'new-center', 'edit-center', 'del-center', 'restore-center',
            'new-input', 'edit-input', 'del-input', 'restore-input', 'new-country', 'edit-country', 'del-country',
            'restore-country', 'new-city', 'edit-city', 'del-city', 'restore-city', 'new-cash', 'edit-cash',
            'del-cash', 'restore-cash', 'new-transaction', 'edit-transaction', 'del-transaction', 'restore-transaction',
            'new-supplier', 'edit-supplier', 'del-supplier', 'restore-supplier', 'new-category', 'edit-category',
            'del-category', 'restore-category', 'confirm-client', 'restore-client', 'new-client', 'del-client',
            'edit-client', 'review-client', 'approve-client', 'decline-client', 'approve-draft-director',
            'check-client-completeness', 'check-client-completeness', 'get-client-categories', 'get-additional-fields',
            'get-min-sale-price',
        ],
    ],
    'supervisor' => [
        'text'  => 'Руководитель',
        'pages' => [
            'notfound',
            'profile',
            'dashboard',
            'clients',
            'edit-client',
        ],
        'forms' => [
            'profile-edit',
            'profile-new-password', 'get-additional-fields'
        ],
    ],
    'manager' => [
        'text'  => 'Менеджер',
        'pages' => [
            'notfound', 'profile', 'dashboard', 'clients', 'new-client', 'edit-client',
        ],
        'forms' => [
            'profile-edit', 'profile-new-password', 'new-client', 'edit-client', 'del-client', 'restore-client',
            'review-client', 'approve-client-manager', 'decline-client', 'check-client-completeness', 'get-additional-fields',
            'get-min-sale-price',
        ],
    ],
    'agent' => [
        'text'  => 'Агент',
        'pages' => [
            'notfound', 'profile', 'dashboard', 'clients', 'new-client', 'edit-client',
        ],
        'forms' => [
            'profile-edit', 'profile-new-password', 'new-client', 'edit-client', 'del-client', 'restore-client',
            'review-client', 'revert-rejection-client', 'check-client-completeness', 'get-additional-fields', 'get-min-sale-price',
        ],
    ],
];