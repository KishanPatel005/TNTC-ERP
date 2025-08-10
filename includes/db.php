<?php
require_once __DIR__ . '/../config.php';

function db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) return $conn;
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_errno) {
        http_response_code(500);
        die('Database connection failed: ' . htmlspecialchars($conn->connect_error));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function db_query(string $sql, string $types = '', array $params = []) {
    $mysqli = db();
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('DB Prepare failed: ' . $mysqli->error);
    }
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        throw new Exception('DB Execute failed: ' . $stmt->error);
    }
    return $stmt;
}

function db_fetch_all(mysqli_stmt $stmt): array {
    $res = $stmt->get_result();
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function db_fetch_one(mysqli_stmt $stmt): ?array {
    $res = $stmt->get_result();
    if (!$res) return null;
    $row = $res->fetch_assoc();
    return $row ?: null;
}
