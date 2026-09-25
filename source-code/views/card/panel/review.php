<?php
$page = 'reviews';
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

$content = getReviewsContent($card, $conn);

function getReviewsContent($card, $conn) {
    // Get filter parameters
    $filter = $_GET['filter'] ?? 'all'; // all, reviews, feedback
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // Get statistics
    $stats = [
        'total_reviews' => 0,
        'high' => 0,
        'low' => 0,
        'feedbacks' => 0,
        'avg' => 0
    ];

    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as high,
            SUM(CASE WHEN rating <= 3 THEN 1 ELSE 0 END) as low,
            AVG(rating) as avg
        FROM reviews WHERE card_id = ?
    ");
    $statsStmt->execute([$card['id']]);
    $statsData = $statsStmt->fetch();
    
    if ($statsData) {
        $stats['total_reviews'] = $statsData['total'];
        $stats['high'] = $statsData['high'];
        $stats['low'] = $statsData['low'];
        $stats['avg'] = $statsData['avg'];
    }
    
    // Get feedbacks count
    $fbStmt = $conn->prepare("SELECT COUNT(*) as count FROM feedbacks WHERE card_id = ?");
    $fbStmt->execute([$card['id']]);
    $stats['feedbacks'] = $fbStmt->fetch()['count'];

    // Build queries based on filter
    $reviews = [];
    $feedbacks = [];
    $totalReviews = 0;
    $totalPages = 0;

    if ($filter === 'all' || $filter === 'reviews') {
        // Build query for reviews
        $whereConditions = ['card_id = ?'];
        $params = [$card['id']];

        if (!empty($search)) {
            $whereConditions[] = '(customer_name LIKE ? OR body LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Get total count for pagination
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE $whereClause");
        $countStmt->execute($params);
        $totalReviews = $countStmt->fetch()['total'];
        $totalPages = ceil($totalReviews / $limit);

        // Get reviews
        $stmt = $conn->prepare("
            SELECT * FROM reviews 
            WHERE $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $reviews = $stmt->fetchAll();
    }

    if ($filter === 'all' || $filter === 'feedback') {
        // Build query for feedbacks
        $feedbackWhere = ['card_id = ?'];
        $feedbackParams = [$card['id']];
        
        if (!empty($search)) {
            $feedbackWhere[] = '(customer_name LIKE ? OR message LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $feedbackParams[] = '%' . $search . '%';
            $feedbackParams[] = '%' . $search . '%';
            $feedbackParams[] = '%' . $search . '%';
            $feedbackParams[] = '%' . $search . '%';
        }
        
        $feedbackClause = implode(' AND ', $feedbackWhere);
        
        $stmt = $conn->prepare("SELECT * FROM feedbacks WHERE $feedbackClause ORDER BY created_at DESC");
        $stmt->execute($feedbackParams);
        $feedbacks = $stmt->fetchAll();
    }

    ob_start();
    ?>

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Manage Reviews & Feedback</h2>
        <p class="text-gray-600 mt-1">View all customer reviews and feedback submissions.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Total Reviews</p>
            <p class="text-2xl font-bold text-gray-900"><?= $stats['total_reviews'] ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Avg Rating</p>
            <p class="text-2xl font-bold <?= $stats['avg'] >= 4 ? 'text-green-600' : ($stats['avg'] >= 3 ? 'text-yellow-600' : 'text-red-600') ?>">
                <?= number_format($stats['avg'], 1) ?>⭐
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">High (4-5⭐)</p>
            <p class="text-2xl font-bold text-green-600"><?= $stats['high'] ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Low (1-3⭐)</p>
            <p class="text-2xl font-bold text-orange-600"><?= $stats['low'] ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Feedbacks</p>
            <p class="text-2xl font-bold text-red-600"><?= $stats['feedbacks'] ?></p>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <!-- <a href="?filter=all" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                    All
                </a> -->
                <a href="?filter=reviews" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'reviews' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        Reviews
                    </span>
                </a>
                <a href="?filter=feedback" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'feedback' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        Feedbacks
                    </span>
                </a>
            </div>
            <form method="GET" class="flex items-center gap-2 w-full md:w-auto">
                <input type="hidden" name="filter" value="<?= $filter ?>">
                <input type="text" name="search" placeholder="Search..." 
                       value="<?= htmlspecialchars($search) ?>"
                       class="flex-1 md:w-48 px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($search)): ?>
                    <a href="?filter=<?= $filter ?>" class="px-3 py-2 text-gray-500 hover:text-gray-700 text-sm">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Reviews and Feedbacks List -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <?php if ($filter === 'feedback'): ?>
            <!-- Show Feedbacks -->
            <?php if (count($feedbacks) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Customer</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Contact</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Rating</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Message</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Status</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Date</th>
                                <th class="text-center py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbacks as $feedback): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors <?= $feedback['status'] === 'new' ? 'bg-blue-50' : '' ?>">
                                    <td class="py-3 px-4">
                                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($feedback['customer_name']) ?></p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($feedback['email']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($feedback['phone']) ?></p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm font-bold text-red-500"><?= $feedback['rating'] ?>★</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="text-sm text-gray-600 max-w-xs truncate"><?= htmlspecialchars($feedback['message']) ?></p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?= $feedback['status'] === 'new' ? 'bg-red-100 text-red-800' : ($feedback['status'] === 'read' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') ?>">
                                            <?= ucfirst($feedback['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-500"><?= date('M d, Y', strtotime($feedback['created_at'])) ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center justify-center space-x-2">
                                            <?php if ($feedback['status'] === 'new'): ?>
                                                <button onclick="markFeedbackRead(<?= $feedback['id'] ?>)" 
                                                        class="p-1.5 text-yellow-600 hover:bg-yellow-50 rounded-lg transition-colors"
                                                        title="Mark as Read">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="viewFeedback(<?= $feedback['id'] ?>)" 
                                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                    title="View">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            <button onclick="deleteFeedback(<?= $feedback['id'] ?>)" 
                                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                    title="Delete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No feedbacks yet</h3>
                    <p class="text-gray-500 text-sm">Customers haven't submitted any feedback forms.</p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Show Reviews (for 'all' and 'reviews' filters) -->
            <?php if (count($reviews) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Customer</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Rating</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Review</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Type</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Date</th>
                                <th class="text-center py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <?php
                                $rating = (int)$review['rating'];
                                $starColor = $rating >= 4 ? 'text-green-500' : ($rating >= 3 ? 'text-yellow-500' : 'text-red-500');
                                $details = json_decode($review['details'] ?? '{}', true);
                                $isAI = isset($details['ai_generated']) && $details['ai_generated'] === true;
                                ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-4">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($review['customer_name'] ?? 'Anonymous') ?>
                                        </p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm font-bold <?= $starColor ?>"><?= $rating ?>★</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="text-sm text-gray-600 max-w-xs truncate">
                                            <?= htmlspecialchars($review['body'] ?? 'No comment') ?>
                                        </p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php if ($isAI): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                               
                                                Review
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                Customer
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-500"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button onclick="viewReview(<?= $review['id'] ?>)" 
                                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                    title="View">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            <button onclick="deleteReview(<?= $review['id'] ?>)" 
                                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                    title="Delete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            Showing <?= $offset + 1 ?> - <?= min($offset + $limit, $totalReviews) ?> of <?= $totalReviews ?>
                        </p>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" 
                                   class="px-3 py-1 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Previous</a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" 
                                   class="px-3 py-1 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No reviews found</h3>
                    <p class="text-gray-500 text-sm">
                        <?php if (!empty($search)): ?>
                            No reviews match your search criteria.
                        <?php else: ?>
                            Share your review page to start collecting feedback.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search)): ?>
                        <a href="?filter=<?= $filter ?>" class="inline-block mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium">
                            Clear search →
                        </a>
                    <?php else: ?>
                        <a href="<?= url('/review/' . $card['slug']) ?>" target="_blank" 
                           class="inline-block mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium">
                            Open your review page →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- View Review Modal -->
    <div id="viewReviewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">Review Details</h3>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="reviewDetails" class="space-y-4">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>

    <!-- View Feedback Modal -->
    <div id="viewFeedbackModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">Feedback Details</h3>
                <button onclick="closeFeedbackModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="feedbackDetails" class="space-y-4">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 bg-gray-900 text-white px-6 py-3 rounded-xl shadow-lg z-50 hidden flex items-center">
        <i class="fas fa-check-circle text-green-400 mr-2"></i>
        <span id="toastMessage">Action completed!</span>
    </div>

    <script>
    function showToast(message) {
        $('#toastMessage').text(message);
        $('#toast').removeClass('hidden').fadeIn(300);
        setTimeout(() => {
            $('#toast').fadeOut(300);
        }, 3000);
    }

    function viewReview(id) {
        $.ajax({
            url: '<?= url("/api/review?action=get") ?>',
            method: 'GET',
            data: { review_id: id },
            success: function(response) {
                if (response.success) {
                    const review = response.data;
                    const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
                    const details = review.details ? JSON.parse(review.details) : {};
                    const isAI = details.ai_generated === true;
                    
                    $('#reviewDetails').html(`
                        <div class="border-b border-gray-100 pb-4">
                            <p class="text-sm text-gray-500">Customer</p>
                            <p class="text-gray-900 font-medium">${review.customer_name || 'Anonymous'}</p>
                        </div>
                        <div class="border-b border-gray-100 pb-4">
                            <p class="text-sm text-gray-500">Rating</p>
                            <p class="text-2xl font-bold ${review.rating >= 4 ? 'text-green-500' : review.rating >= 3 ? 'text-yellow-500' : 'text-red-500'}">
                                ${stars}
                            </p>
                        </div>
                        <div class="border-b border-gray-100 pb-4">
                            <p class="text-sm text-gray-500">Review</p>
                            <p class="text-gray-900">${review.body || 'No comment'}</p>
                        </div>
                        <div class="border-b border-gray-100 pb-4">
                            <p class="text-sm text-gray-500">Type</p>
                            <p class="text-gray-900">${isAI ? '🤖 AI Generated' : '👤 Customer'}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date</p>
                            <p class="text-gray-900">${new Date(review.created_at).toLocaleString()}</p>
                        </div>
                    `);
                    $('#viewReviewModal').removeClass('hidden').addClass('flex');
                } else {
                    showToast('Error: ' + response.error);
                }
            },
            error: function() {
                showToast('An error occurred. Please try again.');
            }
        });
    }

    function viewFeedback(id) {
        $.ajax({
            url: '<?= url("/api/feedback?action=get") ?>',
            method: 'GET',
            data: { feedback_id: id },
            success: function(response) {
                if (response.success) {
                    const fb = response.data;
                    $('#feedbackDetails').html(`
                        <div class="border-b border-gray-100 pb-4">
                            <p class="text-sm text-gray-500">Customer</p>
                            <p class="text-gray-900 font-medium">${fb.customer_name}</p>
                        </div>
                        <div class="border-b border-gray-100 pb-4">
                            <p class="text-sm text-gray-500">Contact</p>
                            <p class="text-gray-900">📧 ${fb.email || 'N/A'}</p>
                            <p class="text-gray-900">📱 ${fb.phone || 'N/A'}</p>
                        </div>
                        <div class="border-b border-gray-100 pb-4">
                            <p class="text-sm text-gray-500">Rating</p>
                            <p class="text-2xl font-bold text-red-500">${fb.rating}★</p>
                        </div>
                        <div class="border-b border-gray-100 pb-4">
                            <p class="text-sm text-gray-500">Message</p>
                            <p class="text-gray-900">${fb.message}</p>
                        </div>
                        <div class="border-b border-gray-100 pb-4">
                            <p class="text-sm text-gray-500">Status</p>
                            <p class="text-gray-900 capitalize">${fb.status}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date</p>
                            <p class="text-gray-900">${new Date(fb.created_at).toLocaleString()}</p>
                        </div>
                    `);
                    $('#viewFeedbackModal').removeClass('hidden').addClass('flex');
                    
                    // Auto mark as read if new
                    if (fb.status === 'new') {
                        markFeedbackRead(fb.id, true);
                    }
                } else {
                    showToast('Error: ' + response.error);
                }
            },
            error: function() {
                showToast('An error occurred. Please try again.');
            }
        });
    }

    function deleteReview(id) {
        if (!confirm('Are you sure you want to delete this review? This action cannot be undone.')) return;
        
        $.ajax({
            url: '<?= url("/api/review?action=delete") ?>',
            method: 'POST',
            data: { review_id: id },
            success: function(response) {
                if (response.success) {
                    showToast('Review deleted successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + response.error);
                }
            },
            error: function() {
                showToast('An error occurred. Please try again.');
            }
        });
    }

    function deleteFeedback(id) {
        if (!confirm('Are you sure you want to delete this feedback?')) return;
        
        $.ajax({
            url: '<?= url("/api/feedback?action=delete") ?>',
            method: 'POST',
            data: { feedback_id: id },
            success: function(response) {
                if (response.success) {
                    showToast('Feedback deleted successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + response.error);
                }
            },
            error: function() {
                showToast('An error occurred. Please try again.');
            }
        });
    }

    function markFeedbackRead(id, silent = false) {
        $.ajax({
            url: '<?= url("/api/feedback?action=markRead") ?>',
            method: 'POST',
            data: { feedback_id: id },
            success: function(response) {
                if (response.success && !silent) {
                    showToast('Feedback marked as read!');
                    setTimeout(() => location.reload(), 1500);
                }
            }
        });
    }

    function closeViewModal() {
        $('#viewReviewModal').removeClass('flex').addClass('hidden');
    }

    function closeFeedbackModal() {
        $('#viewFeedbackModal').removeClass('flex').addClass('hidden');
    }

    // Close modals on escape or backdrop click
    $(document).keydown(function(e) {
        if (e.key === 'Escape') {
            closeViewModal();
            closeFeedbackModal();
        }
    });

    $('#viewReviewModal').click(function(e) {
        if (e.target === this) closeViewModal();
    });

    $('#viewFeedbackModal').click(function(e) {
        if (e.target === this) closeFeedbackModal();
    });
    </script>

    <?php
    return ob_get_clean();
}

require 'views/card/panel/layout.php';
?>