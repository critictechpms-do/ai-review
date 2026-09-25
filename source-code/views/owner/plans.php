<?php
$page = 'plans';

global $conn;

// Get all plans
$stmt = $conn->query("SELECT * FROM plans ORDER BY price ASC");
$plans = $stmt->fetchAll();

// Parse features for display
foreach ($plans as &$plan) {
    $plan['features_array'] = json_decode($plan['features'], true) ?? [];
}
unset($plan);

ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Subscription Plans</h2>
        <p class="text-gray-500 mt-1">Manage pricing plans and features</p>
    </div>
    <button onclick="openAddPlanModal()" 
            class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus mr-2"></i> Add New Plan
    </button>
</div>

<!-- Plans Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <?php if (empty($plans)): ?>
        <div class="col-span-full bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <i class="fas fa-crown text-5xl text-gray-300 mb-4"></i>
            <p class="text-lg text-gray-500">No plans created yet</p>
            <p class="text-sm text-gray-400 mt-1">Create your first subscription plan</p>
        </div>
    <?php else: ?>
        <?php foreach ($plans as $plan): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 relative">
                
                <?php if ($plan['sale_price']): ?>
                    <div class="absolute -top-3 right-4">
                        <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold">Sale</span>
                    </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($plan['name']) ?></h3>
                            <span class="text-sm text-gray-500"><?= $plan['duration'] ?> days</span>
                        </div>
                    </div>
                    <?php if ($plan['description']): ?>
                        <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($plan['description']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="mb-4">
                    <?php if ($plan['sale_price']): ?>
                        <span class="text-3xl font-bold text-gray-900">₹<?= number_format($plan['sale_price']) ?></span>
                        <span class="text-lg text-gray-400 line-through ml-2">₹<?= number_format($plan['price']) ?></span>
                    <?php elseif ($plan['price'] == 0): ?>
                        <span class="text-3xl font-bold text-green-600">Free</span>
                    <?php else: ?>
                        <span class="text-3xl font-bold text-gray-900">₹<?= number_format($plan['price']) ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($plan['features_array'])): ?>
                    <ul class="space-y-2 mb-4">
                        <?php foreach ($plan['features_array'] as $feature): ?>
                            <li class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
                                <?= htmlspecialchars($feature) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if ($plan['cta']): ?>
                    <p class="text-xs text-gray-400 mb-4">Button: <?= htmlspecialchars($plan['cta']) ?></p>
                <?php endif; ?>
                
                <div class="flex space-x-2">
                    <button onclick="editPlan(<?= htmlspecialchars(json_encode($plan)) ?>)" 
                            class="flex-1 px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </button>
                    <button onclick="deletePlan(<?= $plan['id'] ?>)" 
                            class="px-3 py-2 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add/Edit Plan Modal -->
<div id="planModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
        <h3 id="modalTitle" class="text-xl font-bold mb-4">Add New Plan</h3>
        
        <form id="planForm" onsubmit="savePlan(event)" class="space-y-4">
            <input type="hidden" id="planId" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plan Name *</label>
                    <input type="text" id="planName" name="name" required 
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg"
                           placeholder="e.g., Pro Monthly">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹) *</label>
                    <input type="number" id="planPrice" name="price" required step="0.01" min="0"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg"
                           placeholder="0.00">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price (₹)</label>
                    <input type="number" id="planSalePrice" name="sale_price" step="0.01" min="0"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg"
                           placeholder="Optional sale price">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="planDescription" name="description" rows="2"
                              class="w-full px-3 py-2 border border-gray-200 rounded-lg"
                              placeholder="Brief description of the plan"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CTA Button Text</label>
                    <input type="text" id="planCta" name="cta"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg"
                           placeholder="e.g., Get Started">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration (in days) *</label>
                    <input type="number" id="planDuration" name="duration" required min="1"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg"
                           placeholder="e.g., 30 for monthly, 365 for yearly">
                    <p class="text-xs text-gray-500 mt-1">30=Monthly, 90=Quarterly, 180=Half Yearly, 365=Yearly</p>
                </div>
            </div>
            
            <!-- Features -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-medium text-gray-700">Features</label>
                    <button type="button" onclick="addFeature()" 
                            class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-plus mr-1"></i> Add Feature
                    </button>
                </div>
                <div id="featuresContainer" class="space-y-2">
                    <!-- Features will be added here dynamically -->
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closePlanModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Plan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddPlanModal() {
    $('#modalTitle').text('Add New Plan');
    $('#planId').val('');
    $('#planName').val('');
    $('#planPrice').val('');
    $('#planSalePrice').val('');
    $('#planDescription').val('');
    $('#planCta').val('');
    $('#planDuration').val('30');
    $('#featuresContainer').empty();
    addFeature();
    $('#planModal').removeClass('hidden').addClass('flex');
}

function editPlan(plan) {
    $('#modalTitle').text('Edit Plan');
    $('#planId').val(plan.id);
    $('#planName').val(plan.name);
    $('#planPrice').val(plan.price);
    $('#planSalePrice').val(plan.sale_price || '');
    $('#planDescription').val(plan.description || '');
    $('#planCta').val(plan.cta || '');
    $('#planDuration').val(plan.duration || 30);
    
    // Populate features
    $('#featuresContainer').empty();
    const features = JSON.parse(plan.features || '[]');
    if (features.length > 0) {
        features.forEach(feature => addFeature(feature));
    } else {
        addFeature();
    }
    
    $('#planModal').removeClass('hidden').addClass('flex');
}

function addFeature(value = '') {
    const html = `
        <div class="feature-item flex items-center space-x-2">
            <input type="text" name="features[]" value="${value}" 
                   class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm"
                   placeholder="e.g., Unlimited menu items">
            <button type="button" onclick="this.closest('.feature-item').remove()" 
                    class="px-2 py-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    $('#featuresContainer').append(html);
}

function closePlanModal() {
    $('#planModal').removeClass('flex').addClass('hidden');
}

function savePlan(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    $.ajax({
        url: '<?= url("/api/owner/plans?action=save") ?>',
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
            }
        },
        error: function(xhr) {
            alert('Error saving plan. Please try again.');
        }
    });
}

function deletePlan(id) {
    if (!confirm('Are you sure you want to delete this plan? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: '<?= url("/api/owner/plans?action=delete") ?>',
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
        error: function() {
            alert('Error deleting plan. Please try again.');
        }
    });
}

// Close modal on escape or backdrop click
$(document).keydown(function(e) {
    if (e.key === 'Escape') closePlanModal();
});

$('#planModal').click(function(e) {
    if (e.target === this) closePlanModal();
});
</script>

<?php
$content = ob_get_clean();
require 'views/owner/layout.php';
?>