<?php
$page = 'settings';
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

// Parse details
$details = json_decode($card['details'] ?? '{}', true);

// Pre-defined themes
$themes = [
    'purple' => [
        'name' => 'Purple',
        'primary' => '#7c3aed',
        'secondary' => '#6d28d9',
        'gradient' => 'from-purple-600 to-indigo-600',
        'preview' => 'linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%)'
    ],
    'blue' => [
        'name' => 'Blue',
        'primary' => '#2563eb',
        'secondary' => '#1d4ed8',
        'gradient' => 'from-blue-600 to-cyan-600',
        'preview' => 'linear-gradient(135deg, #2563eb 0%, #0891b2 100%)'
    ],
    'pink' => [
        'name' => 'Pink',
        'primary' => '#db2777',
        'secondary' => '#be185d',
        'gradient' => 'from-pink-600 to-rose-600',
        'preview' => 'linear-gradient(135deg, #db2777 0%, #e11d48 100%)'
    ],
    'orange' => [
        'name' => 'Orange',
        'primary' => '#ea580c',
        'secondary' => '#c2410c',
        'gradient' => 'from-orange-600 to-amber-600',
        'preview' => 'linear-gradient(135deg, #ea580c 0%, #d97706 100%)'
    ],
    'green' => [
        'name' => 'Green',
        'primary' => '#059669',
        'secondary' => '#047857',
        'gradient' => 'from-green-600 to-emerald-600',
        'preview' => 'linear-gradient(135deg, #059669 0%, #047857 100%)'
    ],
    'red' => [
        'name' => 'Red',
        'primary' => '#dc2626',
        'secondary' => '#b91c1c',
        'gradient' => 'from-red-600 to-rose-600',
        'preview' => 'linear-gradient(135deg, #dc2626 0%, #e11d48 100%)'
    ],
    'indigo' => [
        'name' => 'Indigo',
        'primary' => '#4f46e5',
        'secondary' => '#4338ca',
        'gradient' => 'from-indigo-600 to-violet-600',
        'preview' => 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)'
    ],
    'teal' => [
        'name' => 'Teal',
        'primary' => '#0d9488',
        'secondary' => '#0f766e',
        'gradient' => 'from-teal-600 to-cyan-600',
        'preview' => 'linear-gradient(135deg, #0d9488 0%, #0891b2 100%)'
    ],
    'rose' => [
        'name' => 'Rose',
        'primary' => '#e11d48',
        'secondary' => '#be123c',
        'gradient' => 'from-rose-600 to-red-600',
        'preview' => 'linear-gradient(135deg, #e11d48 0%, #dc2626 100%)'
    ],
    'amber' => [
        'name' => 'Amber',
        'primary' => '#d97706',
        'secondary' => '#b45309',
        'gradient' => 'from-amber-600 to-yellow-600',
        'preview' => 'linear-gradient(135deg, #d97706 0%, #ca8a04 100%)'
    ]
];

// Set default values if not exists
$selectedTheme = $details['theme'] ?? 'purple';
$enableKeywordSelection = $details['enable_keyword_selection'] ?? true;

$business = [
    'name' => $card['name'],
    'logo' => $details['logo'] ?? '',
    'description' => $details['description'] ?? '',
    'whatsapp' => $details['whatsapp'] ?? '',
    'facebook' => $details['facebook'] ?? '',
    'instagram' => $details['instagram'] ?? '',
    'website' => $details['website'] ?? '',
    'google_review_url' => $details['google_review_url'] ?? '',
    'business_keywords' => $details['business_keywords'] ?? ['professional', 'quality', 'excellent service'],
    'theme' => $selectedTheme,
    'enable_keyword_selection' => $enableKeywordSelection
];

$content = getSettingsContent($card, $business, $themes, $conn);

function getSettingsContent($card, $business, $themes, $conn) {
    ob_start();
    ?>
    
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Card Settings</h2>
        <p class="text-gray-600 mt-1">Manage your review card and AI review settings.</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['settings_success'])): ?>
        <div class="mb-6 flex items-center p-4 text-sm text-green-800 border-t-4 border-green-300 bg-green-50 rounded-lg">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
            </svg>
            <div><?= htmlspecialchars($_SESSION['settings_success']) ?></div>
        </div>
        <?php unset($_SESSION['settings_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['settings_error'])): ?>
        <div class="mb-6 flex items-center p-4 text-sm text-red-800 border-t-4 border-red-300 bg-red-50 rounded-lg">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
            </svg>
            <div><?= htmlspecialchars($_SESSION['settings_error']) ?></div>
        </div>
        <?php unset($_SESSION['settings_error']); ?>
    <?php endif; ?>

    <form action="<?= url('/api/card?action=updateSettings') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
        <input type="hidden" name="slug" value="<?= $card['slug'] ?>">
        
        <!-- Basic Information -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Basic Information</h3>
            
            <!-- Business Name -->
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                <input type="text" id="name" name="name" 
                       value="<?= htmlspecialchars($business['name']) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                       required>
            </div>

            <!-- Logo Upload - Fixed Preview -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <div id="logoPreviewContainer" class="w-24 h-24 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden">
                            <?php if (!empty($business['logo'])): ?>
                                <img src="<?= url('/' . $business['logo']) ?>" 
                                     alt="Logo" 
                                     id="logoPreview"
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <svg class="w-8 h-8 text-gray-400" id="logoPlaceholder" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex-1">
                        <input type="file" id="logo" name="logo" accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-400 mt-1">Recommended: Square image, max 2MB</p>
                        <?php if (!empty($business['logo'])): ?>
                            <label class="inline-flex items-center mt-2">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="ml-2 text-sm text-red-600">Remove logo</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Business Description</label>
                <textarea id="description" name="description" rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                          placeholder="Tell customers about your business..."><?= htmlspecialchars($business['description']) ?></textarea>
            </div>
        </div>

        <!-- Contact & Social Links -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Contact & Social Links</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- WhatsApp -->
                <div>
                    <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fab fa-whatsapp text-green-500 mr-1"></i> WhatsApp Number
                    </label>
                    <input type="tel" id="whatsapp" name="whatsapp" 
                           value="<?= htmlspecialchars($business['whatsapp']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                           placeholder="+1234567890">
                </div>

                <!-- Facebook -->
                <div>
                    <label for="facebook" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fab fa-facebook text-blue-600 mr-1"></i> Facebook URL
                    </label>
                    <input type="url" id="facebook" name="facebook" 
                           value="<?= htmlspecialchars($business['facebook']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                           placeholder="https://facebook.com/yourpage">
                </div>

                <!-- Instagram -->
                <div>
                    <label for="instagram" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fab fa-instagram text-pink-600 mr-1"></i> Instagram URL
                    </label>
                    <input type="url" id="instagram" name="instagram" 
                           value="<?= htmlspecialchars($business['instagram']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                           placeholder="https://instagram.com/yourpage">
                </div>

                <!-- Website -->
                <div>
                    <label for="website" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-globe text-gray-600 mr-1"></i> Website URL
                    </label>
                    <input type="url" id="website" name="website" 
                           value="<?= htmlspecialchars($business['website']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                           placeholder="https://yourwebsite.com">
                </div>
            </div>

            <!-- Google Review URL -->
            <div class="mt-4">
                <label for="google_review_url" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fab fa-google text-blue-500 mr-1"></i> Google Review URL
                </label>
                <input type="url" id="google_review_url" name="google_review_url" 
                       value="<?= htmlspecialchars($business['google_review_url']) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                       placeholder="https://www.google.com/search?q=your+business+review">
                <p class="text-xs text-gray-400 mt-1">Customers will be redirected here after copying the AI review</p>
            </div>
        </div>

        <!-- Theme Selection -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-palette text-purple-600 mr-2"></i> Theme Selection
            </h3>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                <?php foreach ($themes as $key => $theme): ?>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="theme" value="<?= $key ?>" 
                               class="sr-only peer" 
                               <?= $business['theme'] === $key ? 'checked' : '' ?>>
                        <div class="p-3 rounded-xl border-2 transition-all duration-200 peer-checked:border-blue-600 peer-checked:shadow-lg hover:shadow-md">
                            <div class="w-full h-12 rounded-lg mb-2" style="background: <?= $theme['preview'] ?>"></div>
                            <p class="text-xs font-medium text-center text-gray-700"><?= $theme['name'] ?></p>
                            <?php if ($business['theme'] === $key): ?>
                                <div class="absolute top-1 right-1 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-gray-400 mt-3">Select a color theme for your review page</p>
        </div>

        <!-- AI Review Settings -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-robot text-purple-600 mr-2"></i> AI Review Settings
            </h3>
            
            <!-- Enable Keyword Selection Toggle -->
            <div class="mb-6 flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                <div>
                    <label class="text-sm font-medium text-gray-700">Enable Keyword Selection</label>
                    <p class="text-xs text-gray-400">Let customers select keywords before generating AI reviews</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enable_keyword_selection" value="1" 
                           class="sr-only peer"
                           <?= $business['enable_keyword_selection'] ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <div class="mb-4">
                <label for="business_keywords" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Keywords
                </label>
                <input type="text" id="business_keywords" name="business_keywords" 
                       value="<?= htmlspecialchars(implode(', ', $business['business_keywords'] ?? [])) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                       placeholder="professional, quality, excellent service, friendly staff">
                <p class="text-xs text-gray-400 mt-1">Comma-separated keywords to personalize AI reviews</p>
            </div>

            <!-- AI Preview -->
            <div class="mt-4 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                <h4 class="text-sm font-semibold text-purple-800 mb-2">Example AI Review</h4>
                <div id="aiPreview" class="text-sm text-gray-700">
                    <?php 
                    $keywords = implode(', ', array_slice($business['business_keywords'] ?? [], 0, 3));
                    $businessName = $business['name'];
                    echo "I had an amazing experience at {$businessName}! The {$keywords} were outstanding. Highly recommend!";
                    ?>
                </div>
                <p class="text-xs text-gray-400 mt-2">AI will use your business name and keywords to generate personalized reviews</p>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-all transform active:scale-95">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
        </div>
    </form>

    <script>
    // Fixed Logo Preview - Shows preview immediately when image is selected
    document.getElementById('logo')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewContainer = document.getElementById('logoPreviewContainer');
                // Remove placeholder if exists
                const placeholder = document.getElementById('logoPlaceholder');
                if (placeholder) {
                    placeholder.remove();
                }
                // Create or update image preview
                let previewImg = document.getElementById('logoPreview');
                if (!previewImg) {
                    previewImg = document.createElement('img');
                    previewImg.id = 'logoPreview';
                    previewImg.className = 'w-full h-full object-cover';
                    previewContainer.appendChild(previewImg);
                }
                previewImg.src = e.target.result;
                previewContainer.classList.remove('border-dashed', 'border-gray-300');
                previewContainer.classList.add('border', 'border-gray-200');
            };
            reader.readAsDataURL(file);
        }
    });

    // Update AI preview with keywords
    document.getElementById('business_keywords')?.addEventListener('input', function() {
        const keywords = this.value.split(',').map(k => k.trim()).filter(k => k).slice(0, 3);
        const businessName = document.getElementById('name').value || 'Your Business';
        const preview = document.getElementById('aiPreview');
        
        if (keywords.length > 0) {
            preview.textContent = `I had an amazing experience at ${businessName}! The ${keywords.join(', ')} were outstanding. Highly recommend!`;
        } else {
            preview.textContent = `I had an amazing experience at ${businessName}! The quality and service were outstanding. Highly recommend!`;
        }
    });

    document.getElementById('name')?.addEventListener('input', function() {
        document.getElementById('business_keywords')?.dispatchEvent(new Event('input'));
    });
    </script>

    <?php
    return ob_get_clean();
}

require 'views/card/panel/layout.php';
?>