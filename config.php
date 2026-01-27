<?php
declare(strict_types=1);

$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    throw new RuntimeException('.env file not found');
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim($line);

    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);

    $key = trim($key);
    $value = trim($value);

    $value = trim($value, "\"'");

    if (getenv($key) === false) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}


function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $host = getenv('DB_HOST');
    $db = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());

        throw new RuntimeException('Database unavailable');
    }
    createDefaultUser($pdo);

    return $pdo;
}

function createDefaultUser(PDO $pdo): void
{
    $defaultUser = getenv('DEFAULT_USER');
    $defaultPass = getenv('DEFAULT_USER_PASS');

    if ($defaultUser === false || $defaultPass === false) {
        return;
    }
    $stmt = $pdo->prepare("SELECT id
                                         FROM users
                                         WHERE username = ? OR email=?");
    $stmt->execute([$defaultUser, "admin@mail.com"]);
    $row = $stmt->fetch();

    if ($row) {
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");

    $hash = password_hash($defaultPass, PASSWORD_DEFAULT);
    $stmt->execute([$defaultUser, "admin@mail.com", $hash]);

}

