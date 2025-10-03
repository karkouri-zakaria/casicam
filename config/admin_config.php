<?php
// Admin configuration file
// This file should be kept secure and not accessible from the web
$password_hash = password_hash('C@sicam2026', PASSWORD_DEFAULT);

return [
    'admin_password_hash' => $password_hash,
    'session_timeout' => 900,
];
?>