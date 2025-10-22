<?php
require_once __DIR__ . '/config.php';

function fetchProducts($conn, $categoryName) {
    $products = [];
    // Join products -> categories; schema defined in database.sql
    $sql = "SELECT p.id, p.name, p.price, p.image, c.name AS category
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE LOWER(c.name) = LOWER(?)
            AND p.status = 'active'
            ORDER BY p.id DESC
            LIMIT 50";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('s', $categoryName);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $products[] = $row;
            }
        }
        $stmt->close();
    }
    return $products;
}

function renderProductCard($p) {
    $name = isset($p['name']) ? $p['name'] : 'Product';
    $price = isset($p['price']) && is_numeric($p['price']) ? (float)$p['price'] : 0.0;
    $image = isset($p['image']) && $p['image'] !== '' ? $p['image'] : '';
    $productId = isset($p['id']) ? (int)$p['id'] : 0;

    // If DB stores only filenames, try to detect folder by keywords
    // Prefer DB-provided relative image path (seeded under Project/*). If absent, infer.
    $imgSrc = $image;
    
    // Check if image file actually exists
    if ($imgSrc && file_exists(__DIR__ . '/' . $imgSrc)) {
        // Image exists, use it
        $imgSrc = $imgSrc;
    } elseif ($imgSrc === '' || strpos($imgSrc, '/') === false) {
        // Best effort guess: if name hints at category
        $lower = strtolower($name);
        if (strpos($lower, 'milk') !== false || strpos($lower, 'butter') !== false || strpos($lower, 'paneer') !== false) {
            $imgSrc = 'Milk/' . ($image !== '' ? $image : 'Milk.avif');
        } elseif (strpos($lower, 'cream') !== false || strpos($lower, 'yogurt') !== false) {
            $imgSrc = 'Milk/' . ($image !== '' ? $image : 'Homemade-cream-cheese-Thumbnail-scaled.jpg');
        } else {
            $imgSrc = 'Sweet/' . ($image !== '' ? $image : '1.webp');
        }
    }
    
    // Final check - if image still doesn't exist, use absolute placeholder
    if (!file_exists(__DIR__ . '/' . $imgSrc)) {
        // Try to find any image in the category folders
        $categoryFolders = ['Sweet', 'Milk', 'Cream'];
        foreach ($categoryFolders as $folder) {
            $folderPath = __DIR__ . '/' . $folder;
            if (is_dir($folderPath)) {
                $files = scandir($folderPath);
                foreach ($files as $file) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) {
                        $imgSrc = $folder . '/' . $file;
                        break 2;
                    }
                }
            }
        }
    }

    // Escape for HTML output
    $escName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $escImg = htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8');

    echo "\n    <div class=\"pro\">\n      <img src=\"{$escImg}\" alt=\"{$escName}\" onerror=\"this.src='image/images.jfif'; this.onerror=null;\">\n      <div class=\"pr\">\n        <span>Dairy-X</span>\n        <h5>{$escName}</h5>\n        <div class=\"star\">\n          <i class=\"fas fa-star\"></i>\n          <i class=\"fas fa-star\"></i>\n          <i class=\"fas fa-star\"></i>\n          <i class=\"fas fa-star\"></i>\n          <i class=\"fas fa-star\"></i>\n        </div>\n        <h4>₹" . number_format($price, 2) . "</h4>\n        <button class=\"buy-btn\" onclick=\"addToCart('" . addslashes($name) . "', " . $price . ", " . $productId . ")\">🛒 Add to Cart</button>\n        <button class=\"buy-btn\" onclick=\"quickBuy('" . addslashes($name) . "', " . $price . ", " . $productId . ")\">🛍️ Buy Now</button>\n      </div>\n    </div>\n    ";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>milk product</title>
    <link rel="shortcut icon" href="image/images (2).jfif" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta name="description" content="this the page about milk product">
    <style type="text/css">
        h1 { text-align: center; box-sizing: border-box; -webkit-text-fill-color: rgb(001); }
        nav{ width:100%; height: 100px; color:black; display: flex; justify-content: space-around; align-items: center; }
        a:active{ background-color: rgb(184, 23, 225); }
        .dropbtn { background-color: #04AA6D; color: white; padding: 16px; font-size: 16px; border: none; }
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content { display: none; position: absolute; background-color: #f1f1f1; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; }
        .dropdown-content a { color: black; padding: 12px 16px; text-decoration: none; display: block; }
        .dropdown-content a:hover { background-color: #ddd; }
        .dropdown:hover .dropdown-content { display: block; }
        .dropdown:hover .dropbtn { background-color: #3e8e41; }
    </style>
</head>
<body>
    <script src="cart-backend.js"></script>
    <script>
        // Keep backward compatibility with inline handlers
        // These will be overridden by cart-backend.js functions
    </script>

    <h1>Dairy Product</h1>
    <div class="gallery">
        <img src="image/Merieux-NutriSciences-Dairy-Testing-and-services.jpg" alt="image">
        <img src="image/cheese-pepper-cheese-dairy-products-wallpaper-preview.jpg" alt="image">
        <img src="image/D1062_19_253_1200.jpg" alt="image">
        <img src="image/images.jfif" alt="image">
        <img src="image/IMG_6236.jpg" alt="image">
        <img src="image/360_F_517881863_jl9BYN7DN7qsEQHWSuXYoRkJFxUlzdVM.jpg" alt="image">
        <img src="image/istockphoto-2178111666-612x612.jpg" alt="image">
        <img src="image/milk-1489734923-2762748.jpeg" alt="image">
        <img src="image/Stick-butter.webp" alt="image">
        <img src="image/HD-wallpaper-dairy-products-milk-products-dairy-products-cheese.jpg" alt="image">
        <img src="image/istockphoto-910881428-612x612.jpg" alt="image">
        <img src="image/shutterstock_1232966839.webp" alt="image">
        <img src="image/download.jfif" alt="image">
        <img src="image/dairy-759.webp" alt="image">
        <img src="image/images (1).jfif" alt="image">
        <img src="image/GulabJamunWithMilkPowder-02.jpg" alt="image">
        <img src="image/shutterstock_1232966839-1024x742.avif" alt="image">
    </div>

    <header>
        <nav>
            <div class="logo">Dairy-X</div>
            <div class="product">
                <a href="#home">🏚 Home</a>
                <div class="dropdown">
                    <button class="dropbtn">🛍 Product</button>
                    <div class="dropdown-content">
                        <a href="#Sweet">SWEET</a>
                        <a href="#Milk">MILK PRODUCT</a>
                        <a href="#Cream">Cream</a>
                    </div>
                </div>
                <a href="#contact">☎ Contact</a>
                <a href="cart.html">🛒Cart (<span id=\"cartCount\">0</span>)</a>
                <a href="buynow.html">🛍️ Buy now</a>
            </div>
            <div class="register" id="authButton">
                <a href="login.html" id="authLink">LOG IN</a>
            </div>
        </nav>
    </header>

    <section id="Sweet" class="section-s1">
        <h2>Sweet Products</h2>
        <p>Sweet products are available</p>
        <div class="pro-container">
            <?php
            $sweet = fetchProducts($conn, 'Sweet');
            if (!empty($sweet)) {
                foreach ($sweet as $p) { renderProductCard($p); }
            } else {
                // Fallback to a few static items if DB empty / schema mismatch
                $fallback = [
                    ['name' => 'Milk Cake', 'price' => 2, 'image' => 'Sweet/vijay-dairy-dummy.webp'],
                    ['name' => 'Milk Mysore Pak', 'price' => 4, 'image' => 'Sweet/Milk-Product-Ghee-Instagram-Post-4.webp'],
                    ['name' => 'Jelly', 'price' => 5, 'image' => 'Sweet/indian-sweets-with-milk-caramel-custard.webp'],
                ];
                foreach ($fallback as $p) { renderProductCard($p); }
            }
            ?>
        </div>
    </section>

    <section id="Milk" class="section-s1">
        <h2>Milk-products</h2>
        <p>Milk products are available</p>
        <div class="pro-container">
            <?php
            $milk = fetchProducts($conn, 'Milk');
            if (!empty($milk)) {
                foreach ($milk as $p) { renderProductCard($p); }
            } else {
                $fallback = [
                    ['name' => 'White-makhan', 'price' => 2, 'image' => 'Milk/Homemade-white-makhan-02-500x375.jpg'],
                    ['name' => 'Butter', 'price' => 4, 'image' => 'Milk/Salted-or-Unsalted-Butter-Which-Should-I-Use-When.jpg'],
                    ['name' => 'Malai Paneer', 'price' => 4, 'image' => 'Milk/images.jpg'],
                ];
                foreach ($fallback as $p) { renderProductCard($p); }
            }
            ?>
        </div>
    </section>

    <section id="Cream" class="section-s1">
        <h2>Cream-products</h2>
        <p>Cream products are available</p>
        <div class="pro-container">
            <?php
            $cream = fetchProducts($conn, 'Cream');
            if (!empty($cream)) {
                foreach ($cream as $p) { renderProductCard($p); }
            } else {
                $fallback = [
                    ['name' => 'White-makhan', 'price' => 2, 'image' => 'Milk/Homemade-white-makhan-02-500x375.jpg'],
                ];
                foreach ($fallback as $p) { renderProductCard($p); }
            }
            ?>
        </div>
    </section>

    <section id="contact" class="section-s1">
        <h2>📞 Contact Us</h2>
        <p>Get in touch with Dairy-X for fresh dairy products delivery</p>
        <div class="contact-container">
            <div class="contact-info">
                <div class="info-item">
                    <h3>📍 Address</h3>
                    <p>Jawaharlal Nehru Engineering College<br>MGM University, Aurangabad<br>Maharashtra, India</p>
                </div>
                <div class="info-item">
                    <h3>📧 Email</h3>
                    <p>info@dairy-x.com<br>support@dairy-x.com</p>
                </div>
                <div class="info-item">
                    <h3>📱 Phone</h3>
                    <p>+91 1234567890<br>+91 0987654321</p>
                </div>
                <div class="info-item">
                    <h3>🕒 Working Hours</h3>
                    <p>Monday - Saturday: 8:00 AM - 8:00 PM<br>Sunday: 9:00 AM - 6:00 PM</p>
                </div>
            </div>
            <div class="contact-form">
                <h3>Send us a message</h3>
                <form id="contactForm" onsubmit="event.preventDefault(); alert('Thank you for contacting us! We will get back to you soon.'); this.reset();">
                    <input type="text" placeholder="Your Name" required>
                    <input type="email" placeholder="Your Email" required>
                    <input type="tel" placeholder="Your Phone">
                    <textarea placeholder="Your Message" rows="5" required></textarea>
                    <button type="submit" class="buy-btn">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Dairy-X</h3>
                <p>Your trusted source for fresh, quality dairy products delivered to your doorstep.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#Sweet">Sweet Products</a></li>
                    <li><a href="#Milk">Milk Products</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#">Facebook</a>
                    <a href="#">Instagram</a>
                    <a href="#">Twitter</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Dairy-X | Developed by Krishna Vinayak Solanke | FYMCA Project</p>
        </div>
    </footer>
</body>
</html>
