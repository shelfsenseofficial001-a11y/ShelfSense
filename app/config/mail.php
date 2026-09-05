<?php
// app/config/mail.php
// Named profiles so different modules can send as a different mailbox
// (e.g. procurement emails from the supplier-facing address) without
// disturbing the existing default profile used by HR/apply/password-reset.

return [
    'default' => [
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => $_ENV['MAIL_PORT'] ?? 587,
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@shelfsense.com',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'ShelfSense POS',
        'timeout' => 30,
    ],
    'procurement' => [
        'host' => $_ENV['MAIL_PROCUREMENT_HOST'] ?? $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => $_ENV['MAIL_PROCUREMENT_PORT'] ?? $_ENV['MAIL_PORT'] ?? 587,
        'username' => $_ENV['MAIL_PROCUREMENT_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PROCUREMENT_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_PROCUREMENT_ENCRYPTION'] ?? $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_email' => $_ENV['MAIL_PROCUREMENT_FROM_EMAIL'] ?? 'noreply@shelfsense.com',
        'from_name' => $_ENV['MAIL_PROCUREMENT_FROM_NAME'] ?? 'ShelfSense Procurement',
        'timeout' => 30,
    ],
];