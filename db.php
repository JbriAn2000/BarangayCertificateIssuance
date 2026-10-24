<?php
function getDb(): PDO
{
    $host = '127.0.0.1';
    $dbName = 'barangay';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$dbName;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        $adminDsn = "mysql:host=$host;charset=$charset";
        $admin = new PDO($adminDsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $admin->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO($dsn, $user, $pass, $options);
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'resident',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $emailCheck = $pdo->query("SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'users'
          AND column_name = 'email'
        LIMIT 1");

    if (!$emailCheck->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL UNIQUE AFTER username");
    }

    $roleCheck = $pdo->query("SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'users'
          AND column_name = 'role'
        LIMIT 1");

    if (!$roleCheck->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'resident' AFTER password");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            request_type VARCHAR(100) NOT NULL,
            full_name VARCHAR(255) NULL,
            address TEXT NULL,
            contact_number VARCHAR(50) NULL,
            purpose TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    foreach (['full_name', 'address', 'contact_number'] as $columnName) {
        $columnCheck = $pdo->query("SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'requests'
              AND column_name = '$columnName'
            LIMIT 1");

        if (!$columnCheck->fetch()) {
            $columnType = $columnName === 'address' ? 'TEXT NULL' : ($columnName === 'full_name' ? 'VARCHAR(255) NULL' : 'VARCHAR(50) NULL');
            $pdo->exec("ALTER TABLE requests ADD COLUMN $columnName $columnType AFTER request_type");
        }
    }

    return $pdo;
}
?>
