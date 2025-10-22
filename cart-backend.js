// Dairy-X E-Commerce - Cart Backend Integration
// This script replaces localStorage cart with backend API calls

const API_BASE = 'api';

// Cart functionality
let cart = [];
let isLoggedIn = false;
let currentUser = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', async function() {
    await checkAuthentication();
    if (isLoggedIn) {
        await loadCartFromBackend();
    }
    updateCartCount();
    updateAuthButton();
});

/**
 * Check if user is authenticated
 */
async function checkAuthentication() {
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=check`);
        const data = await response.json();
        
        if (data.success && data.authenticated) {
            isLoggedIn = true;
            currentUser = data.user;
        } else {
            isLoggedIn = false;
            currentUser = null;
        }
    } catch (error) {
        console.error('Auth check failed:', error);
        isLoggedIn = false;
    }
}

/**
 * Load cart from backend
 */
async function loadCartFromBackend() {
    try {
        const response = await fetch(`${API_BASE}/cart.php?action=get`);
        const data = await response.json();
        
        if (data.success) {
            cart = data.items;
            updateCartCount();
        }
    } catch (error) {
        console.error('Failed to load cart:', error);
    }
}

/**
 * Add product to cart
 */
async function addToCart(productName, price, productId = null) {
    if (!isLoggedIn) {
        alert('Please login to add items to cart');
        window.location.href = 'login.html';
        return;
    }
    
    // If productId not provided, try to get it from product name
    if (!productId) {
        productId = await getProductIdByName(productName);
        if (!productId) {
            alert('Product not found in database');
            return;
        }
    }
    
    try {
        const response = await fetch(`${API_BASE}/cart.php?action=add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`${productName} added to cart!`);
            await loadCartFromBackend();
        } else {
            alert('Failed to add to cart: ' + data.message);
        }
    } catch (error) {
        console.error('Add to cart failed:', error);
        alert('Failed to add to cart');
    }
}

/**
 * Update cart item quantity
 */
async function updateCartQuantity(productId, quantity) {
    if (!isLoggedIn) return;
    
    try {
        const response = await fetch(`${API_BASE}/cart.php?action=update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadCartFromBackend();
        } else {
            alert('Failed to update cart: ' + data.message);
        }
    } catch (error) {
        console.error('Update cart failed:', error);
    }
}

/**
 * Remove item from cart
 */
async function removeFromCart(productId) {
    if (!isLoggedIn) return;
    
    try {
        const response = await fetch(`${API_BASE}/cart.php?action=delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadCartFromBackend();
        } else {
            alert('Failed to remove item: ' + data.message);
        }
    } catch (error) {
        console.error('Remove from cart failed:', error);
    }
}

/**
 * Clear entire cart
 */
async function clearCart() {
    if (!isLoggedIn) return;
    
    try {
        const response = await fetch(`${API_BASE}/cart.php?action=clear`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            cart = [];
            updateCartCount();
        }
    } catch (error) {
        console.error('Clear cart failed:', error);
    }
}

/**
 * Update cart count display
 */
function updateCartCount() {
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        const count = cart.reduce((total, item) => total + item.quantity, 0);
        cartCountElement.textContent = count;
    }
}

/**
 * Get product ID by name (helper function)
 */
async function getProductIdByName(productName) {
    // This would ideally be handled on the backend
    // For now, we'll need to pass product IDs from the frontend
    // You should modify index.php to include product IDs in the onclick handlers
    return null;
}

/**
 * Quick Buy function
 */
async function quickBuy(productName, price, productId = null) {
    if (!isLoggedIn) {
        alert('Please login to buy products');
        window.location.href = 'login.html';
        return;
    }
    
    // Redirect to checkout with product info
    const item = {
        name: productName,
        price: price,
        quantity: 1,
        product_id: productId
    };
    
    sessionStorage.setItem('quickBuyItem', JSON.stringify(item));
    window.location.href = 'checkout.html';
}

/**
 * Update auth button display
 */
function updateAuthButton() {
    const authLink = document.getElementById('authLink');
    
    if (authLink && isLoggedIn && currentUser) {
        if (currentUser.role === 'admin') {
            authLink.textContent = 'ADMIN PANEL';
            authLink.href = 'admin-dashboard.html';
        } else {
            const displayName = currentUser.fullname || currentUser.email.split('@')[0];
            authLink.innerHTML = `<i class="fas fa-user"></i> ${displayName.toUpperCase()}`;
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

/**
 * Logout function
 */
async function logout() {
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=logout`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            isLoggedIn = false;
            currentUser = null;
            cart = [];
            alert('Logged out successfully!');
            window.location.reload();
        }
    } catch (error) {
        console.error('Logout failed:', error);
        alert('Logout failed');
    }
}
