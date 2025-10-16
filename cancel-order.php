<?php
/**
 * CANCEL ORDER API
 * Xử lý hủy đơn hàng
 */

require_once 'config/config.php';

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user = getCurrentUser();

// Lấy dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$order_id = intval($input['order_id'] ?? 0);

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit;
}

try {
    // Kiểm tra đơn hàng có tồn tại và thuộc về user không
    $order = fetchOne("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ? AND order_status = 'pending'
    ", [$order_id, $user['id']]);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại hoặc không thể hủy']);
        exit;
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    // Lấy danh sách sản phẩm trong đơn hàng để hoàn trả stock
    $order_items = fetchData("
        SELECT product_id, quantity 
        FROM order_items 
        WHERE order_id = ?
    ", [$order_id]);
    
    // Hoàn trả stock cho các sản phẩm
    foreach ($order_items as $item) {
        executeQuery(
            "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
            [$item['quantity'], $item['product_id']]
        );
    }
    
    // Chuyển trạng thái đơn hàng thành 'cancelled'
    executeQuery("
        UPDATE orders 
        SET order_status = 'cancelled', 
            updated_at = NOW() 
        WHERE id = ?
    ", [$order_id]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Đơn hàng đã được hủy thành công'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Cancel order error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Có lỗi xảy ra khi hủy đơn hàng'
    ]);
}
?>
