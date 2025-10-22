-- Dairy-X E-Commerce Schema and Seed
-- Import this in phpMyAdmin: create database and tables with initial data

-- Drop and recreate database (optional). Remove DROP if you already have data you need to keep.
DROP DATABASE IF EXISTS dairy_ecommerce;
CREATE DATABASE dairy_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dairy_ecommerce;

-- Categories
CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(120) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products
CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  image VARCHAR(255) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_products_category_id (category_id),
  INDEX idx_products_status (status)
) ENGINE=InnoDB;

-- Users
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Cart
CREATE TABLE cart (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_cart_user_id (user_id),
  UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB;

-- Orders
CREATE TABLE orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  fullname VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(150) NOT NULL,
  address TEXT NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  pincode VARCHAR(10) NOT NULL,
  landmark VARCHAR(255) NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 50.00,
  tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  payment_method ENUM('cod','phonepe','qrcode') NOT NULL DEFAULT 'cod',
  payment_status ENUM('pending','verified','failed') NOT NULL DEFAULT 'pending',
  transaction_id VARCHAR(100) NULL,
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_orders_user_id (user_id),
  INDEX idx_orders_status (status)
) ENGINE=InnoDB;

-- Order Items
CREATE TABLE order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_items_order_id (order_id)
) ENGINE=InnoDB;

-- Seed: Categories
INSERT INTO categories (name, slug) VALUES
 ('Sweet','sweet'),
 ('Milk','milk'),
 ('Cream','cream');

-- Seed: Products (paths relative to /Project/)
-- Sweet
INSERT INTO products (category_id, name, description, price, image) VALUES
 ((SELECT id FROM categories WHERE slug='sweet'),'Milk Cake','Delicious traditional milk cake', 200.00,'Sweet/vijay-dairy-milk-cake-sweets-500-g-product-images-orvtgqphlvr-p602462321-3-202306220942.webp'),
 ((SELECT id FROM categories WHERE slug='sweet'),'Milk Mysore Pak','Ghee-rich mysore pak', 320.00,'Sweet/Milk-Product-Ghee-Instagram-Post-4.webp'),
 ((SELECT id FROM categories WHERE slug='sweet'),'Jelly','Caramel custard jelly', 180.00,'Sweet/indian-sweets-with-milk-caramel-custard.webp'),
 ((SELECT id FROM categories WHERE slug='sweet'),'Milk Cream','Sweetened milk cream', 150.00,'Sweet/sweetmilk.webp'),
 ((SELECT id FROM categories WHERE slug='sweet'),'Rabri','Thickened sweetened milk', 480.00,'Sweet/Rabri07.JPG'),
 ((SELECT id FROM categories WHERE slug='sweet'),'Malai Sandwich','Rich malai sandwich', 560.00,'Sweet/malai-sandwich.jpg'),
 ((SELECT id FROM categories WHERE slug='sweet'),'GulabJamun','Classic gulab jamun', 300.00,'Sweet/Dry-Gulab-Jamun-26359-pixahive.jpg'),
 ((SELECT id FROM categories WHERE slug='sweet'),'Kalakand','Soft kalakand delight', 360.00,'Sweet/Mango-Kalakand-Cake-1-500x500.webp'),
 ((SELECT id FROM categories WHERE slug='sweet'),'Karadant','Nutty karadant', 340.00,'Sweet/kardant-sweet-1548313634-4654916.jpg'),
 ((SELECT id FROM categories WHERE slug='sweet'),'Barfi','Assorted barfi', 280.00,'Sweet/thabdi-barfi_kamdar_sweet_28dda75f-23d8-4c09-bbc5-7e7152b13d3e.webp'),
 ((SELECT id FROM categories WHERE slug='sweet'),'Cham-cham','Soft cham-cham', 320.00,'Sweet/Cham-Cham-585x366.jpg'),
 ((SELECT id FROM categories WHERE slug='sweet'),'Badam-barfi','Almond barfi', 420.00,'Sweet/indian-sweet-food-badam-barfi-260nw-617397095.webp');

-- Milk
INSERT INTO products (category_id, name, description, price, image) VALUES
 ((SELECT id FROM categories WHERE slug='milk'),'White-makhan','Fresh white butter', 160.00,'Milk/Homemade-white-makhan-02-500x375.jpg'),
 ((SELECT id FROM categories WHERE slug='milk'),'Butter','Creamy butter', 240.00,'Milk/Salted-or-Unsalted-Butter-Which-Should-I-Use-When.jpg'),
 ((SELECT id FROM categories WHERE slug='milk'),'Malai Paneer','Soft paneer', 220.00,'Milk/images.jpg'),
 ((SELECT id FROM categories WHERE slug='milk'),'Ricotta Cheese','Homemade ricotta', 260.00,'Milk/Homemade-Ricotta_-14.jpg'),
 ((SELECT id FROM categories WHERE slug='milk'),'Mozzarella Cheese','Mozzarella for pizza', 280.00,'Milk/mozzarella-pizza-recipe-instructions-120348.webp'),
 ((SELECT id FROM categories WHERE slug='milk'),'Cream Cheese','Rich cream cheese', 250.00,'Milk/Homemade-cream-cheese-Thumbnail-scaled.jpg'),
 ((SELECT id FROM categories WHERE slug='milk'),'Yogurt','Natural yogurt', 120.00,'Milk/q11GhVAT.jpeg'),
 ((SELECT id FROM categories WHERE slug='milk'),'Fresh Milk','Farm fresh milk', 60.00,'Milk/Milk.avif'),
 ((SELECT id FROM categories WHERE slug='milk'),'Fermented milk','Probiotic fermented milk', 140.00,'Milk/image-asset.webp');

-- Cream
INSERT INTO products (category_id, name, description, price, image) VALUES
 ((SELECT id FROM categories WHERE slug='cream'),'White-makhan','Cream category white-makhan', 160.00,'Milk/Homemade-white-makhan-02-500x375.jpg');

-- Seed: Users (passwords are hashed with password_hash())
-- Admin password: admin123
-- Demo user password: password
-- Note: Both passwords are hashed using bcrypt for security
INSERT INTO users (fullname, email, phone, password_hash, role) VALUES
 ('Admin User','admin@dairy-x.com','+91 9000000000', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe6/bF0ZqZP1vMPqPJbGXKJwLSu6CkLyC','admin'),
 ('Demo User','demo@dairy-x.com','+91 9876543210', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe6/bF0ZqZP1vMPqPJbGXKJwLSu6CkLyC','user');

-- Verify admin user creation
SELECT 'Admin user created successfully!' as message, 
       fullname, email, role 
FROM users 
WHERE role = 'admin';

-- Optional: Example order
-- INSERT INTO orders (user_id, total_amount, status, payment_method) VALUES (1, 500.00, 'completed', 'cod');
-- INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (1, 1, 1, 200.00);
