<?php
/**
 * Authentication API - Backend for Login/Signup/Logout
 * Handles: User Registration, Login, Admin Login, Logout
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'register':
        registerUser($conn);
        break;
    
    case 'login':
        loginUser($conn);
        break;
    
    case 'admin_login':
        adminLogin($conn);
        break;
    
    case 'logout':
        logout();
        break;
    
    case 'check':
        checkAuth();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Register a new user
 */
function registerUser($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['fullname']) || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    $fullname = sanitize_input($data['fullname']);
    $email = sanitize_input($data['email']);
    $phone = isset($data['phone']) ? sanitize_input($data['phone']) : null;
    $password = $data['password'];
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    // Check if email already exists
    $checkSql = "SELECT id FROM users WHERE email = ?";
    if ($checkStmt = $conn->prepare($checkSql)) {
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert user
    $sql = "INSERT INTO users (fullname, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'user')";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ssss', $fullname, $email, $phone, $password_hash);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['role'] = 'user';
            
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful',
                'user' => [
                    'id' => $user_id,
                    'fullname' => $fullname,
                    'email' => $email,
                    'role' => 'user'
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Registration failed']);
        }
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

/**
 * Login user
 */
function loginUser($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email and password required']);
        return;
    }
    
    $email = sanitize_input($data['email']);
    $password = $data['password'];
    
    $sql = "SELECT id, fullname, email, phone, password_hash, role FROM users WHERE email = ? AND role = 'user'";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('s', $email);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Verify password
                if (password_verify($password, $row['password_hash'])) {
                    // Set session
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['fullname'] = $row['fullname'];
                    $_SESSION['phone'] = $row['phone'];
                    $_SESSION['role'] = $row['role'];
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => [
                            'id' => $row['id'],
                            'fullname' => $row['fullname'],
                            'email' => $row['email'],
                            'phone' => $row['phone'],
                            'role' => $row['role']
                        ]
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'Invalid password']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Login failed']);
        }
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

/**
 * Admin login
 */
function adminLogin($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username and password required']);
        return;
    }
    
    $username = sanitize_input($data['username']);
    $password = $data['password'];
    
    // Support both email and 'admin' username
    $searchEmail = $username;
    if ($username === 'admin') {
        $searchEmail = 'admin@dairy-x.com';
    }
    
    // Check against admin email
    $sql = "SELECT id, fullname, email, password_hash, role FROM users WHERE email = ? AND role = 'admin'";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('s', $searchEmail);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Verify password
                $validPassword = password_verify($password, $row['password_hash']);
                
                if ($validPassword) {
                    // Set session
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['fullname'] = $row['fullname'];
                    $_SESSION['role'] = 'admin';
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Admin login successful',
                        'user' => [
                            'id' => $row['id'],
                            'fullname' => $row['fullname'],
                            'email' => $row['email'],
                            'role' => 'admin'
                        ]
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'Invalid credentials. Please check your password.']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Admin not found']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Login failed']);
        }
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

/**
 * Logout
 */
function logout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

/**
 * Check authentication status
 */
function checkAuth() {
    if (is_logged_in()) {
        echo json_encode([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['email'],
                'fullname' => $_SESSION['fullname'] ?? '',
                'role' => $_SESSION['role'] ?? 'user'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'authenticated' => false
        ]);
    }
}
?>
