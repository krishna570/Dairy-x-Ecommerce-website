// Dairy Product E-Commerce Website - JavaScript

// Cart functionality
let cart = [];

// Add to cart function
function addToCart(productName, price) {
    const item = {
        name: productName,
        price: price,
        quantity: 1
    };
    
    // Check if item already exists in cart
    const existingItem = cart.find(item => item.name === productName);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push(item);
    }
    
    updateCartCount();
    alert(`${productName} added to cart!`);
    saveCart();
}

// Update cart count in navbar
function updateCartCount() {
    const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        cartCountElement.textContent = cartCount;
    }
}

// Save cart to localStorage
function saveCart() {
    localStorage.setItem('dairyCart', JSON.stringify(cart));
}

// Load cart from localStorage
function loadCart() {
    const savedCart = localStorage.getItem('dairyCart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCartCount();
    }
}

// Buy Now function - immediately redirects to checkout
function buyNow(productName, price) {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    
    if (!isLoggedIn) {
        alert('Please login to buy products');
        window.location.href = 'login.html';
        return;
    }
    
    // Create a temporary cart with just this item
    const buyNowItem = {
        name: productName,
        price: price,
        quantity: 1
    };
    
    // Save to cart (will be loaded in checkout)
    localStorage.setItem('dairyCart', JSON.stringify([buyNowItem]));
    
    // Redirect to checkout
    window.location.href = 'checkout.html';
}

// Initialize cart on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
    updateAuthButton();
    
    // Add click handlers to all buttons
    const allButtons = document.querySelectorAll('.buy-btn');
    allButtons.forEach(button => {
        if (button.textContent.includes('Add to Cart')) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productDiv = this.closest('.pro');
                const productName = productDiv.querySelector('h5').textContent;
                const priceText = productDiv.querySelector('h4').textContent;
                const price = parseFloat(priceText.replace('$', ''));
                addToCart(productName, price);
            });
        } else if (button.textContent.includes('Buy Now')) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productDiv = this.closest('.pro');
                const productName = productDiv.querySelector('h5').textContent;
                const priceText = productDiv.querySelector('h4').textContent;
                const price = parseFloat(priceText.replace('$', ''));
                buyNow(productName, price);
            });
        }
    });
    
    // Smooth scroll for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Update auth button based on login status
function updateAuthButton() {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const userType = localStorage.getItem('userType');
    const userName = localStorage.getItem('userName') || localStorage.getItem('userEmail');
    const authLink = document.getElementById('authLink');
    
    if (authLink && isLoggedIn === 'true') {
        if (userType === 'admin') {
            authLink.textContent = 'ADMIN PANEL';
            authLink.href = 'admin-dashboard.html';
        } else {
            authLink.innerHTML = `<i class="fas fa-user"></i> ${userName ? userName.split('@')[0].toUpperCase() : 'USER'}`;
            authLink.href = '#';
            authLink.onclick = function(e) {
                e.preventDefault();
                if (confirm('Do you want to logout?')) {
                    logout();
                }
            };
        }
    }
}

// Logout function
function logout() {
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('userType');
    localStorage.removeItem('userEmail');
    localStorage.removeItem('userName');
    localStorage.removeItem('adminUsername');
    alert('Logged out successfully!');
    window.location.reload();
}

// Search functionality
function searchProducts(query) {
    const products = document.querySelectorAll('.pro');
    query = query.toLowerCase();
    
    products.forEach(product => {
        const productName = product.querySelector('h5').textContent.toLowerCase();
        if (productName.includes(query)) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

// View cart function
function viewCart() {
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }
    
    let cartContent = 'Your Cart:\n\n';
    let total = 0;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        cartContent += `${item.name} x${item.quantity} - $${itemTotal.toFixed(2)}\n`;
        total += itemTotal;
    });
    
    cartContent += `\nTotal: $${total.toFixed(2)}`;
    alert(cartContent);
}
