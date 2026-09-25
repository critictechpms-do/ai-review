<?php
$page = 'home';

global $conn;

// Get stats
$stmt = $conn->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM review_cards");
$totalCards = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM reviews");
$totalReviews = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM feedbacks");
$totalFeedbacks = $stmt->fetch()['count'];

// Get average rating
$stmt = $conn->query("SELECT AVG(rating) as avg FROM reviews");
$avgRating = $stmt->fetch()['avg'] ?? 0;

// Get high rating count (4-5 stars)
$stmt = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE rating >= 4");
$highRatings = $stmt->fetch()['count'];

// Membership distribution
$stmt = $conn->query("SELECT membership, COUNT(*) as count FROM review_cards GROUP BY membership");
$membershipStats = $stmt->fetchAll();

// Recent users
$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recentUsers = $stmt->fetchAll();

// Recent review cards
$stmt = $conn->query("
    SELECT rc.*, u.email as user_email 
    FROM review_cards rc 
    JOIN users u ON rc.user_id = u.id 
    ORDER BY rc.created_at DESC 
    LIMIT 5
");
$recentCards = $stmt->fetchAll();

// Recent reviews
$stmt = $conn->query("
    SELECT r.*, rc.name as card_name, rc.slug 
    FROM reviews r 
    JOIN review_cards rc ON r.card_id = rc.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$recentReviews = $stmt->fetchAll();

// Recent feedbacks
$stmt = $conn->query("
    SELECT f.*, rc.name as card_name 
    FROM feedbacks f 
    JOIN review_cards rc ON f.card_id = rc.id 
    ORDER BY f.created_at DESC 
    LIMIT 5
");
$recentFeedbacks = $stmt->fetchAll();

// Rating distribution
$stmt = $conn->query("
    SELECT rating, COUNT(*) as count 
    FROM reviews 
    GROUP BY rating 
    ORDER BY rating DESC
");
$ratingDistribution = $stmt->fetchAll();

ob_start();
?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900">Owner Dashboard</h2>
    <p class="text-gray-500 mt-1">Overview of your review platform</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Users</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($totalUsers) ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Review Cards</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($totalCards) ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-id-card text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Reviews</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($totalReviews) ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-star text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Feedbacks</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($totalFeedbacks) ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-comment text-red-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Avg Rating</p>
                <p class="text-3xl font-bold <?= $avgRating >= 4 ? 'text-green-600' : ($avgRating >= 3 ? 'text-yellow-600' : 'text-red-600') ?> mt-1">
                    <?= number_format($avgRating, 1) ?>⭐
                </p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div> -->
</div>

<div class="grid grid-cols-1   gap-6">
    <!-- Recent Review Cards -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recent Cards</h3>
            <a href="<?= url('/owner/cards') ?>" class="text-sm text-purple-600 hover:text-purple-800">View All</a>
        </div>
        <div class="space-y-3">
            <?php foreach ($recentCards as $card): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($card['name']) ?></p>
                        <p class="text-xs text-gray-500">by <?= htmlspecialchars($card['user_email']) ?></p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $card['membership'] === 'pro' ? 'bg-blue-100 text-blue-800' : ($card['membership'] === 'enterprise' ? 'bg-purple-100 text-purple-800' : 'bg-yellow-100 text-yellow-800') ?>">
                        <?= ucfirst($card['membership']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require 'views/owner/layout.php';
?>