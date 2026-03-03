<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful | COSMI BEAUTII</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <header class="navbar"><div class="container"><div class="logo">COSMI<span>BEAUTII</span></div></div></header>

    <div class="container" style="padding-top: 120px; text-align: center;">
        <div class="card" style="padding: 40px; max-width: 600px; margin: auto;">
            <h1 style="color: var(--accent); font-size: 3rem; margin-bottom: 20px;">Thank You!</h1>
            <?php if (isset($_SESSION['order_success_message'])): ?>
                <p style="font-size: 1.2rem; margin-bottom: 30px;"><?php echo htmlspecialchars($_SESSION['order_success_message']); unset($_SESSION['order_success_message']); ?></p>
            <?php else: ?>
                <p style="font-size: 1.2rem; margin-bottom: 30px;">Your order has been placed successfully.</p>
            <?php endif; ?>
            <p>We have received your order and will begin processing it shortly.</p>
            <a href="products.php" class="btn-primary" style="margin-top: 30px;">Continue Shopping</a>
        </div>
    </div>

    <script>
        // Clear the cart from localStorage after a successful order
        document.addEventListener('DOMContentLoaded', () => {
            localStorage.removeItem('cart');
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.textContent = '0';
            }
        });
    </script>

</body>
</html>