<?php
// app/config/mail.php

return [
    'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
    'port' => $_ENV['MAIL_PORT'] ?? 587,
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
    'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@shelfsense.com',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'ShelfSense POS',
    'timeout' => 30,
];