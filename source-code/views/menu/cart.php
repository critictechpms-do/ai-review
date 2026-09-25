<?php
global $conn;
$slug = $_GET['route_params']['slug'] ?? '';

// Get restaurant details
$stmt = $conn->prepare("SELECT * FROM restaurants WHERE slug = ?");
$stmt->execute([$slug]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    http_response_code(404);
    echo "Restaurant not found";
    exit;
}

// Parse restaurant details
$details = json_decode($restaurant['details'] ?? '{}', true);
$logo = $details['logo'] ?? '';
$banner = $details['banner'] ?? '';
$address = $details['address'] ?? '';
$call = $details['call'] ?? $restaurant['whatsapp'] ?? '';
$enableOrdering = $details['enable_ordering'] ?? false;
$enableTableRoom = $details['enable_table_room'] ?? false;
$enableDelivery = $details['enable_delivery'] ?? false;
$enablePickup = $details['enable_pickup'] ?? false;
$deliveryWhatsapp = $details['delivery_whatsapp'] ?? $restaurant['whatsapp'] ?? '';
$whatsappNumber = preg_replace('/[^0-9]/', '', $deliveryWhatsapp ?: $restaurant['whatsapp'] ?? '');

if (!$enableOrdering) {
    header('Location: ' . url('/menu/' . $slug));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Your Cart | <?= htmlspecialchars($restaurant['name']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .slide-up { animation: slideUp 0.3s ease-out; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-10">
        <div class="max-w-md mx-auto px-4 py-4 flex items-center justify-between">
            <a href="<?= url('/menu/' . $slug) ?>" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-lg font-bold text-gray-900">Your Cart</h1>
            <button onclick="clearCart()" class="text-red-500 hover:text-red-700 text-sm font-medium">
                Clear All
            </button>
        </div>
    </header>

    <!-- Cart Content -->
    <main class="max-w-md mx-auto px-4 py-6">
        
        <!-- Empty Cart State -->
        <div id="emptyCart" class="hidden text-center py-16">
            <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-500 font-medium">Your cart is empty</p>
            <p class="text-gray-400 mt-2">Add items from the menu to get started</p>
            <a href="<?= url('/menu/' . $slug) ?>" 
               class="inline-block mt-6 px-6 py-3 bg-orange-500 text-white rounded-full hover:bg-orange-600 transition-colors font-medium">
                Browse Menu
            </a>
        </div>

        <!-- Cart Items -->
        <div id="cartItems" class="space-y-4">
            <!-- Items will be rendered by JavaScript -->
        </div>

        <!-- Order Options -->
        <div id="orderOptions" class="hidden mt-8">
            
            <!-- Order Type Selection -->
            <?php if ($enableDelivery || $enablePickup || $enableTableRoom): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Order Type</h3>
                <div class="space-y-3">
                    <?php if ($enableTableRoom): ?>
                    <label class="flex items-center p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-orange-50 transition-colors">
                        <input type="radio" name="orderType" value="dine-in" onchange="toggleOrderType('dine-in')" 
                               class="w-5 h-5 text-orange-500 focus:ring-orange-500">
                        <div class="ml-3">
                            <p class="font-medium text-gray-900">Dine In</p>
                            <p class="text-sm text-gray-500">Order to your table</p>
                        </div>
                    </label>
                    <div id="dineInFields" class="hidden ml-8 space-y-3">
                        <input type="text" id="tableNumber" placeholder="Enter table/room number *" 
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <input type="text" id="dineInName" placeholder="Your Name" 
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    </div>
                    <?php endif; ?>

                    <?php if ($enableDelivery): ?>
                    <label class="flex items-center p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-orange-50 transition-colors">
                        <input type="radio" name="orderType" value="delivery" onchange="toggleOrderType('delivery')" 
                               class="w-5 h-5 text-orange-500 focus:ring-orange-500">
                        <div class="ml-3">
                            <p class="font-medium text-gray-900">Delivery</p>
                            <p class="text-sm text-gray-500">Deliver to your address</p>
                        </div>
                    </label>
                    <div id="deliveryFields" class="hidden ml-8 space-y-3">
                        <input type="text" id="deliveryName" placeholder="Full Name *" 
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <input type="tel" id="deliveryPhone" placeholder="Phone Number *" 
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <textarea id="deliveryAddress" placeholder="Delivery Address *" rows="2"
                                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm resize-none"></textarea>
                    </div>
                    <?php endif; ?>

                    <?php if ($enablePickup): ?>
                    <label class="flex items-center p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-orange-50 transition-colors">
                        <input type="radio" name="orderType" value="pickup" onchange="toggleOrderType('pickup')" 
                               class="w-5 h-5 text-orange-500 focus:ring-orange-500">
                        <div class="ml-3">
                            <p class="font-medium text-gray-900">Pickup</p>
                            <p class="text-sm text-gray-500">Pick up from restaurant</p>
                        </div>
                    </label>
                    <div id="pickupFields" class="hidden ml-8 space-y-3">
                        <input type="text" id="pickupName" placeholder="Your Name *" 
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <input type="tel" id="pickupPhone" placeholder="Phone Number *" 
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Special Instructions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Special Instructions</h3>
                <textarea id="specialInstructions" rows="3" placeholder="Any special requests or allergies..."
                          class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm resize-none"></textarea>
            </div>

            <!-- Cart Summary -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h3>
                <div class="space-y-3">
                    <?php if ($enableTableRoom): ?>
                    <div class="flex justify-between text-sm" id="dineInSummary" style="display: none;">
                        <span class="text-gray-600">Table/Room</span>
                        <span class="font-medium" id="summaryTable">-</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($enableDelivery): ?>
                    <div class="flex justify-between text-sm" id="deliverySummary" style="display: none;">
                        <span class="text-gray-600">Delivery To</span>
                        <span class="font-medium" id="summaryDeliveryName">-</span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Items</span>
                        <span class="font-medium" id="itemCount">0</span>
                    </div>
                    <div class="border-t pt-3 flex justify-between">
                        <span class="font-bold text-lg text-gray-900">Total</span>
                        <span class="font-bold text-2xl text-orange-500" id="total">₹0.00</span>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Order Button -->
            <div id="whatsappSection" class="mb-6">
                <?php if ($whatsappNumber): ?>
                    <button onclick="placeOrder()" 
                            class="w-full px-6 py-4 bg-green-600 text-white rounded-full hover:bg-green-700 transition-colors font-bold text-lg flex items-center justify-center shadow-lg">
                        <i class="fab fa-whatsapp text-2xl mr-3"></i>
                        Place Order via WhatsApp
                    </button>
                <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-500 text-xl mr-3 mt-1"></i>
                            <div>
                                <p class="font-medium text-yellow-800">WhatsApp number not available</p>
                                <p class="text-sm text-yellow-600 mt-1">This restaurant hasn't set up their WhatsApp number yet. Please contact them directly.</p>
                                <?php if ($call): ?>
                                    <a href="tel:<?= htmlspecialchars($call) ?>" 
                                       class="inline-block mt-3 px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-phone mr-2"></i> Call: <?= htmlspecialchars($call) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        let cart = [];
        let selectedOrderType = '';

        // Load cart from localStorage
        function loadCart() {
            const cartData = localStorage.getItem('menuCart');
            if (cartData) {
                cart = JSON.parse(cartData);
            }
            
            if (cart.length === 0) {
                $('#emptyCart').removeClass('hidden');
                $('#cartItems').addClass('hidden');
                $('#orderOptions').addClass('hidden');
            } else {
                $('#emptyCart').addClass('hidden');
                $('#cartItems').removeClass('hidden');
                $('#orderOptions').removeClass('hidden');
                renderCartItems();
                updateSummary();
            }
        }

        function renderCartItems() {
            let html = '';
            cart.forEach((item, index) => {
                html += `
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 slide-up">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900">${item.name}</h3>
                                ${item.variant ? `<p class="text-sm text-purple-600">${item.variant}</p>` : ''}
                                ${item.addons && item.addons.length > 0 ? 
                                    `<p class="text-sm text-blue-600">+ ${item.addons.join(', ')}</p>` : ''}
                            </div>
                            <button onclick="removeItem(${index})" class="text-red-400 hover:text-red-600 ml-2 p-1">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center space-x-3">
                                <button onclick="updateQuantity(${index}, -1)" 
                                        class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="font-medium w-6 text-center">${item.quantity}</span>
                                <button onclick="updateQuantity(${index}, 1)" 
                                        class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <span class="font-bold text-orange-500">₹${(item.price * item.quantity).toFixed(2)}</span>
                        </div>
                    </div>
                `;
            });
            $('#cartItems').html(html);
        }

        function updateQuantity(index, change) {
            cart[index].quantity += change;
            if (cart[index].quantity <= 0) {
                removeItem(index);
            } else {
                saveCart();
                renderCartItems();
                updateSummary();
            }
        }

        function removeItem(index) {
            cart.splice(index, 1);
            saveCart();
            loadCart();
        }

        function clearCart() {
            if (cart.length === 0) return;
            if (!confirm('Are you sure you want to clear your cart?')) return;
            cart = [];
            saveCart();
            loadCart();
        }

        function saveCart() {
            localStorage.setItem('menuCart', JSON.stringify(cart));
        }

        function updateSummary() {
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            $('#itemCount').text(itemCount + ' item' + (itemCount !== 1 ? 's' : ''));
            $('#total').text('₹' + total.toFixed(2));
        }

        function toggleOrderType(type) {
            selectedOrderType = type;
            
            // Hide all fields
            $('#dineInFields, #deliveryFields, #pickupFields').addClass('hidden');
            $('#dineInSummary, #deliverySummary').hide();
            
            // Show selected fields
            if (type === 'dine-in') {
                $('#dineInFields').removeClass('hidden');
            } else if (type === 'delivery') {
                $('#deliveryFields').removeClass('hidden');
            } else if (type === 'pickup') {
                $('#pickupFields').removeClass('hidden');
            }
        }

        function validateOrder() {
            if (selectedOrderType === 'dine-in') {
                const tableNo = $('#tableNumber').val().trim();
                if (!tableNo) {
                    alert('Please enter your table/room number');
                    $('#tableNumber').focus();
                    return false;
                }
            } else if (selectedOrderType === 'delivery') {
                const name = $('#deliveryName').val().trim();
                const phone = $('#deliveryPhone').val().trim();
                const address = $('#deliveryAddress').val().trim();
                
                if (!name) {
                    alert('Please enter your full name');
                    $('#deliveryName').focus();
                    return false;
                }
                if (!phone) {
                    alert('Please enter your phone number');
                    $('#deliveryPhone').focus();
                    return false;
                }
                if (!address) {
                    alert('Please enter your delivery address');
                    $('#deliveryAddress').focus();
                    return false;
                }
            } else if (selectedOrderType === 'pickup') {
                const name = $('#pickupName').val().trim();
                const phone = $('#pickupPhone').val().trim();
                
                if (!name) {
                    alert('Please enter your name');
                    $('#pickupName').focus();
                    return false;
                }
                if (!phone) {
                    alert('Please enter your phone number');
                    $('#pickupPhone').focus();
                    return false;
                }
            } else {
                alert('Please select an order type');
                return false;
            }
            return true;
        }

        function placeOrder() {
            if (cart.length === 0) {
                alert('Your cart is empty');
                return;
            }

            if (!validateOrder()) {
                return;
            }

            <?php if (!$whatsappNumber): ?>
                alert('WhatsApp number is not available');
                return;
            <?php endif; ?>

            // Build order message
            let message = `🛒 *New Order for <?= htmlspecialchars($restaurant['name']) ?>*\n\n`;
            
            // Order type and details
            if (selectedOrderType === 'dine-in') {
                const tableNo = $('#tableNumber').val().trim();
                const name = $('#dineInName').val().trim();
                message += `📍 *Order Type:* Dine In\n`;
                message += `🪑 *Table/Room No:* ${tableNo}\n`;
                if (name) message += `👤 *Name:* ${name}\n`;
            } else if (selectedOrderType === 'delivery') {
                const name = $('#deliveryName').val().trim();
                const phone = $('#deliveryPhone').val().trim();
                const address = $('#deliveryAddress').val().trim();
                message += `🛵 *Order Type:* Delivery\n`;
                message += `👤 *Name:* ${name}\n`;
                message += `📱 *Phone:* ${phone}\n`;
                message += `📍 *Address:* ${address}\n`;
            } else if (selectedOrderType === 'pickup') {
                const name = $('#pickupName').val().trim();
                const phone = $('#pickupPhone').val().trim();
                message += `🏃 *Order Type:* Pickup\n`;
                message += `👤 *Name:* ${name}\n`;
                message += `📱 *Phone:* ${phone}\n`;
            }
            
            message += `\n📋 *Order Details:*\n`;
            message += `──────────────────\n`;
            
            cart.forEach((item, index) => {
                message += `\n${index + 1}. *${item.name}*`;
                if (item.variant) message += ` (${item.variant})`;
                message += `\n   Qty: ${item.quantity} × ₹${item.price.toFixed(2)} = ₹${(item.price * item.quantity).toFixed(2)}`;
                if (item.addons && item.addons.length > 0) {
                    message += `\n   ➕ ${item.addons.join(', ')}`;
                }
            });
            
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            message += `\n\n──────────────────`;
            message += `\n💰 *Total: ₹${total.toFixed(2)}*`;
            
            const specialInstructions = $('#specialInstructions').val().trim();
            if (specialInstructions) {
                message += `\n\n📝 *Special Instructions:*\n${specialInstructions}`;
            }
            
            // Encode and open WhatsApp
            const whatsappUrl = `https://wa.me/<?= $whatsappNumber ?>?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
            
            // Clear cart after ordering
            cart = [];
            saveCart();
            loadCart();
        }

        // Load cart on page load
        $(document).ready(function() {
            loadCart();
        });
    </script>
</body>
</html>