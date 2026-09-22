<?php
$host = getenv('TOETAP_DB_HOST') ?: 'localhost';
$db   = getenv('TOETAP_DB_NAME') ?: 'tapsole_v0';
$user = getenv('TOETAP_DB_USER') ?: 'root';
$pass = getenv('TOETAP_DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed.');
}
