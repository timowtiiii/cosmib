document.addEventListener('DOMContentLoaded', () => {
    const cartToggleIcon = document.getElementById('cart-toggle-icon');
    const floatingCart = document.getElementById('floating-cart');
    const closeCartBtn = document.getElementById('close-cart-btn');
    const cartItemsContainer = document.getElementById('cart-items');
    const cartCountSpan = document.getElementById('cart-count');
    const cartTotalSpan = document.getElementById('cart-total');

    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    // Function to save cart to localStorage
    const saveCart = () => {
        localStorage.setItem('cart', JSON.stringify(cart));
    };

    // Function to render cart items
    const renderCart = () => {
        cartItemsContainer.innerHTML = ''; // Clear existing items
        let total = 0;
        let itemCount = 0;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p style="text-align: center; padding: 20px; color: var(--text-muted);">Your cart is empty.</p>';
        } else {
            cart.forEach(item => {
                const cartItemDiv = document.createElement('div');
                cartItemDiv.classList.add('cart-item');
                cartItemDiv.dataset.productId = item.id;

                cartItemDiv.innerHTML = `
                    <img src="images/${item.image}" alt="${item.name}">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p>₱${(item.price * item.quantity).toFixed(2)}</p>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn decrease-quantity">-</button>
                        <input type="number" value="${item.quantity}" min="1" class="item-quantity-input">
                        <button class="quantity-btn increase-quantity">+</button>
                    </div>
                    <button class="remove-item-btn"><i class="fas fa-trash"></i></button>
                `;
                cartItemsContainer.appendChild(cartItemDiv);

                total += item.price * item.quantity;
                itemCount += item.quantity;
            });
        }

        cartTotalSpan.textContent = '₱' + total.toFixed(2);
        cartCountSpan.textContent = itemCount;
        saveCart(); // Save cart state after rendering
    };

    // Add to Cart functionality
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', (event) => {
            const productId = event.target.dataset.productId;
            const productName = event.target.dataset.productName;
            const productPrice = parseFloat(event.target.dataset.productPrice);
            const productImage = event.target.dataset.productImage;

            const existingItem = cart.find(item => item.id === productId);

            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    image: productImage,
                    quantity: 1
                });
            }
            renderCart();
            floatingCart.classList.add('open'); // Open cart when item is added
        });
    });

    // Handle quantity changes and item removal within the cart
    cartItemsContainer.addEventListener('click', (event) => {
        const target = event.target;
        const cartItemDiv = target.closest('.cart-item');
        if (!cartItemDiv) return;

        // Stop the click from bubbling up to the document, which would trigger the
        // 'click outside' handler and close the cart.
        event.stopPropagation();

        const productId = cartItemDiv.dataset.productId;
        const itemIndex = cart.findIndex(item => item.id === productId);

        if (itemIndex > -1) {
            if (target.classList.contains('increase-quantity')) {
                cart[itemIndex].quantity++;
            } else if (target.classList.contains('decrease-quantity')) {
                if (cart[itemIndex].quantity > 1) {
                    cart[itemIndex].quantity--;
                }
            } else if (target.closest('.remove-item-btn')) {
                cart.splice(itemIndex, 1); // Remove item
            }
            renderCart();
        }
    });

    // Toggle floating cart visibility
    cartToggleIcon.addEventListener('click', () => {
        // Close user menu if it's open to ensure only one popup is visible
        const userMenu = document.querySelector('.user-menu');
        if (userMenu && userMenu.querySelector('.user-menu-dropdown').classList.contains('show')) {
            userMenu.querySelector('.user-menu-dropdown').classList.remove('show');
            userMenu.querySelector('.user-menu-toggle').classList.remove('open');
        }
        floatingCart.classList.toggle('open');
    });

    // Close cart when 'x' is clicked
    closeCartBtn.addEventListener('click', () => {
        floatingCart.classList.remove('open');
    });

    // User dropdown menu
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        const userMenuToggle = userMenu.querySelector('.user-menu-toggle');
        const userMenuDropdown = userMenu.querySelector('.user-menu-dropdown');

        userMenuToggle.addEventListener('click', () => {
            // Close cart if it's open to ensure only one popup is visible
            if (floatingCart.classList.contains('open')) {
                floatingCart.classList.remove('open');
            }
            userMenuDropdown.classList.toggle('show');
            userMenuToggle.classList.toggle('open');
        });
    }

    // Combined listener for clicks outside of active elements
    document.addEventListener('click', (event) => {
        // Close cart if clicked outside
        if (floatingCart.classList.contains('open') && !floatingCart.contains(event.target) && !cartToggleIcon.contains(event.target)) {
            floatingCart.classList.remove('open');
        }

        // Close user dropdown if clicked outside
        if (userMenu) {
            const userMenuDropdown = userMenu.querySelector('.user-menu-dropdown');
            const userMenuToggle = userMenu.querySelector('.user-menu-toggle');
            if (userMenuDropdown.classList.contains('show') && !userMenu.contains(event.target)) {
                userMenuDropdown.classList.remove('show');
                userMenuToggle.classList.remove('open');
            }
        }
    });

    // Initial render of the cart when the page loads
    renderCart();
});