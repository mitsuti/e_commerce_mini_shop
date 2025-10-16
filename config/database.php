<?php
/**
 * Cấu hình kết nối database
 * File này chứa thông tin kết nối đến MySQL database
 */

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Tạo kết nối PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Nếu database chưa tồn tại, tạo database trước
    if ($e->getCode() == 1049) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE " . DB_NAME);
            
            // Import dữ liệu mẫu
            $sqlFile = __DIR__ . '/../database/ecommerce_db_complete.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $statements = explode(';', $sql);
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement) && !preg_match('/^(--|\/\*)/', $statement)) {
                        try {
                            $pdo->exec($statement);
                        } catch (PDOException $e3) {
                            // Bỏ qua lỗi nếu bảng đã tồn tại
                        }
                    }
                }
            }
        } catch (PDOException $e2) {
            die("Lỗi tạo database: " . $e2->getMessage());
        }
    } else {
        die("Lỗi kết nối database: " . $e->getMessage());
    }
}

// Hàm helper để thực thi query
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Lỗi SQL: " . $e->getMessage());
        return false;
    }
}

// Hàm helper để lấy dữ liệu
function fetchData($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

// Hàm helper để lấy một record
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

// Hàm helper để đếm số records
function countRecords($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->rowCount() : 0;
}
?>
