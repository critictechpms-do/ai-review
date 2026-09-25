<?php
$page = 'home';
// Add this at the top - get database connection
global $conn;

// Get slug from route params
$slug = $_GET['route_params']['slug'] ?? '';

// Check authentication
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

$content = getHomeContent($card, $conn);

function getHomeContent($card, $conn) {
    // Get statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE card_id = ?");
    $stmt->execute([$card['id']]);
    $reviewCount = $stmt->fetch()['count'];

    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE card_id = ?");
    $stmt->execute([$card['id']]);
    $avgRating = $stmt->fetch()['avg_rating'] ?? 0;

    // Get high rating count (4-5 stars)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE card_id = ? AND rating >= 4");
    $stmt->execute([$card['id']]);
    $highCount = $stmt->fetch()['count'];

    // Get feedbacks count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedbacks WHERE card_id = ?");
    $stmt->execute([$card['id']]);
    $feedbackCount = $stmt->fetch()['count'];

    // Get AI reviews count (reviews with ai_generated flag)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE card_id = ? AND details LIKE '%ai_generated%'");
    $stmt->execute([$card['id']]);
    $aiReviewCount = $stmt->fetch()['count'];

    $reviewUrl = url('/review/' . $card['slug']);
    $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($reviewUrl);
    
    ob_start();
    ?>
    
    <!-- Welcome Section -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Welcome back, <?= htmlspecialchars($card['name']) ?>! 👋</h2>
        <p class="text-gray-600 mt-1">Here's what's happening with your review card.</p>
    </div>

    <!-- Trial Status Alert -->
    <?php if ($card['membership'] === 'trial'): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm text-blue-700">
                        <span class="font-medium">Trial Period:</span> 
                        Your trial expires on <?= date('M d, Y', strtotime($card['expiry_date'])) ?> 
                        (<?= ceil((strtotime($card['expiry_date']) - time()) / 86400) ?> days remaining)
                    </p>
                </div>
                <a href="<?= url('/edit/'. $card['slug'].'/membership') ?>" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                    Upgrade Now
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Reviews</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $reviewCount ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Average Rating</p>
                    <p class="text-2xl font-bold <?= $avgRating >= 4 ? 'text-green-600' : ($avgRating >= 3 ? 'text-yellow-600' : 'text-red-600') ?>">
                        <?= number_format($avgRating, 1) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">High Rating (4-5⭐)</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $highCount ?></p>
                </div>
            </div>
        </div> -->

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">AI Generated</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $aiReviewCount ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Link & QR Code Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Your Review Page</h3>
                <p class="text-sm text-gray-500 mb-4">Share this link with your customers to collect reviews</p>
                
                <!-- Review URL with Copy Button -->
                <div class="flex items-center space-x-2 mb-4">
                    <div class="flex-1 relative">
                        <input type="text" id="reviewUrl" value="<?= $reviewUrl ?>" readonly
                               class="w-full px-4 py-3 pr-20 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono text-gray-700 focus:ring-2 focus:ring-blue-500">
                        <button onclick="copyReviewUrl()" 
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 px-4 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-copy mr-1"></i> Copy
                        </button>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3">
                    <a href="<?= $reviewUrl ?>" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm">
                        <i class="fas fa-external-link-alt mr-2"></i> Open Page
                    </a>
                    <button onclick="downloadQRCode()" 
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors text-sm">
                        <i class="fas fa-download mr-2"></i> Download QR
                    </button>
                    <button onclick="shareReviewPage()" 
                            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-share-alt mr-2"></i> Share
                    </button>
                </div>
            </div>
            
            <!-- QR Code Preview -->
            <div class="flex-shrink-0">
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <img id="qrCodeImage" src="<?= $qrCodeUrl ?>" alt="QR Code" 
                         class="w-40 h-40 object-contain cursor-pointer hover:scale-105 transition-transform"
                         onclick="openQRModal()">
                    <p class="text-xs text-gray-500 text-center mt-2">Click to enlarge</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="<?= url('/edit/' . $card['slug'] . '/reviews?filter=reviews') ?>" 
               class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-blue-50 transition-colors group">
                <svg class="w-5 h-5 text-blue-600 mr-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">Manage Reviews</span>
            </a>

            <a href="<?= url('/edit/' . $card['slug'] . '/reviews?filter=feedback') ?>" 
               class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-yellow-50 transition-colors group">
                <svg class="w-5 h-5 text-yellow-600 mr-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">
                    Feedbacks <?php if ($feedbackCount > 0): ?><span class="bg-yellow-500 text-white text-xs px-2 py-0.5 rounded-full ml-1"><?= $feedbackCount ?></span><?php endif; ?>
                </span>
            </a>

            <a href="<?= url('/edit/' . $card['slug'] . '/settings') ?>" 
               class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-green-50 transition-colors group">
                <svg class="w-5 h-5 text-green-600 mr-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">Card Settings</span>
            </a>

            <a href="<?= $reviewUrl ?>" target="_blank"
               class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-purple-50 transition-colors group">
                <svg class="w-5 h-5 text-purple-600 mr-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">Preview Page</span>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <!-- <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recent Activity</h3>
            <a href="<?= url('/edit/' . $card['slug'] . '/reviews') ?>" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                View All →
            </a>
        </div>
        
        <?php
        // Get recent reviews
        $stmt = $conn->prepare("SELECT * FROM reviews WHERE card_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$card['id']]);
        $recentReviews = $stmt->fetchAll();
        ?>

        <?php if (count($recentReviews) > 0): ?>
            <div class="space-y-4">
                <?php foreach ($recentReviews as $review): ?>
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-xl">
                        <div class="flex-shrink-0">
                            <?php
                            $rating = (int)$review['rating'];
                            $starColors = ['gray', 'red', 'orange', 'yellow', 'green'];
                            $starColor = $starColors[$rating] ?? 'gray';
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $starColor ?>-100 text-<?= $starColor ?>-800">
                                <?= $rating ?> ★
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 font-medium">
                                <?= htmlspecialchars($review['customer_name'] ?? 'Anonymous') ?>
                            </p>
                            <p class="text-sm text-gray-600 truncate">
                                <?= htmlspecialchars($review['body'] ?? 'No comment') ?>
                            </p>
                        </div>
                        <div class="flex-shrink-0 text-xs text-gray-500">
                            <?= date('M d', strtotime($review['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
                <p class="text-gray-500">No reviews yet. Share your page to get started!</p>
                <a href="<?= $reviewUrl ?>" target="_blank" class="inline-block mt-2 text-sm text-blue-600 hover:text-blue-700 font-medium">
                    Open your review page →
                </a>
            </div>
        <?php endif; ?>
    </div> -->

    <!-- QR Code Modal -->
    <div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">Your Review Page QR Code</h3>
                <button onclick="closeQRModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-center mb-4">
                <img id="qrModalImage" src="<?= $qrCodeUrl ?>" alt="QR Code" class="w-64 h-64 mx-auto">
                <p class="text-sm text-gray-500 mt-3">Scan this QR code to leave a review</p>
            </div>
            <div class="text-center text-sm text-gray-600 bg-gray-50 rounded-lg p-3 mb-4">
                <p class="font-mono break-all"><?= $reviewUrl ?></p>
            </div>
            <div class="flex space-x-3">
                <button onclick="downloadQRCode()" 
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                    <i class="fas fa-download mr-2"></i> Download
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 bg-gray-900 text-white px-6 py-3 rounded-xl shadow-lg z-50 hidden flex items-center">
        <i class="fas fa-check-circle text-green-400 mr-2"></i>
        <span id="toastMessage">Copied!</span>
    </div>

    <script>
    function copyReviewUrl() {
        const urlInput = document.getElementById('reviewUrl');
        urlInput.select();
        urlInput.setSelectionRange(0, 99999);
        document.execCommand('copy');
        
        // Show toast
        showToast('Review page link copied to clipboard!');
    }

    function showToast(message) {
        $('#toastMessage').text(message);
        $('#toast').removeClass('hidden').fadeIn(300);
        setTimeout(() => {
            $('#toast').fadeOut(300);
        }, 2000);
    }

    function openQRModal() {
        $('#qrModal').removeClass('hidden').addClass('flex');
    }

    function closeQRModal() {
        $('#qrModal').removeClass('flex').addClass('hidden');
    }

    async function downloadQRCode() {
        const qrUrl = '<?= $qrCodeUrl ?>';
        
        try {
            const response = await fetch(qrUrl);
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = '<?= htmlspecialchars($card['slug']) ?>-qr-code.png';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showToast('QR code downloaded!');
        } catch (error) {
            // Fallback: open in new tab
            window.open(qrUrl, '_blank');
            showToast('Right-click the QR code and save as image');
        }
    }

    function printQRCode() {
        const qrUrl = '<?= $qrCodeUrl ?>';
        const reviewUrl = '<?= $reviewUrl ?>';
        const cardName = '<?= htmlspecialchars($card['name'], ENT_QUOTES) ?>';
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>QR Code - ${cardName}</title>
                <style>
                    body { 
                        display: flex; 
                        flex-direction: column;
                        align-items: center; 
                        justify-content: center; 
                        min-height: 100vh;
                        font-family: Arial, sans-serif;
                        padding: 20px;
                    }
                    .container { text-align: center; }
                    h2 { margin-bottom: 10px; color: #333; }
                    p { color: #666; margin-bottom: 20px; font-size: 14px; }
                    img { max-width: 300px; height: auto; }
                    .url { 
                        margin-top: 20px; 
                        padding: 10px; 
                        background: #f5f5f5; 
                        border-radius: 8px; 
                        font-size: 12px;
                        color: #666;
                    }
                    @media print {
                        body { margin: 0; }
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>${cardName}</h2>
                    <p>Scan this QR code to leave a review</p>
                    <img src="${qrUrl}" alt="QR Code" crossorigin="anonymous">
                    <div class="url">${reviewUrl}</div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        setTimeout(() => {
            printWindow.print();
        }, 500);
    }

    function shareReviewPage() {
        const reviewUrl = '<?= $reviewUrl ?>';
        const cardName = '<?= htmlspecialchars($card['name'], ENT_QUOTES) ?>';
        
        if (navigator.share) {
            navigator.share({
                title: cardName + ' - Leave a Review',
                text: 'I would love your feedback! Please leave a review.',
                url: reviewUrl
            }).catch(() => {});
        } else {
            // Fallback: copy to clipboard
            copyReviewUrl();
        }
    }

    // Close modal on escape or backdrop click
    $(document).keydown(function(e) {
        if (e.key === 'Escape') closeQRModal();
    });

    $('#qrModal').click(function(e) {
        if (e.target === this) closeQRModal();
    });
    </script>

    <?php
    return ob_get_clean();
}

require 'views/card/panel/layout.php';
?>