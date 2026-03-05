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

// --- Pancake API Configuration ---
// IMPORTANT: Replace with your actual Pancake application URL.
$pancake_api_url = 'https://your-pancake-app.com/api/v1'; 
// IMPORTANT: Move this key to a secure configuration file and do not expose it publicly.
$pancake_api_key = 'db9fa3fcc59e4a9b9ba66aa82800755e';

$pancake_config = [
    'api_url' => $pancake_api_url,
    'api_key' => $pancake_api_key,
];

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    // New address fields from dropdowns and text input
    $street_address = trim($_POST['street_address']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $region = trim($_POST['region']);

    $cart_data = json_decode($_POST['cart_data'], true);
    $error = '';

    // Construct full address and validate all parts
    $full_address = implode(', ', array_filter([$street_address, $barangay, $city, $province, $region]));

    if (empty($name) || empty($email) || empty($street_address) || empty($barangay) || empty($city) || empty($province) || empty($region) || empty($cart_data)) {
        $error = "Please fill in all fields, including the complete address, and make sure your cart is not empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $total_price = 0;
        foreach ($cart_data as $item) {
            $total_price += $item['price'] * $item['quantity'];
        }

        $conn->begin_transaction();

        try {
            // --- Stock Validation ---
            $sql_stock_check = "SELECT stock FROM products WHERE id = ?";
            $stmt_stock_check = $conn->prepare($sql_stock_check);
            foreach ($cart_data as $item) {
                $product_id = (int)$item['id'];
                $quantity_in_cart = (int)$item['quantity'];

                $stmt_stock_check->bind_param("i", $product_id);
                $stmt_stock_check->execute();
                $stock_result = $stmt_stock_check->get_result()->fetch_assoc();

                if (!$stock_result || $stock_result['stock'] < $quantity_in_cart) {
                    $product_name = htmlspecialchars($item['name']);
                    $available_stock = $stock_result['stock'] ?? 0;
                    throw new Exception("Not enough stock for '{$product_name}'. Only {$available_stock} available, but you have {$quantity_in_cart} in your cart.");
                }
            }
            // --- End Stock Validation ---

            // Insert into orders table
            $sql_order = "INSERT INTO orders (customer_name, customer_email, customer_address, total_price) VALUES (?, ?, ?, ?)";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->bind_param("sssd", $name, $email, $full_address, $total_price);
            $stmt_order->execute();
            $order_id = $stmt_order->insert_id;

            // Insert into order_items table
            $sql_items = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt_items = $conn->prepare($sql_items);
            $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $stmt_update_stock = $conn->prepare($sql_update_stock);

            foreach ($cart_data as $item) {
                $product_id = (int)$item['id'];
                $quantity = (int)$item['quantity'];
                $price = (float)$item['price'];
                $stmt_items->bind_param("iiid", $order_id, $product_id, $quantity, $price);
                $stmt_items->execute();

                // Decrement stock
                $stmt_update_stock->bind_param("ii", $quantity, $product_id);
                $stmt_update_stock->execute();
            }

            $conn->commit();

            // --- Send Order to Pancake API ---
            $order_data_for_pancake = [
                'order_id' => $order_id,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_address' => $full_address,
                'items' => $cart_data,
                'total' => $total_price
            ];
            // This function is defined below, before the HTML starts.
            sendOrderToPancake($pancake_config, $order_data_for_pancake);

            $_SESSION['order_success_message'] = "Your order has been placed successfully! Your Order ID is #{$order_id}.";
            header("Location: order_success.php");
            exit;
        
        } catch (Exception $exception) { // Catch generic Exception to handle our custom stock error
            $conn->rollback();
            $error = "Error: " . $exception->getMessage() . " Please adjust your cart and try again.";
        }
    }
}
?>
<?php
/**
 * Sends a request to the Pancake API.
 *
 * @param string $method The HTTP method (e.g., 'GET', 'POST').
 * @param string $endpoint The API endpoint (e.g., 'clients', 'invoices').
 * @param array $config The Pancake configuration array.
 * @param array|null $data The data to send with the request.
 * @return array The decoded JSON response.
 * @throws Exception If the API request fails.
 */
function pancakeApiRequest($method, $endpoint, $config, $data = null) {
    $ch = curl_init();
    $url = rtrim($config['api_url'], '/') . '/' . ltrim($endpoint, '/');

    if ($method === 'GET' && $data) {
        $url .= '?' . http_build_query($data);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Pancake-API-Key: ' . $config['api_key']
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        return json_decode($response, true);
    } else {
        throw new Exception("Pancake API request failed with status {$http_code}. Endpoint: {$endpoint}. Response: {$response}. cURL Error: {$error}");
    }
}

/**
 * Creates a client and an invoice in Pancake from an order.
 *
 * @param array $config The Pancake configuration array.
 * @param array $order_data The order details from the website.
 */
function sendOrderToPancake($config, $order_data) {
    try {
        // 1. Find client by email to avoid duplicates
        $existing_clients = pancakeApiRequest('GET', 'clients', $config, ['email' => $order_data['customer_email']]);
        
        if (!empty($existing_clients) && isset($existing_clients[0]['id'])) {
            $client_id = $existing_clients[0]['id'];
        } else {
            // 2. Client not found, so create a new one
            $name_parts = explode(' ', $order_data['customer_name'], 2);
            $first_name = $name_parts[0];
            $last_name = $name_parts[1] ?? '';

            $new_client_data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $order_data['customer_email'],
                'address' => $order_data['customer_address']
            ];
            $new_client = pancakeApiRequest('POST', 'clients', $config, $new_client_data);
            $client_id = $new_client['id'] ?? null;
        }

        if (empty($client_id)) {
            throw new Exception("Could not find or create a client ID in Pancake.");
        }

        // 3. Prepare invoice items from the cart data
        $invoice_items = [];
        foreach ($order_data['items'] as $item) {
            $invoice_items[] = [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'description' => 'Product ID: ' . $item['id']
            ];
        }

        // 4. Create the invoice in Pancake
        $invoice_data = ['client_id' => $client_id, 'invoice_number' => 'COSMI-' . $order_data['order_id'], 'notes' => "Order from COSMI BEAUTII website. Local Order ID: #" . $order_data['order_id'], 'items' => $invoice_items];
        pancakeApiRequest('POST', 'invoices', $config, $invoice_data);

    } catch (Exception $e) {
        // If the Pancake API fails, log the error but don't stop the customer.
        // The order is already saved in your local database.
        error_log("Pancake API Integration Error: " . $e->getMessage());
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
                        <div class="form-group"><label for="name">Full Name</label><input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($user_name); ?>"></div>
                        <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user_email); ?>"></div>
                        
                        <!-- New Address Fields -->
                        <div class="form-group">
                            <label for="region">Region</label>
                            <input type="text" id="region" name="region" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="province">Province</label>
                            <input type="text" id="province" name="province" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="city">City/Municipality</label>
                            <input type="text" id="city" name="city" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="barangay">Barangay</label>
                            <input type="text" id="barangay" name="barangay" class="form-control" required>
                        </div>
                        <div class="form-group"><label for="street-address">Street Address, Building, House No.</label><input type="text" id="street-address" name="street_address" class="form-control" required placeholder="e.g. 123 Rizal St."></div>

                        <input type="hidden" name="cart_data" id="cart-data-input">
                        <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Place Order</button>
                    </form>
                </div>
                <div class="card checkout-summary">
                    <h3>Order Summary</h3>
                    <div id="cart-summary-items"><p style="text-align: center; color: var(--text-muted);">Your cart is empty.</p></div>
                    <div id="cart-summary-total"><span>Total:</span> <span>₱0.00</span></div>
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
                const submitButton = checkoutForm.querySelector('button[type="submit"]');

                if (cart.length === 0) {
                    cartSummaryContainer.innerHTML = '<p style="text-align: center; color: var(--text-muted);">Your cart is empty.</p>';
                    cartSummaryTotal.innerHTML = '<span>Total:</span> <span>₱0.00</span>';
                    submitButton.disabled = true; // Disable form submission if cart is empty
                    return;
                }

                submitButton.disabled = false; // Enable form submission if cart has items

                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    const summaryItem = document.createElement('div');
                    summaryItem.classList.add('summary-item');
                    const imageUrl = item.image ? `images/${item.image}` : 'images/placeholder.png';
                    summaryItem.innerHTML = `
                        <img src="${imageUrl}" alt="${item.name}" class="summary-item-img">
                        <div class="summary-item-info">
                            <h4>${item.name}</h4>
                            <span>${item.quantity} x ₱${item.price.toFixed(2)}</span>
                        </div>
                        <strong class="summary-item-price">₱${itemTotal.toFixed(2)}</strong>
                    `;
                    cartSummaryContainer.appendChild(summaryItem);
                });
                cartSummaryTotal.innerHTML = `<span>Total:</span> <span>₱${total.toFixed(2)}</span>`;
            }

            checkoutForm.addEventListener('submit', (e) => {
                cartDataInput.value = JSON.stringify(cart);
            });

            renderSummary();
        });
    </script>

</body>
</html>