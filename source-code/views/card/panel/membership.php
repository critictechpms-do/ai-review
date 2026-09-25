<?php
$page = 'membership';

global $conn;
$slug = $_GET['route_params']['slug'] ?? '';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

// Get card details
$stmt = $conn->prepare("SELECT * FROM review_cards WHERE slug = ? AND user_id = ?");
$stmt->execute([$slug, $_SESSION['user_id']]);
$card = $stmt->fetch();

if (!$card) {
    header('Location: ' . url('/dashboard'));
    exit;
}

// Get all plans from database
$stmt = $conn->prepare("SELECT * FROM plans ORDER BY price ASC");
$stmt->execute();
$plans = $stmt->fetchAll();

// If no plans in database, use default plans
if (empty($plans)) {
    $plans = [
        [
            'id' => 1,
            'name' => 'Trial',
            'description' => 'Perfect for getting started',
            'price' => 0,
            'sale_price' => null,
            'duration' => 14,
            'features' => json_encode([
                'Up to 50 reviews',
                'Basic AI responses',
                'Review management',
                'Valid for 14 days',
                'Basic support'
            ]),
            'cta' => 'Current Plan'
        ],
        [
            'id' => 2,
            'name' => 'Pro',
            'description' => 'Best for growing businesses',
            'price' => 499,
            'sale_price' => 399,
            'duration' => 30,
            'features' => json_encode([
                'Unlimited reviews',
                'Advanced AI responses',
                'Custom branding',
                'Review analytics',
                'Priority support',
                'Custom domain',
                'Remove branding',
                'Export reviews'
            ]),
            'cta' => 'Upgrade to Pro'
        ],
        [
            'id' => 3,
            'name' => 'Enterprise',
            'description' => 'For multiple businesses',
            'price' => 999,
            'sale_price' => 799,
            'duration' => 30,
            'features' => json_encode([
                'Everything in Pro',
                'Multiple cards',
                'Central dashboard',
                'API access',
                'Dedicated support',
                'Custom integrations',
                'Team management',
                'Advanced reporting'
            ]),
            'cta' => 'Contact Us'
        ]
    ];
}

// Parse features JSON for each plan
foreach ($plans as &$plan) {
    if (is_string($plan['features'])) {
        $plan['features'] = json_decode($plan['features'], true);
    }
}
unset($plan);

// Get current plan details
$currentMembership = $card['membership'] ?? 'trial';
$expiryDate = $card['expiry_date'] ?? null;

// Calculate days remaining
$daysLeft = 0;
$isExpired = false;
if ($expiryDate) {
    $now = new DateTime();
    $expiry = new DateTime($expiryDate);
    if ($expiry > $now) {
        $daysLeft = $now->diff($expiry)->days;
    } else {
        $isExpired = true;
        // If expired, set membership to expired
        if ($currentMembership !== 'expired') {
            $currentMembership = 'expired';
            // Update in database
            $stmt = $conn->prepare("UPDATE review_cards SET membership = 'expired' WHERE id = ?");
            $stmt->execute([$card['id']]);
        }
    }
}

// Get usage statistics
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE card_id = ?");
$stmt->execute([$card['id']]);
$reviewCount = $stmt->fetch()['count'];

$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE card_id = ?");
$stmt->execute([$card['id']]);
$avgRating = $stmt->fetch()['avg_rating'] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE card_id = ?");
$stmt->execute([$card['id']]);
$approvedCount = $stmt->fetch()['count'];

// Get platform WhatsApp from settings
$stmt = $conn->query("SELECT whatsapp FROM platform_settings LIMIT 1");
$platformSettings = $stmt->fetch();
$platformWhatsapp = $platformSettings['whatsapp'] ?? env('OWNER_EMAIL', '');

ob_start();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Membership & Plan</h2>
    <p class="text-gray-500 mt-1">Manage your subscription and view plan details</p>
</div>

<!-- Current Plan Status -->
<?php if ($isExpired): ?>
    <!-- Expired Plan Alert -->
    <div class="bg-gradient-to-r from-red-500 to-red-700 rounded-2xl shadow-lg p-6 mb-6 text-white">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-2xl mr-3"></i>
                    <div>
                        <h3 class="text-lg font-bold">Plan Expired</h3>
                        <p class="text-sm opacity-90 mt-1">Your plan has expired. Please upgrade to continue using all features.</p>
                    </div>
                </div>
                <?php if ($expiryDate): ?>
                    <p class="text-sm opacity-80 mt-2 ml-11">Expired on: <?= date('F d, Y', strtotime($expiryDate)) ?></p>
                <?php endif; ?>
            </div>
            <span class="px-3 py-1 text-sm font-bold rounded-full bg-white text-red-700">
                Expired
            </span>
        </div>
    </div>
<?php else: ?>
    <!-- Active Plan Status -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl shadow-lg p-6 mb-6 text-white">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-lg font-bold opacity-90">Current Plan</h3>
                <div class="flex items-center mt-2">
                    <span class="px-3 py-1 text-sm font-bold rounded-full bg-white text-gray-900">
                        <?= ucfirst($currentMembership) ?>
                    </span>
                    <?php if ($daysLeft > 0): ?>
                        <span class="ml-3 text-sm font-medium">
                            <i class="fas fa-clock mr-1"></i>
                            <?= $daysLeft ?> days remaining
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($expiryDate): ?>
                    <p class="text-sm opacity-80 mt-2">
                        Valid until: <?= date('F d, Y', strtotime($expiryDate)) ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <p class="text-3xl font-bold">
                    <?php 
                    $currentPlan = null;
                    foreach ($plans as $plan) {
                        if (strtolower($plan['name']) === strtolower($currentMembership)) {
                            $currentPlan = $plan;
                            break;
                        }
                    }
                    if ($currentPlan && $currentPlan['price'] == 0) {
                        echo 'Free';
                    } elseif ($currentPlan) {
                        echo '₹' . number_format($currentPlan['sale_price'] ?? $currentPlan['price']) . '/mo';
                    } else {
                        echo 'Free';
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- Progress Bar -->
        <?php if ($expiryDate && !$isExpired): 
            $totalDays = $currentPlan['duration'] ?? 30;
            $usedDays = $totalDays - $daysLeft;
            $progressPercent = min(100, max(0, ($usedDays / $totalDays) * 100));
        ?>
        <div class="mt-6">
            <div class="flex justify-between text-sm mb-2 opacity-90">
                <span>Plan Progress</span>
                <span><?= $daysLeft ?> days left</span>
            </div>
            <div class="w-full bg-white/30 rounded-full h-2">
                <div class="bg-white h-2 rounded-full transition-all duration-500" style="width: <?= $progressPercent ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Usage Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Reviews</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= $reviewCount ?></p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-star text-blue-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Average Rating</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($avgRating, 1) ?></p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-star text-yellow-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Status</p>
                <p class="text-lg font-bold <?= $isExpired ? 'text-red-600' : 'text-green-600' ?> mt-1">
                    <?= $isExpired ? 'Expired' : 'Active' ?>
                </p>
            </div>
            <div class="w-10 h-10 <?= $isExpired ? 'bg-red-100' : 'bg-green-100' ?> rounded-xl flex items-center justify-center">
                <i class="fas <?= $isExpired ? 'fa-times-circle text-red-600' : 'fa-check-circle text-green-600' ?>"></i>
            </div>
        </div>
    </div>
</div>

<!-- Available Plans -->
<div class="mb-6">
    <h3 class="text-xl font-bold text-gray-900 mb-4">
        <?= $isExpired ? 'Choose a New Plan' : ($currentMembership === 'trial' ? 'Upgrade Your Plan' : 'Available Plans') ?>
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($plans as $plan): 
            $planType = strtolower($plan['name']);
            $isCurrentPlan = !$isExpired && ($planType === strtolower($currentMembership) || 
                           (isset($plan['type']) && $plan['type'] === $currentMembership));
            $isPopular = stripos($plan['name'], 'pro') !== false;
        ?>
            <div class="bg-white rounded-2xl shadow-sm border <?= $isCurrentPlan ? 'ring-2 ring-green-500 border-green-500' : ($isPopular && !$isCurrentPlan ? 'border-orange-300' : 'border-gray-100') ?> p-6 relative <?= $isPopular && !$isCurrentPlan ? 'md:scale-105 shadow-lg' : '' ?>">
                
                <?php if ($isCurrentPlan): ?>
                    <div class="absolute -top-3 right-4">
                        <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow">
                            <i class="fas fa-check mr-1"></i> Current
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if ($isPopular && !$isCurrentPlan): ?>
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-orange-500 text-white px-4 py-1 rounded-full text-xs font-bold shadow">
                            <i class="fas fa-star mr-1"></i> Popular
                        </span>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mb-6">
                    <h4 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($plan['name']) ?></h4>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($plan['description']) ?></p>
                </div>
                
                <div class="text-center mb-6">
                    <?php if ($plan['price'] == 0): ?>
                        <span class="text-4xl font-extrabold text-gray-900">Free</span>
                    <?php else: ?>
                        <?php if ($plan['sale_price']): ?>
                            <span class="text-4xl font-extrabold text-gray-900">₹<?= number_format($plan['sale_price']) ?></span>
                            <span class="text-lg text-gray-400 line-through ml-2">₹<?= number_format($plan['price']) ?></span>
                        <?php else: ?>
                            <span class="text-4xl font-extrabold text-gray-900">₹<?= number_format($plan['price']) ?></span>
                        <?php endif; ?>
                        <p class="text-sm text-gray-500 mt-1">per <?= $plan['duration'] ?? 30 ?> days</p>
                    <?php endif; ?>
                </div>
                
                <ul class="space-y-2 mb-6">
                    <?php 
                    $features = $plan['features'];
                    if (is_string($features)) {
                        $features = json_decode($features, true);
                    }
                    if (is_array($features)):
                        foreach ($features as $feature): 
                    ?>
                        <li class="flex items-center text-gray-700">
                            <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
                            <span class="text-sm"><?= htmlspecialchars($feature) ?></span>
                        </li>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </ul>
                
                <?php if ($isCurrentPlan): ?>
                    <button class="w-full py-2.5 bg-green-100 text-green-700 rounded-xl font-bold cursor-default" disabled>
                        <i class="fas fa-check mr-2"></i> Current Plan
                    </button>
                <?php elseif ($isExpired && $plan['price'] == 0): ?>
                    <button onclick="reactivateTrial()"
                            class="w-full py-2.5 rounded-xl font-bold transition-all transform hover:scale-105 bg-blue-500 text-white hover:bg-blue-600 shadow-lg">
                        <i class="fas fa-redo mr-2"></i> Reactivate Trial
                    </button>
                <?php else: ?>
                    <button onclick="upgradePlan('<?= htmlspecialchars($plan['name']) ?>', <?= $plan['sale_price'] ?? $plan['price'] ?>)"
                            class="w-full py-2.5 rounded-xl font-bold transition-all transform hover:scale-105 <?= $isPopular ? 'bg-orange-500 text-white hover:bg-orange-600 shadow-lg' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        <?= $plan['cta'] ?? ($plan['price'] == 0 ? 'Get Started' : 'Upgrade') ?>
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Upgrade Modal -->
<div id="upgradeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6">
        <div class="text-center mb-4">
            <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-crown text-orange-500 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold">Upgrade to <span id="upgradePlanName"></span></h3>
        </div>
        
        <div class="flex space-x-3">
            <a id="whatsappUpgradeLink" href="#" target="_blank"
               class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-center font-medium transition-colors">
                <i class="fab fa-whatsapp mr-2"></i> Contact on WhatsApp
            </a>
        </div>
    </div>
</div>

<script>
function upgradePlan(name, price) {
    $('#upgradePlanName').text(name);
    
    const cardName = '<?= htmlspecialchars($card['name'], ENT_QUOTES) ?>';
    const message = `Hi, I want to upgrade my plan to ${name} for my review card "${cardName}"`;
    const whatsappNumber = '<?= preg_replace('/[^0-9]/', '', $platformWhatsapp) ?>';
    $('#whatsappUpgradeLink').attr('href', `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`);
    
    $('#upgradeModal').removeClass('hidden').addClass('flex');
}

function reactivateTrial() {
    if (!confirm('Are you sure you want to reactivate your trial plan? You will get a new 3-day trial period.')) {
        return;
    }
    
    $.ajax({
        url: '<?= url("/api/card?action=reactivateTrial") ?>',
        method: 'POST',
        data: {
            card_id: <?= $card['id'] ?>
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'Unknown error'));
            }
        },
        error: function(xhr) {
            alert('Error reactivating trial. Please try again.');
        }
    });
}

function closeUpgradeModal() {
    $('#upgradeModal').removeClass('flex').addClass('hidden');
}

$('#upgradeModal').click(function(e) {
    if (e.target === this) closeUpgradeModal();
});

$(document).keydown(function(e) {
    if (e.key === 'Escape') closeUpgradeModal();
});
</script>

<?php
$content = ob_get_clean();
require 'views/card/panel/layout.php';
?>