// Initialize cart from localStorage
let cart = [];

// Load cart for current user session
function loadUserCart() {
    const currentUser = localStorage.getItem('currentUser');
    if (currentUser) {
        // Load user-specific cart
        const userCartKey = 'cart_' + currentUser;
        cart = JSON.parse(localStorage.getItem(userCartKey)) || [];
    } else {
        cart = [];
    }
    updateCartBadge();
}

function updateCartBadge() {
    const badge = document.getElementById('cart-count');
    if (badge) {
        badge.innerText = cart.length;
    }
}

function requireAuth() {
    if (!localStorage.getItem('currentUser')) {
        alert('Please login to use this feature.');
        window.location.href = 'login.html';
        return false;
    }
    return true;
}

function addToCart(product) {
    if (!requireAuth()) return;
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({ ...product, quantity: 1 });
    }
    saveCart();
    updateCartBadge();
    showToast(`${product.title} added to cart!`);
}

function buyNow(product) {
    if (!requireAuth()) return;

    // Add to cart if not already there
    const existing = cart.find(item => item.id === product.id);
    if (!existing) {
        cart.push({ ...product, quantity: 1 });
        saveCart();
        updateCartBadge();
    }

    window.location.href = 'checkout.html';
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    saveCart();
    renderCart(); // Call if on cart page
    updateCartBadge();
}

function updateQuantity(productId, delta) {
    const item = cart.find(i => i.id === productId);
    if (item) {
        item.quantity += delta;
        if (item.quantity <= 0) {
            removeFromCart(productId);
        } else {
            saveCart();
            renderCart();
        }
    }
}

function saveCart() {
    const currentUser = localStorage.getItem('currentUser');
    if (currentUser) {
        // Save to user-specific cart key
        const userCartKey = 'cart_' + currentUser;
        localStorage.setItem(userCartKey, JSON.stringify(cart));
    }
}

function showToast(message) {
    // Simple alert-based toast for now, or use a Bootstrap toast if preferred
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        const div = document.createElement('div');
        div.id = 'toast-container';
        div.style.position = 'fixed';
        div.style.bottom = '20px';
        div.style.right = '20px';
        div.style.zIndex = '9999';
        document.body.appendChild(div);
    }

    const toast = document.createElement('div');
    toast.className = 'alert alert-success alert-dismissible fade show shadow';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.getElementById('toast-container').appendChild(toast);
    setTimeout(() => {
        const bsToast = new bootstrap.Alert(toast);
        bsToast.close();
    }, 3000);
}

// Authentication Logic
function checkAuth() {
    const user = localStorage.getItem('currentUser');
    const authNav = document.querySelector('.navbar-nav');
    const currentPage = window.location.pathname.split('/').pop();

    const protectedPages = ['dashboard.html', 'admin.html', 'post-item.html', 'checkout.html'];

    // Protect sensitive pages
    if (!user && protectedPages.includes(currentPage)) {
        window.location.href = 'login.html';
        return;
    }

    // Redirect away from login if already logged in
    if (user && currentPage === 'login.html') {
        const isAdmin = user === 'admin@university.edu' || user === 'admin@uni.edu';
        window.location.href = isAdmin ? 'admin.html' : 'dashboard.html';
        return;
    }

    if (user && authNav) {
        // Find the login button/link
        const loginBtn = authNav.querySelector('a[href="login.html"]');
        if (loginBtn) {
            const isAdmin = user === 'admin@university.edu' || user === 'admin@uni.edu';
            const dashboardUrl = isAdmin ? 'admin.html' : 'dashboard.html';
            const dashboardText = isAdmin ? 'Admin Panel' : 'Dashboard';

            // Replace Login with Dashboard/Admin
            loginBtn.href = dashboardUrl;
            loginBtn.innerText = dashboardText;
            loginBtn.classList.remove('btn-outline-primary');
            loginBtn.classList.add('fw-bold');

            // Add Logout Button
            const logoutLi = document.createElement('li');
            logoutLi.className = 'nav-item ms-lg-3';
            logoutLi.innerHTML = `<a href="#" class="btn btn-outline-danger btn-sm px-4" onclick="logout(event)">Logout</a>`;

            // Insert before cart if exists, else append
            const cartLi = authNav.querySelector('a[href="cart.html"]')?.parentElement;
            if (cartLi) {
                authNav.insertBefore(logoutLi, cartLi);
            } else {
                authNav.appendChild(logoutLi);
            }
        }
    }
}

function logout(e) {
    if (e) e.preventDefault();
    const currentUser = localStorage.getItem('currentUser');
    if (currentUser) {
        // Clear user's cart on logout
        const userCartKey = 'cart_' + currentUser;
        localStorage.removeItem(userCartKey);
    }
    localStorage.removeItem('currentUser');
    cart = []; // Clear in-memory cart
    updateCartBadge(); // Update badge to 0
    window.location.href = 'index.html';
}

// Wishlist Logic
let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];

function toggleWishlist(productId) {
    if (!requireAuth()) return;
    const index = wishlist.indexOf(productId);
    if (index > -1) {
        wishlist.splice(index, 1);
        showToast('Item removed from wishlist');
    } else {
        wishlist.push(productId);
        showToast('Item added to wishlist!');
    }
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    updateWishlistIcons();
}

function updateWishlistIcons() {
    document.querySelectorAll('[data-product-id]').forEach(btn => {
        const productId = parseInt(btn.getAttribute('data-product-id'));
        const icon = btn.querySelector('i');
        if (wishlist.includes(productId)) {
            icon.classList.remove('far');
            icon.classList.add('fas', 'text-danger');
        } else {
            icon.classList.remove('fas', 'text-danger');
            icon.classList.add('far');
        }
    });
}

function openChat(sellerName) {
    if (!requireAuth()) return;
    const modalHtml = `
        <div class="modal fade" id="chatModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-bold"><i class="fas fa-comment-dots me-2"></i>Chat with ${sellerName}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="display-4 text-primary mb-3"><i class="fas fa-user-circle"></i></div>
                        <h6>Connecting to ${sellerName}...</h6>
                        <p class="text-muted small">The student seller will be notified of your interest. You can view your active chats in the Dashboard.</p>
                        <textarea class="form-control mb-3" rows="3" placeholder="Type your message here..."></textarea>
                        <button class="btn btn-primary w-100" data-bs-dismiss="modal" onclick="showToast('Message sent to ${sellerName}!')">Send Message</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing if any
    const existing = document.getElementById('chatModal');
    if (existing) existing.remove();

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('chatModal'));
    modal.show();
}

function openReport(itemName) {
    const modalHtml = `
        <div class="modal fade" id="reportModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-flag me-2"></i>Report Item</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-3">Why are you reporting <strong>${itemName}</strong>?</p>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Reason for Report</label>
                            <select class="form-select">
                                <option>Misleading description</option>
                                <option>Inappropriate content</option>
                                <option>Duplicate listing</option>
                                <option>Counterfeit product</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Additional Details</label>
                            <textarea class="form-control" rows="3" placeholder="Explain the issue..."></textarea>
                        </div>
                        <button class="btn btn-danger w-100" data-bs-dismiss="modal" onclick="showToast('Thank you. Our admins will review this report.')">Submit Report</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const existing = document.getElementById('reportModal');
    if (existing) existing.remove();

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    modal.show();
}

// Data Management for Admin CRUD
const initialUsers = [
    { id: '1', name: 'Student User', email: 'user@university.edu', role: 'Student', status: 'Active' },
    { id: '2', name: 'admin@university.edu', email: 'admin@university.edu', role: 'Admin', status: 'Active' }
];

const initialListings = [
    { id: '1', title: 'Arduino Uno Rev3', price: 750, seller: 'admin@university.edu', status: 'Active', category: 'Electronics', condition: 'Used (Good)', img: 'img/arduino.avif', description: 'Perfect for DIY electronics projects and robotics. Includes USB cable and basic documentation.' },
    { id: '2', title: 'Compound Microscope', price: 1200, seller: 'admin@university.edu', status: 'Active', category: 'Lab Material', condition: 'Fair', img: 'img/lab.avif', description: 'Ideal for biology and chemistry lab work. Features adjustable magnification and LED illumination.' }
];

const initialCategories = [
    { id: '1', name: 'Electronics', items: 2, icon: 'fas fa-microchip' },
    { id: '2', name: 'Lab Material', items: 1, icon: 'fas fa-flask' },
    { id: '3', name: 'Dorm Essentials', items: 0, icon: 'fas fa-bed' },
    { id: '4', name: 'Notes', items: 0, icon: 'fas fa-book' }
];

// Initialize Data if not exists
if (!localStorage.getItem('users')) {
    localStorage.setItem('users', JSON.stringify(initialUsers));
} else {
    // Sync users if email changed or status mismatch
    let users = JSON.parse(localStorage.getItem('users'));
    initialUsers.forEach(initUser => {
        const index = users.findIndex(u => String(u.id) === String(initUser.id));
        if (index !== -1) {
            users[index].email = initUser.email;
            users[index].name = initUser.name;
        }
    });
    localStorage.setItem('users', JSON.stringify(users));
}

// Migrate currentUser if it was the old email
if (localStorage.getItem('currentUser') === 'jane@university.edu') {
    localStorage.setItem('currentUser', 'user@university.edu');
}
if (!localStorage.getItem('adminListings')) {
    localStorage.setItem('adminListings', JSON.stringify(initialListings));
} else {
    // Sync logic: Update specific fields OR remove items as requested
    let existingListings = JSON.parse(localStorage.getItem('adminListings'));

    // User requested to remove Thomas Calculus (ID: '3')
    existingListings = existingListings.filter(l => String(l.id) !== '3');

    let updated = true; // Set to true to ensure removal is saved

    initialListings.forEach(initItem => {
        const index = existingListings.findIndex(l => String(l.id) === String(initItem.id));
        if (index !== -1) {
            // Update specific fields if they differ
            if (existingListings[index].seller !== initItem.seller || existingListings[index].status !== initItem.status) {
                existingListings[index].seller = initItem.seller;
                existingListings[index].status = initItem.status;
                updated = true;
            }
        } else {
            // Add if missing (like Thomas Calculus)
            existingListings.push(initItem);
            updated = true;
        }
    });

    if (updated) localStorage.setItem('adminListings', JSON.stringify(existingListings));
}
if (!localStorage.getItem('categories')) {
    localStorage.setItem('categories', JSON.stringify(initialCategories));
} else {
    // Sync categories to ensure new ones are added
    let existingCategories = JSON.parse(localStorage.getItem('categories'));
    
    initialCategories.forEach(initCat => {
        const index = existingCategories.findIndex(c => String(c.id) === String(initCat.id));
        if (index !== -1) {
            // Update existing category
            existingCategories[index].name = initCat.name;
            existingCategories[index].icon = initCat.icon;
            existingCategories[index].items = initCat.items;
        } else {
            // Add new category if missing
            existingCategories.push(initCat);
        }
    });
    
    localStorage.setItem('categories', JSON.stringify(existingCategories));
}

// CRUD Helpers
function getFromStorage(key) {
    return JSON.parse(localStorage.getItem(key)) || [];
}

function saveToStorage(key, data) {
    localStorage.setItem(key, JSON.stringify(data));
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadUserCart(); // Load user's cart on page load
    checkAuth();
    updateWishlistIcons();
});
