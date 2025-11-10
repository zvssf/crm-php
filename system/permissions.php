<?php

return [
    'director' => [
        'text'  => 'Директор',
        'pages' => [
            'notfound', 'profile', 'dashboard', 'customers', 'finance', 'new-customer', 'edit-customer',
            'settings-centers', 'settings-countries', 'settings-cities', 'new-city', 'edit-city',
            'settings-inputs', 'clients', 'new-client', 'edit-client', 'settings-pdf-rules', 'pdf-upload',
        ],
        'forms' => [
            'profile-edit', 'profile-new-password', 'new-customer', 'edit-info-customer', 'edit-customer-new-password',
            'restore-customer', 'del-customer', 'new-center', 'edit-center', 'del-center', 'restore-center',
            'new-input', 'edit-input', 'del-input', 'restore-input', 'new-country', 'edit-country', 'del-country',
            'restore-country', 'new-city', 'edit-city', 'del-city', 'restore-city', 'new-cash', 'edit-cash',
            'del-cash', 'restore-cash', 'new-transaction', 'edit-transaction', 'del-transaction', 'restore-transaction',
            'new-supplier', 'edit-supplier', 'del-supplier', 'restore-supplier', 'confirm-client', 'restore-client', 
            'new-client', 'del-client', 'edit-client', 'review-client', 'approve-client', 'decline-client', 'approve-draft-director',
            'check-client-completeness', 'get-client-categories', 'get-additional-fields',
            'get-min-sale-price', 'pay_client_credit', 'revert-recorded-client', 'get-center-fields', 'get_export_fields', 'export_clients_excel', 
            'mass-client-action', 'mass-center-action', 'mass-country-action', 'mass-city-action', 'mass-input-action', 'mass-customer-action',
            'edit-pdf-rule', 'upload-client-pdfs', 'delete-pdf-rule',
        ],
    ],
    'supervisor' => [
        'text'  => 'Руководитель',
        'pages' => [
            'notfound', 'profile', 'dashboard', 'finance', 'clients', 'customers', 'edit-client',
        ],
        'forms' => [
            'profile-edit', 'profile-new-password', 'get-additional-fields', 'get_export_fields', 'export_clients_excel',
            'new-transaction', 'new-cash', 'edit-cash', 'del-cash', 'restore-cash', 'new-supplier', 'edit-supplier', 'del-supplier', 'restore-supplier',
        ],
    ],
    'manager' => [
        'text'  => 'Менеджер',
        'pages' => [
            'notfound', 'profile', 'dashboard', 'clients', 'new-client', 'edit-client', 'customers',
        ],
        'forms' => [
            'profile-edit', 'profile-new-password', 'new-client', 'edit-client', 'del-client', 'restore-client',
            'review-client', 'approve-client-manager', 'decline-client', 'check-client-completeness', 
            'get-additional-fields', 'get-min-sale-price', 'pay_client_credit', 'get_export_fields', 'export_clients_excel', 'mass-client-action',
        ],
    ],
    'agent' => [
        'text'  => 'Агент',
        'pages' => [
            'notfound', 'profile', 'dashboard', 'clients', 'new-client', 'edit-client',
        ],
        'forms' => [
            'profile-edit', 'profile-new-password', 'new-client', 'edit-client', 'del-client', 'restore-client',
            'review-client', 'revert-rejection-client', 'check-client-completeness', 
            'get-additional-fields', 'get-min-sale-price', 'get_export_fields', 'export_clients_excel', 'mass-client-action',
        ],
    ],
];