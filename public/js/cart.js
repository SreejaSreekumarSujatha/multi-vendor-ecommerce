// Enhanced Cart functionality with better error handling
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cart.js loaded');
    
    const cartBtn = document.getElementById('cart-btn');
    const cartPopup = document.getElementById('cart-popup');
    
    if (cartBtn && cartPopup) {
        console.log('Cart elements found');
        
        let hoverTimeout;
        let isHovering = false;
        
        // Show popup on hover
        cartBtn.addEventListener('mouseenter', function() {
            console.log('Cart button hover');
            clearTimeout(hoverTimeout);
            isHovering = true;
            loadCartPopup();
            showPopup();
        });
        
        // Hide popup when mouse leaves
        cartBtn.addEventListener('mouseleave', function() {
            isHovering = false;
            hoverTimeout = setTimeout(() => {
                if (!isHovering) {
                    hidePopup();
                }
            }, 500);
        });
        
        // Keep popup open when hovering over it
        cartPopup.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
            isHovering = true;
        });
        
        cartPopup.addEventListener('mouseleave', function() {
            isHovering = false;
            hidePopup();
        });
        
        // Click to toggle popup on mobile
        cartBtn.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                togglePopup();
            }
        });
    } else {
        console.log('Cart elements not found:', {
            cartBtn: !!cartBtn,
            cartPopup: !!cartPopup
        });
    }
    
    function showPopup() {
        const cartPopup = document.getElementById('cart-popup');
        if (cartPopup) {
            cartPopup.classList.add('show');
        }
    }
    
    function hidePopup() {
        const cartPopup = document.getElementById('cart-popup');
        if (cartPopup) {
            cartPopup.classList.remove('show');
        }
    }
    
    function togglePopup() {
        const cartPopup = document.getElementById('cart-popup');
        if (cartPopup) {
            cartPopup.classList.toggle('show');
        }
    }
    
    // Load cart data for popup
    function loadCartPopup() {
        console.log('Loading cart popup data');
        
        const itemsContainer = document.getElementById('cart-popup-items');
        const totalElement = document.getElementById('cart-popup-total');
        
        if (!itemsContainer || !totalElement) {
            console.error('Cart popup containers not found');
            return;
        }
        
        // Show loading state
        itemsContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #6c757d;"><i>Loading...</i></div>';
        
        // Build the URL for the request
        const url = window.location.pathname + '?action=get-cart-data';
        console.log('Fetching cart data from:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text(); // Get as text first to debug
        })
        .then(text => {
            console.log('Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Parsed cart data:', data);
                updateCartPopup(data);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response');
            }
        })
        .catch(error => {
            console.error('Error loading cart data:', error);
            if (itemsContainer) {
                itemsContainer.innerHTML = `
                    <div style="padding: 20px; text-align: center; color: #e74c3c;">
                        <div>❌ Error loading cart</div>
                        <small style="display: block; margin-top: 5px;">Please refresh the page</small>
                    </div>
                `;
            }
        });
    }
    
    // Update cart popup content
    function updateCartPopup(cartData) {
        console.log('Updating cart popup with data:', cartData);
        
        const itemsContainer = document.getElementById('cart-popup-items');
        const totalElement = document.getElementById('cart-popup-total');
        
        if (!itemsContainer || !totalElement) {
            console.error('Cart popup elements not found');
            return;
        }
        
        // Handle error response
        if (cartData.error) {
            itemsContainer.innerHTML = `
                <div style="padding: 20px; text-align: center; color: #e74c3c;">
                    <div>❌ ${cartData.error}</div>
                    ${cartData.message ? `<small style="display: block; margin-top: 5px;">${cartData.message}</small>` : ''}
                </div>
            `;
            totalElement.innerHTML = '<div style="color: #e74c3c;">Error loading total</div>';
            updateCartCounterBadge(0);
            return;
        }
        
        // Update items display
        if (cartData.items && cartData.items.length > 0) {
            let itemsHtml = '';
            
            // Show up to 4 items
            const displayItems = cartData.items.slice(0, 4);
            
            displayItems.forEach(item => {
                const itemTotal = (parseFloat(item.quantity || 0) * parseFloat(item.price || 0)).toFixed(2);
                itemsHtml += `
                    <div class="cart-popup-item">
                        <div class="cart-item-image">
                            ${item.image_url ? 
                                `<img src="${item.image_url}" alt="${item.name || 'Product'}" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                 <div class="no-image-placeholder" style="display: none;">📦</div>` : 
                                '<div class="no-image-placeholder">📦</div>'
                            }
                        </div>
                        <div class="cart-popup-item-info">
                            <h5 title="${item.name || 'Unknown Product'}">${item.name || 'Unknown Product'}</h5>
                            <p class="item-details">
                                <span class="quantity">Qty: ${item.quantity || 0}</span>
                                <span class="price">$${parseFloat(item.price || 0).toFixed(2)}</span>
                            </p>
                            <p class="item-total">Total: $${itemTotal}</p>
                        </div>
                    </div>
                `;
            });
            
            // Show "more items" indicator if needed
            if (cartData.items.length > 4) {
                itemsHtml += `
                    <div class="cart-popup-item more-items">
                        <div style="text-align: center; color: #6c757d; font-style: italic; padding: 10px;">
                            <small>+ ${cartData.items.length - 4} more item${cartData.items.length - 4 !== 1 ? 's' : ''}</small>
                        </div>
                    </div>
                `;
            }
            
            itemsContainer.innerHTML = itemsHtml;
        } else {
            itemsContainer.innerHTML = `
                <div class="empty-cart-popup">
                    <div style="padding: 30px 20px; text-align: center; color: #6c757d;">
                        <div style="font-size: 48px; margin-bottom: 10px;">🛒</div>
                        <p>Your cart is empty</p>
                        <small>Add some products to get started!</small>
                    </div>
                </div>
            `;
        }
        
        // Update total
        const total = parseFloat(cartData.total || 0);
        totalElement.innerHTML = `
            <div style="font-size: 18px; font-weight: 700; color: #27ae60;">
                Total: $${total.toFixed(2)}
            </div>
        `;
        
        // Update cart counter badge
        updateCartCounterBadge(cartData.count || 0);
    }
    
    // Update cart counter badge
    function updateCartCounterBadge(count) {
        console.log('Updating cart counter badge:', count);
        
        const cartBtn = document.getElementById('cart-btn');
        let counter = document.querySelector('.cart-counter');
        
        if (count > 0) {
            if (counter) {
                counter.textContent = count;
                // Add pulse animation for updates
                counter.style.animation = 'none';
                setTimeout(() => {
                    counter.style.animation = 'pulse 2s infinite';
                }, 10);
            } else if (cartBtn) {
                // Create counter if it doesn't exist
                console.log('Creating new cart counter');
                const newCounter = document.createElement('span');
                newCounter.className = 'cart-counter';
                newCounter.textContent = count;
                cartBtn.appendChild(newCounter);
            }
        } else {
            if (counter) {
                console.log('Removing cart counter');
                counter.remove();
            }
        }
    }
    
    // Update counter on page load
    setTimeout(() => {
        console.log('Initial cart counter update');
        updateCartCounter();
    }, 1000);
    
    // Function to update just the counter (lighter than full popup)
    function updateCartCounter() {
        const url = window.location.pathname + '?action=get-cart-data';
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Counter update data:', data);
            updateCartCounterBadge(data.count || 0);
        })
        .catch(error => {
            console.error('Error updating cart counter:', error);
        });
    }
    
    // Expose functions globally for debugging
    window.cartDebug = {
        loadCartPopup,
        updateCartCounter,
        updateCartCounterBadge
    };
});