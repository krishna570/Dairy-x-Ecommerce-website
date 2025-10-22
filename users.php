<?php
require_once __DIR__ . '/config.php';

function fetchUsers($conn) {
    $users = [];
    $queries = [
        // Common schemas
        'SELECT id, fullname, email, phone, created_at FROM users ORDER BY id DESC',
        'SELECT id, name AS fullname, email, phone, created_at FROM users ORDER BY id DESC',
        'SELECT id, fullname, email, phone, created_at FROM user ORDER BY id DESC',
        'SELECT id, name AS fullname, email, phone, created_at FROM user ORDER BY id DESC',
        // Minimal fallback
        'SELECT id, email FROM users ORDER BY id DESC',
        'SELECT id, email FROM user ORDER BY id DESC',
    ];

    foreach ($queries as $sql) {
        if ($res = $conn->query($sql)) {
            while ($row = $res->fetch_assoc()) { $users[] = $row; }
            $res->close();
            if (!empty($users)) break;
        }
    }
    return $users;
}

$users = fetchUsers($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Dairy-X</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body{font-family:Arial, sans-serif;background:#f5f7fa;margin:0}
        .header{background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%);color:#fff;padding:16px 24px;display:flex;align-items:center;justify-content:space-between}
        .header a{color:#fff;text-decoration:none;background:rgba(255,255,255,.2);padding:8px 12px;border-radius:6px;margin-left:8px}
        .container{max-width:1200px;margin:24px auto;padding:0 16px}
        .table{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.08);overflow:auto}
        table{width:100%;border-collapse:collapse;min-width:700px}
        thead{background:#e9f7ff}
        th,td{padding:12px 14px;border-bottom:1px solid #eee;text-align:left}
        tbody tr:hover{background:#fafafa}
    </style>
</head>
<body>
    <div class="header">
        <div><strong><i class="fas fa-users"></i> Users</strong></div>
        <div>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="admin.php"><i class="fas fa-shield-alt"></i> Admin</a>
        </div>
    </div>

    <div class="container">
        <div class="table">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($users)) { $i=1; foreach ($users as $u) { ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($u['fullname'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['email'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['phone'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['created_at'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php } } else { ?>
                    <tr>
                        <td colspan="5" style="text-align:center;color:#666;">No users found. Create a `users` table or adjust the fields to match your schema.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
