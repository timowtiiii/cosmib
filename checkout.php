<?php
session_start();
include 'db.php';

// Require users to be logged in to check out
if (!isset($_SESSION['user_id'])) {
    // Store the page they were trying to access
    $_SESSION['redirect_to'] = 'checkout.php';
    // Add a message for the login page to display (optional)
    $_SESSION['login_message'] = 'You need to log in to place an order.';
    header('Location: login.php');
    exit;
}

// Pre-fill form with logged-in user's data from session
$user_name = $_SESSION['username'] ?? '';
$user_email = $_SESSION['email'] ?? '';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $cart_data = json_decode($_POST['cart_data'], true);
    $error = '';

    if (empty($name) || empty($email) || empty($address) || empty($cart_data)) {
        $error = "Please fill in all fields and make sure your cart is not empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $total_price = 0;
        foreach ($cart_data as $item) {
            $total_price += $item['price'] * $item['quantity'];
        }

        $conn->begin_transaction();

        try {
            // Insert into orders table
            $sql_order = "INSERT INTO orders (customer_name, customer_email, customer_address, total_price) VALUES (?, ?, ?, ?)";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->bind_param("sssd", $name, $email, $address, $total_price);
            $stmt_order->execute();
            $order_id = $stmt_order->insert_id;

            // Insert into order_items table
            $sql_items = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt_items = $conn->prepare($sql_items);

            foreach ($cart_data as $item) {
                $product_id = (int)$item['id'];
                $quantity = (int)$item['quantity'];
                $price = (float)$item['price'];
                $stmt_items->bind_param("iiid", $order_id, $product_id, $quantity, $price);
                $stmt_items->execute();
            }

            $conn->commit();

            $_SESSION['order_success_message'] = "Your order has been placed successfully! Your Order ID is #{$order_id}.";
            header("Location: order_success.php");
            exit;

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $error = "There was an error processing your order. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | COSMI BEAUTII</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .checkout-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .checkout-form label { display: block; margin-bottom: 8px; font-weight: 500; }
        .summary-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .summary-item-info { font-size: 0.9rem; }
        .summary-item-info span { color: var(--text-muted); }
        @media (max-width: 768px) { .checkout-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <header class="navbar"><div class="container"><div class="logo">COSMI<span>BEAUTII</span></div><nav><ul class="nav-links"><li><a href="index.php">Home</a></li><li><a href="products.php">Shop</a></li></ul></nav></div></header>

    <section id="checkout" style="padding-top: 120px; padding-bottom: 100px;">
        <div class="container">
            <h2 class="section-title">Checkout</h2>
            <div class="checkout-layout">
                <div class="card checkout-form">
                    <h3>Shipping Information</h3>
                    <?php if (!empty($error)): ?>
                        <p style="color: red; background: rgba(255,0,0,0.1); padding: 10px; border-radius: 5px; margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <form id="checkout-form" action="checkout.php" method="post">
                        <div style="margin-bottom: 20px;"><label for="name">Full Name</label><input type="text" id="name" name="name" required style="width: 100%; padding: 10px;" value="<?php echo htmlspecialchars($user_name); ?>"></div>
                        <div style="margin-bottom: 20px;"><label for="email">Email Address</label><input type="email" id="email" name="email" required style="width: 100%; padding: 10px;" value="<?php echo htmlspecialchars($user_email); ?>"></div>
                        <div style="margin-bottom: 20px;"><label for="address">Shipping Address</label><textarea id="address" name="address" required style="width: 100%; padding: 10px; min-height: 100px;"></textarea></div>
                        <input type="hidden" name="cart_data" id="cart-data-input">
                        <button type="submit" class="btn-primary" style="width: 100%;">Place Order</button>
                    </form>
                </div>
                <div class="card checkout-summary">
                    <h3>Order Summary</h3>
                    <div id="cart-summary-items"><p>Your cart is empty.</p></div>
                    <div id="cart-summary-total" style="border-top: 1px solid var(--border-color); margin-top: 20px; padding-top: 20px; font-size: 1.5rem; font-weight: 600;">Total: ₱0.00</div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartSummaryContainer = document.getElementById('cart-summary-items');
            const cartSummaryTotal = document.getElementById('cart-summary-total');
            const cartDataInput = document.getElementById('cart-data-input');
            const checkoutForm = document.getElementById('checkout-form');

            function renderSummary() {
                cartSummaryContainer.innerHTML = '';
                let total = 0;
                if (cart.length === 0) {
                    cartSummaryContainer.innerHTML = '<p>Your cart is empty.</p>';
                    cartSummaryTotal.textContent = 'Total: ₱0.00';
                    return;
                }
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    const summaryItem = document.createElement('div');
                    summaryItem.classList.add('summary-item');
                    summaryItem.innerHTML = `<div class="summary-item-info">${item.name} <br><span>${item.quantity} x ₱${item.price.toFixed(2)}</span></div><strong>₱${itemTotal.toFixed(2)}</strong>`;
                    cartSummaryContainer.appendChild(summaryItem);
                });
                cartSummaryTotal.textContent = `Total: ₱${total.toFixed(2)}`;
            }

            checkoutForm.addEventListener('submit', (e) => {
                cartDataInput.value = JSON.stringify(cart);
            });

            renderSummary();
        });
    </script>

</body>
</html>