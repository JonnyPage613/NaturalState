<?php
return [
 'dsn'=>getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=REPLACE;charset=utf8mb4',
 'db_user'=>getenv('DB_USER') ?: '', 'db_password'=>getenv('DB_PASSWORD') ?: '',
 'base_url'=>getenv('APP_URL') ?: 'http://localhost:8080',
 'storage'=>getenv('APP_STORAGE') ?: dirname(__DIR__).'/storage',
 'smtp_host'=>getenv('SMTP_HOST') ?: '', 'smtp_port'=>(int)(getenv('SMTP_PORT') ?: 587),
 'smtp_user'=>getenv('SMTP_USER') ?: '', 'smtp_password'=>getenv('SMTP_PASSWORD') ?: '',
 'smtp_security'=>getenv('SMTP_SECURITY') !== false ? getenv('SMTP_SECURITY') : 'tls',
 'mail_from'=>getenv('MAIL_FROM') ?: 'hello@example.invalid',
 'staff_alert'=>getenv('STAFF_ALERT') ?: '',
 'geocodio_key'=>getenv('GEOCODIO_KEY') ?: '',
];
