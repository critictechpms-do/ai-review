<?php
$page = 'cards';

global $conn;

// Get search and filter params
$search = $_GET['search'] ?? '';
$membershipFilter = $_GET['membership'] ?? '';
$page_num = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page_num - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(rc.name LIKE ? OR rc.slug LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($membershipFilter) {
    $where[] = "rc.membership = ?";
    $params[] = $membershipFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM review_cards rc JOIN users u ON rc.user_id = u.id $whereClause");
$stmt->execute($params);
$totalCards = $stmt->fetch()['count'];
$totalPages = ceil($totalCards / $perPage);

// Get cards with user info and stats
$stmt = $conn->prepare("
    SELECT rc.*, u.email as user_email, u.id as user_id,
           COUNT(DISTINCT r.id) as review_count,
           COUNT(DISTINCT f.id) as feedback_count,
           AVG(r.rating) as avg_rating
    FROM review_cards rc 
    JOIN users u ON rc.user_id = u.id 
    LEFT JOIN reviews r ON rc.id = r.card_id
    LEFT JOIN feedbacks f ON rc.id = f.card_id
    $whereClause 
    GROUP BY rc.id 
    ORDER BY rc.created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$cards = $stmt->fetchAll();

// Get all plans for assignment
$stmt = $conn->query("SELECT * FROM plans ORDER BY price ASC");
$plans = $stmt->fetchAll();

// Membership counts for filter
$stmt = $conn->query("SELECT membership, COUNT(*) as count FROM review_cards GROUP BY membership");
$membershipCounts = $stmt->fetchAll();
$membershipData = [];
foreach ($membershipCounts as $mc) {
    $membershipData[$mc['membership']] = $mc['count'];
}

// Get all unique membership types
$stmt = $conn->query("SELECT DISTINCT membership FROM review_cards ORDER BY membership");
$membershipTypes = $stmt->fetchAll();

ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Review Cards</h2>
        <p class="text-gray-500 mt-1">Manage all review cards and memberships</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-sm text-gray-600">Total Cards</p>
        <p class="text-2xl font-bold text-gray-900"><?= number_format($totalCards) ?></p>
    </div>
    <?php foreach ($membershipData as $type => $count): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-sm text-gray-600"><?= ucfirst($type) ?></p>
            <p class="text-2xl font-bold <?= getMembershipColor($type) ?>"><?= number_format($count) ?></p>
        </div>
    <?php endforeach; ?>
    <?php if (count($membershipData) < 3): ?>
        <?php for ($i = count($membershipData); $i < 3; $i++): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-sm text-gray-600">-</p>
                <p class="text-2xl font-bold text-gray-300">0</p>
            </div>
        <?php endfor; ?>
    <?php endif; ?>
</div>

<!-- Search and Filters -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex gap-4 flex-wrap">
        <div class="flex-1 min-w-[200px] relative">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Search by name, slug or email..." 
                   class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none">
        </div>
        <select name="membership" class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none">
            <option value="">All Memberships</option>
            <?php foreach ($membershipTypes as $mt): ?>
                <option value="<?= htmlspecialchars($mt['membership']) ?>" <?= $membershipFilter === $mt['membership'] ? 'selected' : '' ?>>
                    <?= ucfirst($mt['membership']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700 transition-colors">
            <i class="fas fa-filter mr-1"></i> Filter
        </button>
        <?php if ($search || $membershipFilter): ?>
            <a href="?page=1" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                <i class="fas fa-times mr-1"></i> Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Cards Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Card</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membership</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <?php if (empty($cards)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p>No review cards found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cards as $card): 
                        $avgRating = round($card['avg_rating'] ?? 0, 1);
                    ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($card['name']) ?></p>
                                    <p class="text-xs text-gray-500">/review/<?= htmlspecialchars($card['slug']) ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900"><?= htmlspecialchars($card['user_email']) ?></p>
                                <p class="text-xs text-gray-500">ID: #<?= $card['user_id'] ?></p>
                            </td>
                         
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= getMembershipBadge($card['membership']) ?>">
                                    <?= ucfirst($card['membership']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($card['expiry_date']): ?>
                                    <?php $isExpired = strtotime($card['expiry_date']) < time(); ?>
                                    <p class="text-sm <?= $isExpired ? 'text-red-600 font-medium' : 'text-gray-600' ?>">
                                        <?= date('M d, Y', strtotime($card['expiry_date'])) ?>
                                    </p>
                                    <?php if ($isExpired): ?>
                                        <span class="text-xs text-red-500 font-medium">Expired</span>
                                    <?php else: ?>
                                        <?php $daysLeft = ceil((strtotime($card['expiry_date']) - time()) / (60 * 60 * 24)); ?>
                                        <span class="text-xs text-green-500"><?= $daysLeft ?> days left</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-sm text-gray-400">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center space-x-2">
                                    <a href="<?= url('/review/' . $card['slug']) ?>" target="_blank" 
                                       class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View Page">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="openMembershipModal(<?= $card['id'] ?>, '<?= htmlspecialchars($card['name'], ENT_QUOTES) ?>', '<?= $card['membership'] ?>', '<?= $card['expiry_date'] ?>')" 
                                            class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Change Membership">
                                        <i class="fas fa-crown"></i>
                                    </button>
                                    <button onclick="deleteCard(<?= $card['id'] ?>, '<?= htmlspecialchars($card['name'], ENT_QUOTES) ?>')" 
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete Card">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
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
        Showing <?= ($offset + 1) ?> - <?= min($offset + $perPage, $totalCards) ?> of <?= $totalCards ?> cards
    </p>
    <div class="flex space-x-2">
        <?php if ($page_num > 1): ?>
            <a href="?page=<?= $page_num - 1 ?>&search=<?= urlencode($search) ?>&membership=<?= urlencode($membershipFilter) ?>" 
               class="px-4 py-2 rounded-lg text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-colors">
                <i class="fas fa-chevron-left mr-1"></i> Previous
            </a>
        <?php endif; ?>
        
        <?php
        $start = max(1, $page_num - 2);
        $end = min($totalPages, $page_num + 2);
        ?>
        <?php if ($start > 1): ?>
            <a href="?page=1&search=<?= urlencode($search) ?>&membership=<?= urlencode($membershipFilter) ?>" 
               class="px-4 py-2 rounded-lg text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-colors">
                1
            </a>
            <?php if ($start > 2): ?>
                <span class="px-2 py-2 text-gray-400">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&membership=<?= urlencode($membershipFilter) ?>" 
               class="px-4 py-2 rounded-lg text-sm transition-colors <?= $i == $page_num ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
                <span class="px-2 py-2 text-gray-400">...</span>
            <?php endif; ?>
            <a href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&membership=<?= urlencode($membershipFilter) ?>" 
               class="px-4 py-2 rounded-lg text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-colors">
                <?= $totalPages ?>
            </a>
        <?php endif; ?>
        
        <?php if ($page_num < $totalPages): ?>
            <a href="?page=<?= $page_num + 1 ?>&search=<?= urlencode($search) ?>&membership=<?= urlencode($membershipFilter) ?>" 
               class="px-4 py-2 rounded-lg text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-colors">
                Next <i class="fas fa-chevron-right ml-1"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Membership Modal -->
<div id="membershipModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Change Membership</h3>
            <button onclick="closeMembershipModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <p class="text-gray-600 mb-4">
            Card: <span id="membershipCardName" class="font-bold"></span>
        </p>
        
        <form id="membershipForm" onsubmit="updateMembership(event)">
            <input type="hidden" id="membershipCardId" name="card_id">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Membership</label>
                <span id="currentMembership" class="px-3 py-1 bg-gray-100 rounded-full text-sm font-medium"></span>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Plan *</label>
                <select id="planSelect" name="plan_id" required class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none">
                    <option value="">Choose a plan...</option>
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?= $plan['id'] ?>" 
                                data-duration="<?= $plan['duration'] ?>"
                                data-name="<?= htmlspecialchars($plan['name']) ?>">
                            <?= htmlspecialchars($plan['name']) ?> - ₹<?= number_format($plan['sale_price'] ?? $plan['price']) ?> (<?= $plan['duration'] ?> days)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Membership Type</label>
                <input type="text" id="membershipType" name="membership" required
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50"
                       placeholder="e.g., trial, pro, enterprise">
                <p class="text-xs text-gray-500 mt-1">Auto-filled from plan name</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                <input type="datetime-local" id="expiryDate" name="expiry_date" required
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none">
            </div>
            
            <div class="flex space-x-3">
                <button type="button" onclick="closeMembershipModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                    <i class="fas fa-crown mr-2"></i> Update Membership
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openMembershipModal(cardId, cardName, currentMembership, currentExpiry) {
    $('#membershipCardId').val(cardId);
    $('#membershipCardName').text(cardName);
    $('#currentMembership').text(currentMembership.charAt(0).toUpperCase() + currentMembership.slice(1));
    $('#membershipType').val(currentMembership);
    
    // Set expiry date if exists
    if (currentExpiry && currentExpiry !== '') {
        const expiry = new Date(currentExpiry.replace(' ', 'T'));
        $('#expiryDate').val(expiry.toISOString().slice(0, 16));
    } else {
        const defaultExpiry = new Date();
        defaultExpiry.setDate(defaultExpiry.getDate() + 30);
        $('#expiryDate').val(defaultExpiry.toISOString().slice(0, 16));
    }
    
    $('#planSelect').val('');
    $('#membershipModal').removeClass('hidden').addClass('flex');
}

function closeMembershipModal() {
    $('#membershipModal').removeClass('flex').addClass('hidden');
}

// Auto-fill when plan is selected
$('#planSelect').on('change', function() {
    const selectedOption = $(this).find('option:selected');
    const planName = selectedOption.data('name');
    const duration = selectedOption.data('duration');
    
    if (planName && duration) {
        // Set membership type from plan name
        const nameLower = planName.toLowerCase();
        if (nameLower.includes('trial')) {
            $('#membershipType').val('trial');
        } else if (nameLower.includes('enterprise')) {
            $('#membershipType').val('enterprise');
        } else if (nameLower.includes('pro')) {
            $('#membershipType').val('pro');
        } else {
            $('#membershipType').val(nameLower);
        }
        
        // Calculate expiry date
        const expiryDate = new Date();
        expiryDate.setDate(expiryDate.getDate() + parseInt(duration));
        $('#expiryDate').val(expiryDate.toISOString().slice(0, 16));
    }
});

function updateMembership(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = $(e.target).find('button[type="submit"]');
    const originalHtml = submitBtn.html();
    
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Updating...');
    
    $.ajax({
        url: '<?= url("/api/owner/cards?action=updateMembership") ?>',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'Unknown error'));
                submitBtn.prop('disabled', false).html(originalHtml);
            }
        },
        error: function(xhr) {
            let errorMsg = 'Error updating membership.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMsg = xhr.responseJSON.error;
            }
            alert(errorMsg);
            submitBtn.prop('disabled', false).html(originalHtml);
        }
    });
}

function deleteCard(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone and will delete all associated reviews and feedbacks.`)) {
        return;
    }
    
    if (!confirm('Please confirm again. This will permanently delete all data.')) {
        return;
    }
    
    $.ajax({
        url: '<?= url("/api/owner/cards?action=delete") ?>',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'Unknown error'));
            }
        },
        error: function(xhr) {
            alert('Error deleting card.');
        }
    });
}

// Close modal on escape or backdrop click
$(document).keydown(function(e) {
    if (e.key === 'Escape') closeMembershipModal();
});

$('#membershipModal').click(function(e) {
    if (e.target === this) closeMembershipModal();
});
</script>

<?php
$content = ob_get_clean();
require 'views/owner/layout.php';

function getMembershipBadge($membership) {
    switch (strtolower($membership)) {
        case 'trial':
            return 'bg-yellow-100 text-yellow-800';
        case 'pro':
            return 'bg-blue-100 text-blue-800';
        case 'enterprise':
            return 'bg-purple-100 text-purple-800';
        case 'premium':
            return 'bg-indigo-100 text-indigo-800';
        case 'basic':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function getMembershipColor($membership) {
    switch (strtolower($membership)) {
        case 'trial': return 'text-yellow-600';
        case 'pro': return 'text-blue-600';
        case 'enterprise': return 'text-purple-600';
        case 'premium': return 'text-indigo-600';
        case 'basic': return 'text-green-600';
        default: return 'text-gray-600';
    }
}
?>