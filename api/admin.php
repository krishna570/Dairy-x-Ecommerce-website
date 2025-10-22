<?php
/**
 * Admin API - Backend for Admin Dashboard
 * Handles: Get All Orders, Users, Carts, Statistics
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check if user is admin
if (!is_logged_in() || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden. Admin access required.']);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'users':
        getAllUsers($conn);
        break;
    
    case 'carts':
        getAllCarts($conn);
        break;
    
    case 'orders':
        getAllOrders($conn);
        break;
    
    case 'statistics':
        getStatistics($conn);
        break;
    
    case 'update_order_status':
        updateOrderStatus($conn);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Get all registered users
 */
function getAllUsers($conn) {
    $sql = "SELECT id, fullname, email, phone, role, created_at
            FROM users
            WHERE role = 'user'
            ORDER BY created_at DESC";
    
    if ($result = $conn->query($sql)) {
        $users = [];
        
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id' => $row['id'],
                'fullname' => $row['fullname'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'role' => $row['role'],
                'created_at' => $row['created_at']
            ];
        }
        
        echo json_encode(['success' => true, 'users' => $users]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch users']);
    }
}

/**
 * Get all active carts
 */
function getAllCarts($conn) {
    $sql = "SELECT c.id, c.user_id, c.quantity, c.added_at,
                   u.email as user_email, u.fullname as user_name,
                   p.name as product_name, p.price as product_price, p.image as product_image
            FROM cart c
            JOIN users u ON c.user_id = u.id
            JOIN products p ON c.product_id = p.id
            ORDER BY c.added_at DESC";
    
    if ($result = $conn->query($sql)) {
        $carts = [];
        
        while ($row = $result->fetch_assoc()) {
            $carts[] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'user_email' => $row['user_email'],
                'user_name' => $row['user_name'],
                'product_name' => $row['product_name'],
                'product_price' => (float)$row['product_price'],
                'product_image' => $row['product_image'],
                'quantity' => (int)$row['quantity'],
                'total_price' => (float)$row['product_price'] * (int)$row['quantity'],
                'added_at' => $row['added_at']
            ];
        }
        
        echo json_encode(['success' => true, 'carts' => $carts]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch carts']);
    }
}

/**
 * Get all orders
 */
function getAllOrders($conn) {
    $sql = "SELECT o.id, o.user_id, o.fullname, o.phone, o.email, o.address, o.city, o.state, o.pincode,
                   o.total_amount, o.delivery_fee, o.tax_amount, o.status, o.payment_method, 
                   o.payment_status, o.transaction_id, o.order_date,
                   u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.order_date DESC";
    
    if ($result = $conn->query($sql)) {
        $orders = [];
        
        while ($row = $result->fetch_assoc()) {
            $order = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'user_email' => $row['user_email'],
                'fullname' => $row['fullname'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'address' => $row['address'],
                'city' => $row['city'],
                'state' => $row['state'],
                'pincode' => $row['pincode'],
                'total_amount' => (float)$row['total_amount'],
                'delivery_fee' => (float)$row['delivery_fee'],
                'tax_amount' => (float)$row['tax_amount'],
                'status' => $row['status'],
                'payment_method' => $row['payment_method'],
                'payment_status' => $row['payment_status'],
                'transaction_id' => $row['transaction_id'],
                'order_date' => $row['order_date']
            ];
            
            // Get order items
            $order['items'] = getOrderItems($conn, $row['id']);
            $orders[] = $order;
        }
        
        echo json_encode(['success' => true, 'orders' => $orders]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch orders']);
    }
}

/**
 * Get order items
 */
function getOrderItems($conn, $order_id) {
    $sql = "SELECT oi.quantity, oi.unit_price, p.name, p.image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?";
    
    $items = [];
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $order_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $items[] = [
                    'name' => $row['name'],
                    'quantity' => (int)$row['quantity'],
                    'price' => (float)$row['unit_price'],
                    'image' => $row['image']
                ];
            }
        }
        
        $stmt->close();
    }
    
    return $items;
}

/**
 * Get dashboard statistics
 */
function getStatistics($conn) {
    // Total users
    $userSql = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    $userResult = $conn->query($userSql);
    $totalUsers = $userResult->fetch_assoc()['total'];
    
    // Total orders
    $orderSql = "SELECT COUNT(*) as total FROM orders";
    $orderResult = $conn->query($orderSql);
    $totalOrders = $orderResult->fetch_assoc()['total'];
    
    // Total revenue
    $revenueSql = "SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'";
    $revenueResult = $conn->query($revenueSql);
    $totalRevenue = $revenueResult->fetch_assoc()['total'] ?? 0;
    
    // Total products
    $productSql = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
    $productResult = $conn->query($productSql);
    $totalProducts = $productResult->fetch_assoc()['total'];
    
    // Pending orders
    $pendingSql = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
    $pendingResult = $conn->query($pendingSql);
    $pendingOrders = $pendingResult->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'statistics' => [
            'total_users' => (int)$totalUsers,
            'total_orders' => (int)$totalOrders,
            'total_revenue' => (float)$totalRevenue,
            'total_products' => (int)$totalProducts,
            'pending_orders' => (int)$pendingOrders
        ]
    ]);
}

/**
 * Update order status
 */
function updateOrderStatus($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['order_id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID and status required']);
        return;
    }
    
    $order_id = (int)$data['order_id'];
    $status = sanitize_input($data['status']);
    
    // Validate status
    if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('si', $status, $order_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Order status updated']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Order not found']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
