<?php

try {
    $conn = new PDO(
        "mysql:host=" . env('DB_HOST') . ";port=" . env('DB_PORT') . ";dbname=" . env('DB_NAME') . ";charset=utf8mb4",
        env('DB_USER'),
        env('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}