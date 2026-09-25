<?php
$page = 'users';

global $conn;

// Search and filter
$search = $_GET['search'] ?? '';
$page_num = $_GET['page'] ?? 1;
$perPage = 20;
$offset = ($page_num - 1) * $perPage;

// Build query
$where = '';
$params = [];
if ($search) {
    $where = "WHERE email LIKE ?";
    $params[] = "%$search%";
}

// Get total count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users $where");
$stmt->execute($params);
$totalUsers = $stmt->fetch()['count'];
$totalPages = ceil($totalUsers / $perPage);

// Get users with card count
$stmt = $conn->prepare("
    SELECT u.*, COUNT(rc.id) as card_count 
    FROM users u 
    LEFT JOIN review_cards rc ON u.id = rc.user_id 
    $where 
    GROUP BY u.id 
    ORDER BY u.created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get additional stats for each user
foreach ($users as &$user) {
    // Get review count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM reviews r 
        JOIN review_cards rc ON r.card_id = rc.id 
        WHERE rc.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $user['review_count'] = $stmt->fetch()['count'];
    
    // Get feedback count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM feedbacks f 
        JOIN review_cards rc ON f.card_id = rc.id 
        WHERE rc.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $user['feedback_count'] = $stmt->fetch()['count'];
}
unset($user);

ob_start();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Users</h2>
    <p class="text-gray-500 mt-1">Manage all registered users</p>
</div>

<!-- Search -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex gap-4">
        <div class="flex-1 relative">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Search by email..." 
                   class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700 transition-colors">
            <i class="fas fa-search mr-2"></i> Search
        </button>
        <?php if ($search): ?>
            <a href="<?= url('/owner/users') ?>" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition-colors">
                <i class="fas fa-times mr-2"></i> Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cards</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviews</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feedbacks</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <p>No users found</p>
                            <?php if ($search): ?>
                                <a href="<?= url('/owner/users') ?>" class="text-purple-600 hover:text-purple-700 text-sm font-medium mt-2 inline-block">
                                    Clear search →
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-600 font-mono">#<?= $user['id'] ?></td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($user['email']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <?= $user['card_count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <?= $user['review_count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <?= $user['feedback_count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="viewUser(<?= $user['id'] ?>)" 
                                        class="text-purple-600 hover:text-purple-800 transition-colors" 
                                        title="View User Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex justify-between items-center mt-6">
    <p class="text-sm text-gray-500">
        Showing <?= ($offset + 1) ?> - <?= min($offset + $perPage, $totalUsers) ?> of <?= $totalUsers ?> users
    </p>
    <div class="flex space-x-2">
        <?php if ($page_num > 1): ?>
            <a href="?page=<?= $page_num - 1 ?>&search=<?= urlencode($search) ?>" 
               class="px-4 py-2 rounded-lg text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-colors">
                <i class="fas fa-chevron-left mr-1"></i> Previous
            </a>
        <?php endif; ?>
        
        <?php
        $start = max(1, $page_num - 2);
        $end = min($totalPages, $page_num + 2);
        ?>
        <?php if ($start > 1): ?>
            <a href="?page=1&search=<?= urlencode($search) ?>" 
               class="px-4 py-2 rounded-lg text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-colors">
                1
            </a>
            <?php if ($start > 2): ?>
                <span class="px-2 py-2 text-gray-400">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
               class="px-4 py-2 rounded-lg text-sm transition-colors <?= $i == $page_num ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
                <span class="px-2 py-2 text-gray-400">...</span>
            <?php endif; ?>
            <a href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>" 
               class="px-4 py-2 rounded-lg text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-colors">
                <?= $totalPages ?>
            </a>
        <?php endif; ?>
        
        <?php if ($page_num < $totalPages): ?>
            <a href="?page=<?= $page_num + 1 ?>&search=<?= urlencode($search) ?>" 
               class="px-4 py-2 rounded-lg text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-colors">
                Next <i class="fas fa-chevron-right ml-1"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
function viewUser(userId) {
    // Show user details in a modal or redirect
    window.location.href = '<?= url("/owner/users/") ?>' + userId;
}
</script>

<?php
$content = ob_get_clean();
require 'views/owner/layout.php';
?>