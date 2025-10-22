<?php
/**
 * Image Checker & Fixer for Dairy-X Products
 * This script checks which product images are missing and helps you fix them
 */

require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Product Image Checker - Dairy-X</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #667eea; }
        .section { margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 8px; }
        .product { display: flex; align-items: center; padding: 15px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        .product.missing { background: #ffe6e6; border-color: #ff4444; }
        .product.found { background: #e6ffe6; border-color: #44ff44; }
        .product img { width: 100px; height: 100px; object-fit: cover; margin-right: 20px; border-radius: 5px; }
        .product .no-img { width: 100px; height: 100px; background: #ddd; display: flex; align-items: center; justify-content: center; margin-right: 20px; border-radius: 5px; color: #666; }
        .info { flex: 1; }
        .status { font-weight: bold; }
        .status.ok { color: #00aa00; }
        .status.error { color: #ff0000; }
        .fix-btn { padding: 8px 15px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .fix-btn:hover { background: #5568d3; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .folder-list { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .folder-list h3 { margin-top: 0; color: #667eea; }
        .folder-list ul { list-style: none; padding: 0; }
        .folder-list li { padding: 5px 0; }
        .instructions { background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 5px solid #ffc107; margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🖼️ Product Image Checker</h1>
        <p>This page shows which product images are missing and how to fix them.</p>";

// Check all products
$categories = ['Sweet', 'Milk', 'Cream'];
$totalProducts = 0;
$missingImages = 0;
$foundImages = 0;

foreach ($categories as $category) {
    echo "<div class='section'>";
    echo "<h2>📦 {$category} Products</h2>";
    
    $sql = "SELECT p.id, p.name, p.price, p.image, c.name as category
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE c.name = ?
            ORDER BY p.id";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('s', $category);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $totalProducts++;
            $imagePath = $row['image'];
            $fullPath = __DIR__ . '/' . $imagePath;
            $imageExists = file_exists($fullPath);
            
            $statusClass = $imageExists ? 'found' : 'missing';
            $statusText = $imageExists ? '✅ Image Found' : '❌ Image Missing';
            $statusColor = $imageExists ? 'ok' : 'error';
            
            if ($imageExists) {
                $foundImages++;
            } else {
                $missingImages++;
            }
            
            echo "<div class='product {$statusClass}'>";
            
            if ($imageExists) {
                echo "<img src='{$imagePath}' alt='{$row['name']}'>";
            } else {
                echo "<div class='no-img'>No Image</div>";
            }
            
            echo "<div class='info'>";
            echo "<strong>{$row['name']}</strong><br>";
            echo "Price: ₹{$row['price']}<br>";
            echo "Database Path: <code>{$imagePath}</code><br>";
            echo "Full Path: <code>{$fullPath}</code><br>";
            echo "<span class='status {$statusColor}'>{$statusText}</span>";
            echo "</div>";
            echo "</div>";
        }
        
        $stmt->close();
    }
    
    echo "</div>";
}

// Show summary
echo "<div class='section' style='background: #e7f3ff;'>";
echo "<h2>📊 Summary</h2>";
echo "<p>Total Products: <strong>{$totalProducts}</strong></p>";
echo "<p style='color: green;'>Images Found: <strong>{$foundImages}</strong></p>";
echo "<p style='color: red;'>Images Missing: <strong>{$missingImages}</strong></p>";
echo "</div>";

// Show available images in folders
echo "<div class='section'>";
echo "<h2>📁 Available Images in Folders</h2>";

$folders = ['Sweet', 'Milk', 'Cream', 'image'];
foreach ($folders as $folder) {
    $folderPath = __DIR__ . '/' . $folder;
    if (is_dir($folderPath)) {
        echo "<div class='folder-list'>";
        echo "<h3>{$folder}/</h3>";
        echo "<ul>";
        
        $files = scandir($folderPath);
        $imageFiles = array_filter($files, function($file) use ($folderPath) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'jfif']);
        });
        
        if (empty($imageFiles)) {
            echo "<li style='color: red;'>⚠️ No images found in this folder</li>";
        } else {
            foreach ($imageFiles as $file) {
                echo "<li>✓ {$file}</li>";
            }
        }
        
        echo "</ul>";
        echo "</div>";
    }
}
echo "</div>";

// Instructions
echo "<div class='instructions'>";
echo "<h2>🔧 How to Fix Missing Images</h2>";
echo "<ol>";
echo "<li><strong>Option 1: Add Your Images to Folders</strong>
        <ul>
            <li>Put Sweet product images in: <code>c:\\xampp\\htdocs\\Project\\Sweet\\</code></li>
            <li>Put Milk product images in: <code>c:\\xampp\\htdocs\\Project\\Milk\\</code></li>
            <li>Put Cream product images in: <code>c:\\xampp\\htdocs\\Project\\Cream\\</code></li>
        </ul>
    </li>";
echo "<li><strong>Option 2: Use Default/Placeholder Image</strong>
        <ul>
            <li>The system will show a default image if the specific image is missing</li>
        </ul>
    </li>";
echo "<li><strong>Option 3: Update Database with Correct Image Names</strong>
        <ul>
            <li>Make sure the image filename in database matches actual file</li>
            <li>Go to phpMyAdmin → dairy_ecommerce → products table</li>
            <li>Edit the 'image' column to match your actual image filenames</li>
        </ul>
    </li>";
echo "</ol>";
echo "</div>";

// Show fix button
echo "<div class='section' style='text-align: center;'>";
echo "<h3>Quick Fixes</h3>";
echo "<button class='fix-btn' onclick=\"location.href='fix-images.php'\">🔧 Auto-Fix Missing Images</button> ";
echo "<button class='fix-btn' onclick=\"location.href='index.php'\">🏠 Back to Home</button>";
echo "</div>";

echo "</div></body></html>";

$conn->close();
?>
