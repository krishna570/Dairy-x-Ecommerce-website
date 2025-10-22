<?php
require_once __DIR__ . '/config.php';

function fetchAllProducts($conn, $category = null) {
    $items = [];

    // Strategy A: products table with category text
    if ($category) {
        if ($stmt = $conn->prepare("SELECT id, name, price, image, category FROM products WHERE LOWER(category)=LOWER(?) ORDER BY id DESC")) {
            $stmt->bind_param('s', $category);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) $items[] = $row;
            }
            $stmt->close();
        }
    } else {
        if ($res = $conn->query("SELECT id, name, price, image, category FROM products ORDER BY id DESC")) {
            while ($row = $res->fetch_assoc()) $items[] = $row;
            $res->close();
        }
    }

    // Strategy B: join categories
    if (empty($items)) {
        $sql = $category
            ? "SELECT p.id, p.name, p.price, p.image, c.name AS category FROM products p JOIN categories c ON c.id=p.category_id WHERE LOWER(c.name)=LOWER(?) ORDER BY p.id DESC"
            : "SELECT p.id, p.name, p.price, p.image, c.name AS category FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC";
        if ($category) {
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('s', $category);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) $items[] = $row;
                }
                $stmt->close();
            }
        } else {
            if ($res = $conn->query($sql)) {
                while ($row = $res->fetch_assoc()) $items[] = $row;
                $res->close();
            }
        }
    }

    return $items;
}

function localImagePath($name, $image, $category) {
    if ($image && strpos($image, '/') !== false) return $image; // already a relative path
    $fallback = '';
    $cat = strtolower((string)$category);
    if ($cat === 'milk') {
        $fallback = 'Milk/' . ($image ?: 'Milk.avif');
    } elseif ($cat === 'cream') {
        $fallback = 'Milk/' . ($image ?: 'Homemade-cream-cheese-Thumbnail-scaled.jpg');
    } elseif ($cat === 'sweet' || $cat === 'sweets') {
        $fallback = 'Sweet/' . ($image ?: 'vijay-dairy-milk-cake-sweets-500-g-product-images-orvtgqphlvr-p602462321-3-202306220942.webp');
    } else {
        $lower = strtolower((string)$name);
        if (strpos($lower, 'milk') !== false || strpos($lower, 'butter') !== false || strpos($lower, 'paneer') !== false) {
            $fallback = 'Milk/' . ($image ?: 'Milk.avif');
        } else if (strpos($lower, 'cream') !== false || strpos($lower, 'yogurt') !== false) {
            $fallback = 'Milk/' . ($image ?: 'Homemade-cream-cheese-Thumbnail-scaled.jpg');
        } else {
            $fallback = 'Sweet/' . ($image ?: 'vijay-dairy-milk-cake-sweets-500-g-product-images-orvtgqphlvr-p602462321-3-202306220942.webp');
        }
    }
    return $fallback;
}

$category = isset($_GET['category']) ? trim($_GET['category']) : null; // Sweet, Milk, Cream
$products = fetchAllProducts($conn, $category);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Dairy-X</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:16px 24px;display:flex;align-items:center;justify-content:space-between}
        .header a{color:#fff;text-decoration:none;background:rgba(255,255,255,.2);padding:8px 12px;border-radius:6px}
        .filter{max-width:1200px;margin:20px auto;padding:0 16px;display:flex;gap:8px;align-items:center}
        .pro-container{max-width:1200px;margin:0 auto 40px auto}
        .pro{border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08);overflow:hidden}
        .pro img{width:100%;height:220px;object-fit:cover}
        .pr h4{color:#0a8f3c}
    </style>
</head>
<body>
    <div class="header">
        <div>
            <strong>Products</strong>
        </div>
        <div>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="admin.php"><i class="fas fa-shield-alt"></i> Admin</a>
        </div>
    </div>

    <div class="filter">
        <form method="get" action="product.php">
            <label for="category">Category:</label>
            <select id="category" name="category" onchange="this.form.submit()">
                <option value="" <?php echo $category? '' : 'selected'; ?>>All</option>
                <option value="Sweet" <?php echo ($category==='Sweet'?'selected':''); ?>>Sweet</option>
                <option value="Milk" <?php echo ($category==='Milk'?'selected':''); ?>>Milk</option>
                <option value="Cream" <?php echo ($category==='Cream'?'selected':''); ?>>Cream</option>
            </select>
            <noscript><button type="submit">Apply</button></noscript>
        </form>
    </div>

    <section class="section-s1">
        <div class="pro-container">
            <?php if (!empty($products)) { foreach ($products as $p) { 
                $name = $p['name'] ?? 'Product';
                $price = isset($p['price']) && is_numeric($p['price']) ? (float)$p['price'] : 0.0;
                $cat = $p['category'] ?? '';
                $img = localImagePath($name, $p['image'] ?? '', $cat);
            ?>
            <div class="pro">
                <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="pr">
                    <span>Dairy-X</span>
                    <h5><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <h4>₹<?php echo number_format($price, 2); ?></h4>
                    <button class="buy-btn" onclick="addToCart('<?php echo addslashes($name); ?>', <?php echo $price; ?>)">🛒 Add to Cart</button>
                    <button class="buy-btn" onclick="quickBuy('<?php echo addslashes($name); ?>', <?php echo $price; ?>)">🛍️ Buy Now</button>
                </div>
            </div>
            <?php } } else { ?>
                <p style="text-align:center;color:#666;">No products found. Adjust your category filter or add products in the database.</p>
            <?php } ?>
        </div>
    </section>

    <script>
        function addToCart(productName, price){
            const item={name:productName,price:Number(price),quantity:1};
            let cart=JSON.parse(localStorage.getItem('dairyCart'))||[];
            const ex=cart.find(i=>i.name===item.name); if(ex) ex.quantity++; else cart.push(item);
            localStorage.setItem('dairyCart',JSON.stringify(cart));
            alert(productName+' added to cart!');
        }
        function quickBuy(productName, price){
            const isLoggedIn=localStorage.getItem('isLoggedIn');
            if(!isLoggedIn){ alert('Please login to buy products'); window.location.href='login.html'; return; }
            const item={name:productName,price:Number(price),quantity:1};
            localStorage.setItem('dairyCart', JSON.stringify([item]));
            window.location.href='checkout.html';
        }
    </script>
</body>
</html>
