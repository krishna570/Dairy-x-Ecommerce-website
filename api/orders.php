<?php
/**
 * Orders API - Backend for Order Management
 * Handles: Place Order, Get Orders, Update Order Status
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit();
}

$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'place':
        placeOrder($conn, $user_id);
        break;
    
    case 'get':
        getUserOrders($conn, $user_id);
        break;
    
    case 'details':
        getOrderDetails($conn, $user_id);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Place a new order
 */
function placeOrder($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['fullname', 'phone', 'email', 'address', 'city', 'state', 'pincode', 'items', 'payment_method'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }
    
    // Extract data
    $fullname = sanitize_input($data['fullname']);
    $phone = sanitize_input($data['phone']);
    $email = sanitize_input($data['email']);
    $address = sanitize_input($data['address']);
    $city = sanitize_input($data['city']);
    $state = sanitize_input($data['state']);
    $pincode = sanitize_input($data['pincode']);
    $landmark = isset($data['landmark']) ? sanitize_input($data['landmark']) : null;
    $payment_method = sanitize_input($data['payment_method']);
    $transaction_id = isset($data['transaction_id']) ? sanitize_input($data['transaction_id']) : null;
    $items = $data['items'];
    
    // Validate payment method
    if (!in_array($payment_method, ['cod', 'phonepe', 'qrcode'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
        return;
    }
    
    // Calculate totals
    $subtotal = 0;
    $delivery_fee = 50.00;
    
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $tax_amount = $subtotal * 0.05; // 5% tax
    $total_amount = $subtotal + $delivery_fee + $tax_amount;
    
    // Set payment status
    $payment_status = ($payment_method === 'cod') ? 'pending' : 'verified';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert order
        $sql = "INSERT INTO orders (user_id, fullname, phone, email, address, city, state, pincode, landmark,
                                   total_amount, delivery_fee, tax_amount, payment_method, payment_status, transaction_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('issssssssdddsss', 
                $user_id, $fullname, $phone, $email, $address, $city, $state, $pincode, $landmark,
                $total_amount, $delivery_fee, $tax_amount, $payment_method, $payment_status, $transaction_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create order');
            }
            
            $order_id = $conn->insert_id;
            $stmt->close();
        } else {
            throw new Exception('Database error');
        }
        
        // Insert order items
        $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
        
        if ($itemStmt = $conn->prepare($itemSql)) {
            foreach ($items as $item) {
                // Get product_id from database by name (or use product_id if provided)
                $product_id = isset($item['product_id']) ? $item['product_id'] : getProductIdByName($conn, $item['name']);
                
                if (!$product_id) {
                    throw new Exception('Product not found: ' . $item['name']);
                }
                
                $quantity = (int)$item['quantity'];
                $price = (float)$item['price'];
                
                $itemStmt->bind_param('iiid', $order_id, $product_id, $quantity, $price);
                
                if (!$itemStmt->execute()) {
                    throw new Exception('Failed to add order items');
                }
            }
            $itemStmt->close();
        } else {
            throw new Exception('Database error');
        }
        
        // Clear cart after successful order
        $clearSql = "DELETE FROM cart WHERE user_id = ?";
        if ($clearStmt = $conn->prepare($clearSql)) {
            $clearStmt->bind_param('i', $user_id);
            $clearStmt->execute();
            $clearStmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order placed successfully',
            'order_id' => $order_id,
            'total_amount' => $total_amount
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Get product ID by name
 */
function getProductIdByName($conn, $name) {
    $sql = "SELECT id FROM products WHERE name = ? LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['id'];
        }
        $stmt->close();
    }
    return null;
}

/**
 * Get all orders for a user
 */
function getUserOrders($conn, $user_id) {
    $sql = "SELECT id, fullname, phone, email, address, city, state, pincode, landmark,
                   total_amount, delivery_fee, tax_amount, status, payment_method, 
                   payment_status, transaction_id, order_date
            FROM orders
            WHERE user_id = ?
            ORDER BY order_date DESC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $orders = [];
            
            while ($row = $result->fetch_assoc()) {
                $order = [
                    'id' => $row['id'],
                    'fullname' => $row['fullname'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'address' => $row['address'],
                    'city' => $row['city'],
                    'state' => $row['state'],
                    'pincode' => $row['pincode'],
                    'landmark' => $row['landmark'],
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
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
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
 * Get order details
 */
function getOrderDetails($conn, $user_id) {
    if (!isset($_GET['order_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        return;
    }
    
    $order_id = (int)$_GET['order_id'];
    
    $sql = "SELECT id, fullname, phone, email, address, city, state, pincode, landmark,
                   total_amount, delivery_fee, tax_amount, status, payment_method, 
                   payment_status, transaction_id, order_date
            FROM orders
            WHERE id = ? AND user_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ii', $order_id, $user_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $order = [
                    'id' => $row['id'],
                    'fullname' => $row['fullname'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'address' => $row['address'],
                    'city' => $row['city'],
                    'state' => $row['state'],
                    'pincode' => $row['pincode'],
                    'landmark' => $row['landmark'],
                    'total_amount' => (float)$row['total_amount'],
                    'delivery_fee' => (float)$row['delivery_fee'],
                    'tax_amount' => (float)$row['tax_amount'],
                    'status' => $row['status'],
                    'payment_method' => $row['payment_method'],
                    'payment_status' => $row['payment_status'],
                    'transaction_id' => $row['transaction_id'],
                    'order_date' => $row['order_date']
                ];
                
                $order['items'] = getOrderItems($conn, $order_id);
                
                echo json_encode(['success' => true, 'order' => $order]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Order not found']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch order']);
        }
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
