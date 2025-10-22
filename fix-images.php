<?php
/**
 * Automatic Image Fixer - Updates database with existing image files
 */

require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Product Images - Dairy-X</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #667eea; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn { padding: 12px 25px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #5568d3; }
        pre { background: #2d3748; color: #68d391; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Fixing Product Images</h1>";

// Update products with existing images
$updates = 0;
$errors = 0;

// Sweet products - map to available images
$sweetImages = [
    'Milk Cake' => '1.webp',
    'Milk Mysore Pak' => '2.webp',
    'Jelly' => '3.webp',
    'Milk Cream' => '4.webp',
    'Rabri' => '5.JPG',
    'Malai Sandwich' => '6.jpg',
    'GulabJamun' => '7.jpg',
    'Kalakand' => '8.webp',
    'Karadant' => '9.jpg',
    'Barfi' => '10.webp',
    'Cham-cham' => '11.jpg',
    'Badam-barfi' => '12.webp'
];

foreach ($sweetImages as $productName => $imageName) {
    $imagePath = 'Sweet/' . $imageName;
    $sql = "UPDATE products SET image = ? WHERE name LIKE ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $likeName = '%' . $productName . '%';
        $stmt->bind_param('ss', $imagePath, $likeName);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo "<div class='success'>✅ Updated: {$productName} → {$imagePath}</div>";
            $updates++;
        }
        $stmt->close();
    }
}

// Milk products
$milkImages = [
    'White-makhan' => 'Homemade-white-makhan-02-500x375.jpg',
    'Butter' => 'Salted-or-Unsalted-Butter-Which-Should-I-Use-When.jpg',
    'Malai Paneer' => 'images.jpg',
    'Ricotta Cheese' => 'Homemade-Ricotta_-14.jpg',
    'Mozzarella Cheese' => 'mozzarella-pizza-recipe-instructions-120348.webp',
    'Cream Cheese' => 'Homemade-cream-cheese-Thumbnail-scaled.jpg',
    'Yogurt' => 'q11GhVAT.jpeg',
    'Fresh Milk' => 'Milk.avif',
    'Fermented milk' => 'image-asset.webp'
];

foreach ($milkImages as $productName => $imageName) {
    $imagePath = 'Milk/' . $imageName;
    $sql = "UPDATE products SET image = ? WHERE name LIKE ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $likeName = '%' . $productName . '%';
        $stmt->bind_param('ss', $imagePath, $likeName);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo "<div class='success'>✅ Updated: {$productName} → {$imagePath}</div>";
            $updates++;
        }
        $stmt->close();
    }
}

// Cream products
$creamImages = [
    'White-makhan' => 'Homemade-white-makhan-02-500x375.jpg',
    'Cream' => 'homemade-fresh-cream3.webp'
];

foreach ($creamImages as $productName => $imageName) {
    $imagePath = 'Milk/' . $imageName; // Using Milk folder for cream products too
    $sql = "UPDATE products SET image = ? WHERE name LIKE ? AND category_id = (SELECT id FROM categories WHERE name = 'Cream')";
    
    if ($stmt = $conn->prepare($sql)) {
        $likeName = '%' . $productName . '%';
        $stmt->bind_param('ss', $imagePath, $likeName);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo "<div class='success'>✅ Updated: {$productName} (Cream) → {$imagePath}</div>";
            $updates++;
        }
        $stmt->close();
    }
}

echo "<div class='info'><strong>Summary:</strong> Updated {$updates} product images</div>";

echo "<h3>✅ Images Fixed!</h3>";
echo "<p>Your product images have been updated to use the available image files.</p>";

echo "<div style='margin-top: 30px;'>";
echo "<a href='index.php' class='btn'>🏠 View Products</a>";
echo "<a href='check-images.php' class='btn'>📊 Check Images Again</a>";
echo "</div>";

echo "</div></body></html>";

$conn->close();
?>
