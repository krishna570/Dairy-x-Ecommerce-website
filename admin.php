<?php
require_once __DIR__ . '/config.php';

function safeCount($conn, $sql) {
    $count = 0;
    if ($res = $conn->query($sql)) {
        if ($row = $res->fetch_row()) $count = (int)$row[0];
        $res->close();
    }
    return $count;
}

// Try common table names for counts
$productCount = 0;
$userCount = 0;

$productTables = [
    'products',
    'product'
];
$userTables = [
    'users',
    'user'
];

foreach ($productTables as $t) {
    $c = safeCount($conn, "SELECT COUNT(*) FROM `$t`");
    if ($c > 0) { $productCount = $c; break; }
}
foreach ($userTables as $t) {
    $c = safeCount($conn, "SELECT COUNT(*) FROM `$t`");
    if ($c > 0) { $userCount = $c; break; }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dairy-X</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body{font-family:Arial, sans-serif;background:#f5f7fa;margin:0}
        .admin-header{background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);color:#fff;padding:20px 40px;display:flex;justify-content:space-between;align-items:center}
        .admin-nav a{color:#fff;text-decoration:none;padding:10px 16px;background:rgba(255,255,255,0.2);border-radius:6px;margin-left:10px}
        .container{max-width:1200px;margin:24px auto;padding:0 16px}
        .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
        .card{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.08);padding:20px}
        .card h3{margin:0 0 8px}
        .links{margin-top:24px;display:flex;gap:12px;flex-wrap:wrap}
        .btn{display:inline-block;padding:10px 16px;border-radius:8px;border:2px solid #e0e0e0;background:#fff;text-decoration:none;color:#444;font-weight:600}
        .btn.primary{border-color:#667eea;color:#667eea}
    </style>
</head>
<body>
    <div class="admin-header">
        <h1><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>
        <div class="admin-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
        </div>
    </div>

    <div class="container">
        <div class="cards">
            <div class="card">
                <h3><i class="fas fa-box"></i> Products</h3>
                <div style="font-size:28px;font-weight:700;"><?php echo (int)$productCount; ?></div>
                <div class="links"><a class="btn primary" href="product.php">Manage Products</a></div>
            </div>
            <div class="card">
                <h3><i class="fas fa-users"></i> Users</h3>
                <div style="font-size:28px;font-weight:700;"><?php echo (int)$userCount; ?></div>
                <div class="links"><a class="btn primary" href="users.php">View Users</a></div>
            </div>
        </div>
    </div>
</body>
</html>
