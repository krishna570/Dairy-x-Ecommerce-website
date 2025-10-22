<?php
/**
 * Cart API - Backend for Cart Operations
 * Handles: Add, Update, Delete, Get Cart Items
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
    case 'get':
        getCart($conn, $user_id);
        break;
    
    case 'add':
        addToCart($conn, $user_id);
        break;
    
    case 'update':
        updateCart($conn, $user_id);
        break;
    
    case 'delete':
        deleteFromCart($conn, $user_id);
        break;
    
    case 'clear':
        clearCart($conn, $user_id);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Get all cart items for a user
 */
function getCart($conn, $user_id) {
    $sql = "SELECT c.id, c.product_id, c.quantity, c.added_at,
                   p.name, p.price, p.image, cat.name as category
            FROM cart c
            JOIN products p ON c.product_id = p.id
            JOIN categories cat ON p.category_id = cat.id
            WHERE c.user_id = ?
            ORDER BY c.added_at DESC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $items = [];
            
            while ($row = $result->fetch_assoc()) {
                $items[] = [
                    'id' => $row['id'],
                    'product_id' => $row['product_id'],
                    'name' => $row['name'],
                    'price' => (float)$row['price'],
                    'quantity' => (int)$row['quantity'],
                    'image' => $row['image'],
                    'category' => $row['category'],
                    'added_at' => $row['added_at']
                ];
            }
            
            echo json_encode(['success' => true, 'items' => $items]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch cart']);
        }
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

/**
 * Add item to cart
 */
function addToCart($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['product_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    $product_id = (int)$data['product_id'];
    $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
    
    // Check if product exists
    $checkSql = "SELECT id FROM products WHERE id = ? AND status = 'active'";
    if ($checkStmt = $conn->prepare($checkSql)) {
        $checkStmt->bind_param('i', $product_id);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();
    }
    
    // Check if item already in cart
    $sql = "INSERT INTO cart (user_id, product_id, quantity) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('iiii', $user_id, $product_id, $quantity, $quantity);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product added to cart']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
        }
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

/**
 * Update cart item quantity
 */
function updateCart($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['product_id']) || !isset($data['quantity'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID and quantity required']);
        return;
    }
    
    $product_id = (int)$data['product_id'];
    $quantity = (int)$data['quantity'];
    
    if ($quantity <= 0) {
        // If quantity is 0 or negative, delete the item
        deleteFromCart($conn, $user_id, $product_id);
        return;
    }
    
    $sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('iii', $quantity, $user_id, $product_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Cart updated']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
        }
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

/**
 * Delete item from cart
 */
function deleteFromCart($conn, $user_id, $product_id = null) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($product_id === null) {
        if (!isset($data['product_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            return;
        }
        $product_id = (int)$data['product_id'];
    }
    
    $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ii', $user_id, $product_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
        }
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

/**
 * Clear entire cart
 */
function clearCart($conn, $user_id) {
    $sql = "DELETE FROM cart WHERE user_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cart cleared']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
        }
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
