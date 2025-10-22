<?php
/**
 * Admin Password Checker & Fixer
 * This script checks if admin password is correct and fixes it if needed
 */

require_once __DIR__ . '/config.php';

echo "=== ADMIN PASSWORD CHECKER ===\n\n";

// Check if admin user exists
$sql = "SELECT id, fullname, email, password_hash, role FROM users WHERE email = 'admin@dairy-x.com' AND role = 'admin'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "❌ ERROR: Admin user not found in database!\n";
    echo "\nCreating admin user...\n";
    
    $password_hash = password_hash('admin123', PASSWORD_BCRYPT);
    $insertSql = "INSERT INTO users (fullname, email, phone, password_hash, role) 
                  VALUES ('Admin User', 'admin@dairy-x.com', '+91 9000000000', ?, 'admin')";
    
    if ($stmt = $conn->prepare($insertSql)) {
        $stmt->bind_param('s', $password_hash);
        if ($stmt->execute()) {
            echo "✅ Admin user created successfully!\n";
            echo "Username: admin@dairy-x.com\n";
            echo "Password: admin123\n";
        } else {
            echo "❌ Failed to create admin user\n";
        }
        $stmt->close();
    }
} else {
    $admin = $result->fetch_assoc();
    echo "✅ Admin user found:\n";
    echo "   Email: " . $admin['email'] . "\n";
    echo "   Name: " . $admin['fullname'] . "\n";
    echo "   Role: " . $admin['role'] . "\n\n";
    
    // Test password
    echo "Testing password 'admin123'...\n";
    if (password_verify('admin123', $admin['password_hash'])) {
        echo "✅ Password 'admin123' is CORRECT!\n";
        echo "\n=== YOUR CREDENTIALS ===\n";
        echo "Username: admin OR admin@dairy-x.com\n";
        echo "Password: admin123\n";
        echo "URL: http://localhost/Project/login.html\n";
    } else {
        echo "❌ Password 'admin123' does NOT match!\n";
        echo "\nFixing password...\n";
        
        $new_hash = password_hash('admin123', PASSWORD_BCRYPT);
        $updateSql = "UPDATE users SET password_hash = ? WHERE email = 'admin@dairy-x.com' AND role = 'admin'";
        
        if ($stmt = $conn->prepare($updateSql)) {
            $stmt->bind_param('s', $new_hash);
            if ($stmt->execute()) {
                echo "✅ Password has been reset to 'admin123'\n";
                echo "\n=== YOUR CREDENTIALS ===\n";
                echo "Username: admin OR admin@dairy-x.com\n";
                echo "Password: admin123\n";
                echo "URL: http://localhost/Project/login.html\n";
            } else {
                echo "❌ Failed to update password\n";
            }
            $stmt->close();
        }
    }
}

echo "\n=== NEXT STEPS ===\n";
echo "1. Go to: http://localhost/Project/login.html\n";
echo "2. Click 'Admin Login' tab\n";
echo "3. Enter username: admin\n";
echo "4. Enter password: admin123\n";
echo "5. Click 'Login as Admin'\n";

$conn->close();
?>
