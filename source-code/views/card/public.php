<?php
global $conn;

// Get slug from route params
$slug = $_GET['route_params']['slug'] ?? '';

// Get card details
$stmt = $conn->prepare("SELECT * FROM review_cards WHERE slug = ?");
$stmt->execute([$slug]);
$card = $stmt->fetch();

if (!$card) {
    // Card not found
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
        <title>Page Not Found</title>
    </head>
    <body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">404</h1>
            <p class="text-gray-600">Review page not found.</p>
        </div>
    </body>
    </html>
<?php
    exit;
}

// Check if membership is expired
$isExpired = false;
$expiryDate = null;
if ($card['expiry_date']) {
    $expiryDate = new DateTime($card['expiry_date']);
    $now = new DateTime();
    $isExpired = $expiryDate < $now;
}

// Get business details from card details JSON
$details = json_decode($card['details'] ?? '{}', true);
$business = [
    'name' => $card['name'],
    'logo' => $details['logo'] ?? '',
    'description' => $details['description'] ?? '',
    'whatsapp' => $details['whatsapp'] ?? '',
    'facebook' => $details['facebook'] ?? '',
    'instagram' => $details['instagram'] ?? '',
    'website' => $details['website'] ?? '',
    'google_review_url' => $details['google_review_url'] ?? 'https://www.google.com/search?q=review',
    'business_keywords' => $details['business_keywords'] ?? ['professional', 'quality', 'excellent service', 'friendly staff', 'clean', 'affordable', 'great value'],
    'enable_keyword_selection' => $details['enable_keyword_selection'] ?? true
];

// Theme colors - Full list from settings
$themes = [
    'purple' => [
        'name' => 'Purple',
        'primary' => '#7c3aed',
        'primary_light' => '#8b5cf6',
        'gradient' => 'from-purple-600 to-indigo-600',
        'gradient_style' => 'background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);'
    ],
    'blue' => [
        'name' => 'Blue',
        'primary' => '#2563eb',
        'primary_light' => '#3b82f6',
        'gradient' => 'from-blue-600 to-cyan-600',
        'gradient_style' => 'background: linear-gradient(135deg, #2563eb 0%, #0891b2 100%);'
    ],
    'pink' => [
        'name' => 'Pink',
        'primary' => '#db2777',
        'primary_light' => '#ec4899',
        'gradient' => 'from-pink-600 to-rose-600',
        'gradient_style' => 'background: linear-gradient(135deg, #db2777 0%, #e11d48 100%);'
    ],
    'orange' => [
        'name' => 'Orange',
        'primary' => '#ea580c',
        'primary_light' => '#f97316',
        'gradient' => 'from-orange-600 to-amber-600',
        'gradient_style' => 'background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);'
    ],
    'green' => [
        'name' => 'Green',
        'primary' => '#059669',
        'primary_light' => '#10b981',
        'gradient' => 'from-green-600 to-emerald-600',
        'gradient_style' => 'background: linear-gradient(135deg, #059669 0%, #047857 100%);'
    ],
    'red' => [
        'name' => 'Red',
        'primary' => '#dc2626',
        'primary_light' => '#ef4444',
        'gradient' => 'from-red-600 to-rose-600',
        'gradient_style' => 'background: linear-gradient(135deg, #dc2626 0%, #e11d48 100%);'
    ],
    'indigo' => [
        'name' => 'Indigo',
        'primary' => '#4f46e5',
        'primary_light' => '#6366f1',
        'gradient' => 'from-indigo-600 to-violet-600',
        'gradient_style' => 'background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);'
    ],
    'teal' => [
        'name' => 'Teal',
        'primary' => '#0d9488',
        'primary_light' => '#14b8a6',
        'gradient' => 'from-teal-600 to-cyan-600',
        'gradient_style' => 'background: linear-gradient(135deg, #0d9488 0%, #0891b2 100%);'
    ],
    'rose' => [
        'name' => 'Rose',
        'primary' => '#e11d48',
        'primary_light' => '#f43f5e',
        'gradient' => 'from-rose-600 to-red-600',
        'gradient_style' => 'background: linear-gradient(135deg, #e11d48 0%, #dc2626 100%);'
    ],
    'amber' => [
        'name' => 'Amber',
        'primary' => '#d97706',
        'primary_light' => '#f59e0b',
        'gradient' => 'from-amber-600 to-yellow-600',
        'gradient_style' => 'background: linear-gradient(135deg, #d97706 0%, #ca8a04 100%);'
    ]
];

// Get theme from card settings or default to purple
$selectedTheme = $details['theme'] ?? 'purple';
$theme = $themes[$selectedTheme] ?? $themes['purple'];

// Keywords for selection
$keywords = $business['business_keywords'];
$maxKeywords = 3;
$enableKeywordSelection = $business['enable_keyword_selection'];

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title><?= htmlspecialchars($business['name']) ?> | Leave a Review</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .rating-star {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .rating-star:hover {
            transform: scale(1.2);
        }
        .rating-star.selected {
            transform: scale(1.1);
            color: #fbbf24;
        }
        .rating-star:not(.selected) {
            color: #d1d5db;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .review-card {
            transition: all 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .review-option {
            transition: all 0.3s ease;
        }
        .review-option:hover {
            transform: translateX(5px);
        }
        
        .copy-btn {
            transition: all 0.2s ease;
        }
        .copy-btn:hover {
            transform: scale(1.05);
        }
        
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .keyword-btn {
            transition: all 0.2s ease;
        }
        .keyword-btn:hover {
            transform: scale(1.05);
        }
        .keyword-btn.selected {
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }
        
        .gradient-bg {
            <?= $theme['gradient_style'] ?>
        }
        
        .primary-color {
            color: <?= $theme['primary'] ?>;
        }
        
        .primary-bg {
            background-color: <?= $theme['primary'] ?>;
        }
        
        .primary-border {
            border-color: <?= $theme['primary'] ?>;
        }
        
        .primary-light-bg {
            background-color: <?= $theme['primary'] ?>15;
        }
        
        .primary-text {
            color: <?= $theme['primary'] ?>;
        }
        
        .btn-primary:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px -5px <?= $theme['primary'] ?>40;
        }
        
        .btn-primary {
    background: <?= $theme['primary'] ?>;
    background: <?= $theme['gradient_style'] ?>;
    color: white;
    transition: all 0.3s ease;
    border: none;
}
.btn-primary:hover:not(:disabled) {
    transform: scale(1.02);
    box-shadow: 0 10px 25px -5px <?= $theme['primary'] ?>40;
}
.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}
        .theme-badge {
            background: <?= $theme['gradient_style'] ?>;
            color: white;
        }
        
        /* Ensure Google Review button is visible */
        #googleReviewBtn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col relative" style="background-color: <?= $theme['primary'] ?>08;">

    <!-- Decorative top gradient -->
    <div class="absolute top-0 left-0 right-0 h-64 opacity-10" style="<?= $theme['gradient_style'] ?>"></div>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col items-center px-4 py-8 md:py-16 relative z-10">
        
        <!-- Single Unified Card -->
        <div class="w-full max-w-lg">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 review-card">
                
                <!-- Card Header with Theme Gradient -->
                <div class="p-8 pb-6 text-center text-white relative overflow-hidden gradient-bg">
                    <!-- Decorative circles -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
                    
                    <div class="relative z-10">
                        <!-- Logo -->
                        <?php if (!empty($business['logo'])): ?>
                            <div class="mb-4 inline-block">
                                <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl p-1 bg-white/20 backdrop-blur-sm">
                                    <img src="<?= url("/" . htmlspecialchars($business['logo'])) ?>" 
                                         alt="Logo" 
                                         class="w-full h-full rounded-2xl object-cover shadow-lg">
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Business Name -->
                        <h1 class="text-2xl md:text-3xl font-bold mb-2 drop-shadow-sm">
                            <?= htmlspecialchars($business['name']) ?>
                        </h1>
                        
                        <!-- Description -->
                        <?php if (!empty($business['description'])): ?>
                            <p class="text-white/90 text-sm leading-relaxed max-w-sm mx-auto">
                                <?= htmlspecialchars($business['description']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-6 md:p-8">
                    
                    <!-- Social Links -->
                    <?php if (!empty($business['whatsapp']) || !empty($business['facebook']) || !empty($business['instagram']) || !empty($business['website'])): ?>
                        <div class="flex items-center justify-center gap-2 mb-6 pb-6 border-b border-gray-100 flex-wrap">
                            <?php if (!empty($business['whatsapp'])): ?>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $business['whatsapp']) ?>"
                                   target="_blank"
                                   class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:scale-110 transition-all duration-200"
                                   title="WhatsApp">
                                    <i class="fab fa-whatsapp text-lg"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($business['facebook'])): ?>
                                <a href="<?= htmlspecialchars($business['facebook']) ?>"
                                   target="_blank"
                                   class="p-2.5 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 hover:scale-110 transition-all duration-200"
                                   title="Facebook">
                                    <i class="fab fa-facebook text-lg"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($business['instagram'])): ?>
                                <a href="<?= htmlspecialchars($business['instagram']) ?>"
                                   target="_blank"
                                   class="p-2.5 rounded-xl bg-pink-50 text-pink-600 hover:bg-pink-100 hover:scale-110 transition-all duration-200"
                                   title="Instagram">
                                    <i class="fab fa-instagram text-lg"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($business['website'])): ?>
                                <a href="<?= htmlspecialchars($business['website']) ?>"
                                   target="_blank"
                                   class="p-2.5 rounded-xl bg-purple-50 text-purple-600 hover:bg-purple-100 hover:scale-110 transition-all duration-200"
                                   title="Website">
                                    <i class="fas fa-globe text-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Rating Section -->
                    <div class="mb-2">
                        <h2 class="text-center font-semibold text-gray-800 mb-6 text-lg">
                            How was your experience?
                        </h2>
                        <div class="flex justify-center gap-2 md:gap-3 mb-4">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="rating-star text-4xl md:text-5xl <?= $isExpired ? 'cursor-not-allowed opacity-50' : '' ?>" 
                                      data-rating="<?= $i ?>" 
                                      onclick="<?= $isExpired ? '' : "selectRating($i)" ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <p class="text-center text-xs text-gray-400 mb-2" id="ratingLabel">
                            Tap a star to rate
                        </p>
                        
                        <?php if ($isExpired): ?>
                            <div class="mt-4 text-center">
                                <span class="inline-flex items-center px-4 py-2 bg-red-50 text-red-600 text-sm rounded-lg">
                                    <i class="fas fa-lock mr-2"></i> 
                                    Membership expired. Please renew to leave a review.
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Feedback Form for 1-3 Stars -->
                    <div id="feedbackForm" class="hidden fade-in pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="p-2 rounded-xl primary-light-bg">
                                <i class="fas fa-comment text-sm primary-text"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-sm">Help Us Improve</h3>
                                <p class="text-xs text-gray-500">We value your honest feedback</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-medium text-gray-700 mb-1.5 block">
                                    Your Feedback <span class="text-red-500">*</span>
                                </label>
                                <textarea id="feedbackMessage" rows="4"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-<?= $selectedTheme ?>-500 outline-none transition-all text-sm resize-none"
                                    placeholder="Tell us what went wrong and how we can improve..."
                                    oninput="validateFeedbackForm()"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-gray-700 mb-1.5 block">
                                        Your Name <span class="text-gray-400">(optional)</span>
                                    </label>
                                    <input type="text" id="feedbackName"
                                        placeholder="John Doe"
                                        class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-<?= $selectedTheme ?>-500 outline-none transition-all text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-700 mb-1.5 block">
                                        Phone <span class="text-gray-400">(optional)</span>
                                    </label>
                                    <input type="tel" id="feedbackPhone"
                                        placeholder="+1 234 567 890"
                                        class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-<?= $selectedTheme ?>-500 outline-none transition-all text-sm">
                                </div>
                            </div>

                            <button onclick="submitFeedback()"
                                id="submitFeedbackBtn"
                                disabled
                                class="w-full py-5 rounded-xl font-semibold text-white shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-[1.01] btn-primary disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Feedback
                            </button>
                        </div>
                    </div>

                    <!-- Keyword Selection for 4-5 Stars (only if enabled) -->
                    <?php if ($enableKeywordSelection): ?>
                    <div id="keywordSection" class="hidden fade-in pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="p-2 rounded-xl primary-light-bg">
                                <i class="fas fa-tag text-sm primary-text"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-sm">Select Keywords for Your Review</h3>
                                <p class="text-xs text-gray-500">
                                    Choose up to <?= $maxKeywords ?> keywords that describe your experience
                                </p>
                            </div>
                        </div>

                        <!-- Keywords Grid -->
                        <div class="mb-5">
                            <div class="flex flex-wrap gap-2" id="keywordsContainer">
                                <?php foreach ($keywords as $keyword): ?>
                                    <button onclick="toggleKeyword(this, '<?= htmlspecialchars($keyword) ?>')"
                                        class="keyword-btn px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-sm"
                                        data-keyword="<?= htmlspecialchars($keyword) ?>">
                                        <?= htmlspecialchars($keyword) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <p class="text-xs text-gray-400 mt-3">
                                <span id="selectedCount">0</span>/<?= $maxKeywords ?> keywords selected
                            </p>
                        </div>

                        <!-- Generate Button -->
                        <button onclick="generateAIReviews()"
                            id="generateBtn"
                            disabled
                            class="w-full py-5 rounded-xl font-semibold text-white shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-[1.01] btn-primary disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                            <i class="fas fa-sparkles mr-2"></i> Generate AI Reviews
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- AI Reviews for 4-5 Stars -->
                    <div id="aiReviewsSection" class="hidden fade-in pt-4 border-t border-gray-100">
                        <!-- AI Status Badge -->
                        <div class="flex items-center justify-center mb-5">
                            <div class="px-4 py-2 rounded-full text-xs font-medium flex items-center gap-2 primary-light-bg primary-text">
                                <i class="fas fa-sparkles text-xs"></i>
                                <span id="aiStatusText">AI-Powered Reviews Ready</span>
                            </div>
                        </div>

                        <!-- Loading Spinner -->
                        <div id="aiLoading" class="hidden text-center py-4">
                            <div class="inline-block">
                                <svg class="w-12 h-12 text-purple-600 loading-spinner mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-sm text-gray-500 mt-2">Generating AI reviews...</p>
                            </div>
                        </div>

                        <!-- Show selected keywords if any -->
                        <div id="selectedKeywordsDisplay" class="hidden mb-4 p-3 rounded-xl primary-light-bg">
                            <p class="text-xs font-medium text-gray-600 mb-2">Reviews based on your selected keywords:</p>
                            <div class="flex flex-wrap gap-1.5" id="selectedKeywordsTags"></div>
                        </div>

                        <!-- Review Selection -->
                        <div class="space-y-2.5 mb-5">
                            <label class="text-sm font-semibold text-gray-700 flex items-center gap-2 mb-1">
                                <i class="fas fa-thumbs-up text-sm"></i>
                                Select a review to share:
                            </label>
                            <div id="reviewsList" class="space-y-2.5">
                                <!-- Reviews will be injected here -->
                            </div>
                        </div>

                        <!-- Reviewer Name -->
                        <div class="mb-5">
                            <label class="text-xs font-semibold text-gray-700 mb-1.5 flex items-center gap-1.5">
                                <i class="fas fa-user text-xs"></i>
                                Your Name <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <input type="text" id="reviewerName"
                                placeholder="Enter your name"
                                class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-<?= $selectedTheme ?>-500 outline-none transition-all text-sm">
                        </div>

                        <!-- Google Review Button - Always visible but disabled until review is selected -->
                        <button onclick="reviewOnGoogle()"
                            id="googleReviewBtn"
                            disabled
                            class="w-full py-6 rounded-2xl font-bold text-base shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-[1.01] active:scale-[0.99] text-white btn-primary disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                            <i class="fab fa-google mr-3 text-xl"></i>
                            Review us on Google
                        </button>
                        <p class="text-[11px] text-center text-gray-400 mt-3">
                            Select a review above to enable Google review
                        </p>
                    </div>

                    <!-- Success State for Bad Feedback -->
                    <div id="feedbackSuccess" class="hidden fade-in text-center py-6">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 primary-light-bg">
                            <i class="fas fa-check-circle text-2xl primary-text"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Thank You!</h3>
                        <p class="text-gray-600 text-sm">
                            Your feedback has been received. We'll work on improving your experience.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 bg-gray-900 text-white px-6 py-3 rounded-xl shadow-2xl z-50 hidden flex items-center gap-3">
        <i class="fas fa-check-circle text-green-400"></i>
        <span id="toastMessage">Copied!</span>
    </div>

    <script>
        // Theme configuration
        const themeColors = {
            primary: '<?= $theme['primary'] ?>',
            primaryLight: '<?= $theme['primary_light'] ?>'
        };

        let selectedRating = 0;
        let selectedKeywords = [];
        let maxKeywords = <?= $maxKeywords ?>;
        let aiReviews = [];
        let selectedReviewIndex = -1;
        let selectedReviewText = '';
        let isGenerating = false;
        let isExpired = <?= $isExpired ? 'true' : 'false' ?>;
        let cardSlug = '<?= $slug ?>';
        let googleReviewUrl = '<?= $business['google_review_url'] ?>';
        let enableKeywordSelection = <?= $enableKeywordSelection ? 'true' : 'false' ?>;

        // Validate feedback form - enable/disable submit button
        function validateFeedbackForm() {
            const message = $('#feedbackMessage').val().trim();
            const submitBtn = $('#submitFeedbackBtn');
            if (message.length > 0) {
                submitBtn.prop('disabled', false);
            } else {
                submitBtn.prop('disabled', true);
            }
        }

        function selectRating(rating) {
            if (isExpired) {
                showToast('⚠️ Membership expired. Please renew to leave a review.');
                return;
            }
            
            selectedRating = rating;

            // Update stars
            $('.rating-star').each(function() {
                const starRating = parseInt($(this).data('rating'));
                if (starRating <= rating) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });

            // Update label
            $('#ratingLabel').text(`You selected ${rating} star${rating > 1 ? 's' : ''}`);

            // Hide all forms
            $('#feedbackForm').addClass('hidden');
            $('#keywordSection').addClass('hidden');
            $('#aiReviewsSection').addClass('hidden');
            $('#feedbackSuccess').addClass('hidden');
            $('#aiLoading').addClass('hidden');

            // Reset Google Review button
            $('#googleReviewBtn').prop('disabled', true);
            selectedReviewIndex = -1;
            selectedReviewText = '';

            // Show appropriate form
            if (rating <= 3) {
                $('#feedbackForm').removeClass('hidden').addClass('fade-in');
                // Reset feedback form
                $('#feedbackMessage').val('');
                $('#feedbackName').val('');
                $('#feedbackPhone').val('');
                $('#submitFeedbackBtn').prop('disabled', true);
            } else {
                // If keyword selection is enabled, show keyword section first
                if (enableKeywordSelection) {
                    $('#keywordSection').removeClass('hidden').addClass('fade-in');
                    // Reset keyword selection
                    selectedKeywords = [];
                    $('.keyword-btn').removeClass('selected').css({
                        'background': '',
                        'color': '',
                        'boxShadow': ''
                    });
                    updateKeywordCount();
                    $('#generateBtn').prop('disabled', true);
                } else {
                    // If keyword selection is disabled, generate reviews directly
                    generateAIReviewsDirect();
                }
            }

            // Scroll to form
            setTimeout(() => {
                const form = rating <= 3 ? '#feedbackForm' : (enableKeywordSelection ? '#keywordSection' : '#aiReviewsSection');
                $(form)[0]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        }

        function generateAIReviewsDirect() {
            if (isGenerating || isExpired) return;
            isGenerating = true;
            
            $('#aiStatusText').text('Generating reviews...');
            $('#aiLoading').removeClass('hidden');
            $('#aiReviewsSection').removeClass('hidden').addClass('fade-in');
            
            $.ajax({
                url: '<?= url("/api/public/review?action=generate") ?>',
                method: 'POST',
                data: {
                    slug: cardSlug,
                    keywords: []
                },
                dataType: 'json',
                success: function(response) {
                    isGenerating = false;
                    $('#aiStatusText').text('AI-Powered Reviews Ready');
                    $('#aiLoading').addClass('hidden');
                    
                    if (response.success) {
                        aiReviews = response.reviews;
                        googleReviewUrl = response.google_review_url || googleReviewUrl;
                        displayReviews(aiReviews);
                        showToast('✨ Reviews generated successfully!');
                    } else {
                        showToast('Error: ' + (response.error || 'Failed to generate reviews'));
                        $('#aiReviewsSection').addClass('hidden');
                    }
                },
                error: function() {
                    isGenerating = false;
                    $('#aiStatusText').text('AI-Powered Reviews Ready');
                    $('#aiLoading').addClass('hidden');
                    showToast('An error occurred. Please try again.');
                    $('#aiReviewsSection').addClass('hidden');
                }
            });
        }

        function toggleKeyword(btn, keyword) {
            if (isExpired) return;
            
            const index = selectedKeywords.indexOf(keyword);
            if (index > -1) {
                selectedKeywords.splice(index, 1);
                $(btn).removeClass('selected').css({
                    'background': '',
                    'color': '',
                    'boxShadow': ''
                });
            } else {
                if (selectedKeywords.length >= maxKeywords) {
                    showToast(`⚠️ You can only select up to ${maxKeywords} keywords`);
                    return;
                }
                selectedKeywords.push(keyword);
                $(btn).addClass('selected').css({
                    'background': themeColors.primary,
                    'color': 'white',
                    'boxShadow': '0 4px 12px ' + themeColors.primary + '40'
                });
            }
            updateKeywordCount();
            $('#generateBtn').prop('disabled', selectedKeywords.length === 0);
        }

        function updateKeywordCount() {
            $('#selectedCount').text(selectedKeywords.length);
        }

        function generateAIReviews() {
            if (isGenerating || isExpired || selectedKeywords.length === 0) return;
            isGenerating = true;
            
            const btn = $('#generateBtn');
            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Generating...').prop('disabled', true);
            $('#aiStatusText').text('Generating reviews...');
            $('#aiLoading').removeClass('hidden');
            $('#aiReviewsSection').removeClass('hidden').addClass('fade-in');
            
            $.ajax({
                url: '<?= url("/api/public/review?action=generate") ?>',
                method: 'POST',
                data: {
                    slug: cardSlug,
                    keywords: selectedKeywords
                },
                dataType: 'json',
                success: function(response) {
                    isGenerating = false;
                    btn.html('<i class="fas fa-sparkles mr-2"></i> Regenerate Reviews').prop('disabled', false);
                    $('#aiStatusText').text('AI-Powered Reviews Ready');
                    $('#aiLoading').addClass('hidden');
                    
                    if (response.success) {
                        aiReviews = response.reviews;
                        googleReviewUrl = response.google_review_url || googleReviewUrl;
                        displayReviews(aiReviews);
                        showToast('✨ Reviews generated successfully!');
                    } else {
                        showToast('Error: ' + (response.error || 'Failed to generate reviews'));
                        $('#aiReviewsSection').addClass('hidden');
                    }
                },
                error: function() {
                    isGenerating = false;
                    btn.html('<i class="fas fa-sparkles mr-2"></i> Generate AI Reviews').prop('disabled', false);
                    $('#aiStatusText').text('AI-Powered Reviews Ready');
                    $('#aiLoading').addClass('hidden');
                    showToast('An error occurred. Please try again.');
                    $('#aiReviewsSection').addClass('hidden');
                }
            });
        }

        function displayReviews(reviews) {
            const container = $('#reviewsList');
            container.empty();
            selectedReviewIndex = -1;
            selectedReviewText = '';
            $('#googleReviewBtn').prop('disabled', true);
            
            // Show selected keywords if any
            if (selectedKeywords.length > 0) {
                $('#selectedKeywordsDisplay').removeClass('hidden');
                const tagsContainer = $('#selectedKeywordsTags');
                tagsContainer.empty();
                selectedKeywords.forEach(keyword => {
                    tagsContainer.append(`
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium text-white" style="background: ${themeColors.primary}">
                            ${keyword}
                        </span>
                    `);
                });
            } else {
                $('#selectedKeywordsDisplay').addClass('hidden');
            }
            
            if (reviews.length === 0) {
                container.append(`
                    <div class="text-center py-4 text-gray-500 text-sm">
                        No reviews generated. Please try again.
                    </div>
                `);
                return;
            }
            
            reviews.forEach((review, index) => {
                const escapedReview = review.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
                container.append(`
                    <div class="relative">
                        <button onclick="selectReview(${index})"
                            class="w-full text-left p-3.5 pr-12 rounded-2xl border-2 transition-all duration-200 border-gray-200 hover:border-gray-300 bg-gray-50/50"
                            id="reviewBtn${index}">
                            <div class="flex items-start gap-3">
                                <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 bg-gray-200 text-gray-500" id="reviewBadge${index}">
                                    <span class="text-[10px] font-bold">${index + 1}</span>
                                </div>
                                <p class="text-sm text-gray-700 leading-relaxed">
                                    &ldquo;${review}&rdquo;
                                </p>
                            </div>
                        </button>
                        <button onclick="copyReviewText('${escapedReview}', ${index})"
                            class="absolute right-2 top-2 p-2 rounded-lg bg-white border flex items-center justify-center gap-2 border-gray-200 hover:bg-gray-50 transition-colors"
                            title="Copy review">
                            <i class="fas fa-copy text-gray-500" id="copyIcon${index}"></i>
                            <p class="text-xs text-gray-500">Copy Review</p>
                        </button>
                    </div>
                `);
            });
            
            if (enableKeywordSelection) {
                $('#keywordSection').addClass('hidden');
            }
            $('#aiReviewsSection').removeClass('hidden').addClass('fade-in');
            
            setTimeout(() => {
                $('#aiReviewsSection')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        }

        function selectReview(index) {
            if (isExpired) return;
            
            selectedReviewIndex = index;
            selectedReviewText = aiReviews[index];
            $('#googleReviewBtn').prop('disabled', false);
            
            // Update visual
            $('[id^="reviewBtn"]').each(function(i) {
                const btn = $(this);
                const badge = $(`#reviewBadge${i}`);
                if (i === index) {
                    btn.css({
                        'borderColor': themeColors.primary,
                        'backgroundColor': themeColors.primary + '08'
                    });
                    badge.css({
                        'backgroundColor': themeColors.primary,
                        'color': 'white'
                    });
                } else {
                    btn.css({
                        'borderColor': '#e5e7eb',
                        'backgroundColor': '#f9fafb'
                    });
                    badge.css({
                        'backgroundColor': '#e5e7eb',
                        'color': '#6b7280'
                    });
                }
            });
            
            // Update Google review button text
            $('#googleReviewBtn').html('<i class="fab fa-google mr-3 text-xl"></i> Review us on Google');
        }

        function copyReviewText(text, index) {
            if (isExpired) {
                showToast('⚠️ Membership expired. Please renew to copy reviews.');
                return;
            }
            
            // Copy to clipboard
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast('✅ Review copied to clipboard!');
                    $(`#copyIcon${index}`).removeClass('fa-copy').addClass('fa-check-circle text-green-600');
                    // Automatically select this review
                    selectReview(index);
                    // Save review
                    saveReview(text);
                }).catch(() => {
                    fallbackCopy(text, index);
                });
            } else {
                fallbackCopy(text, index);
            }
        }

        function fallbackCopy(text, index) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('✅ Review copied to clipboard!');
            $(`#copyIcon${index}`).removeClass('fa-copy').addClass('fa-check-circle text-green-600');
            selectReview(index);
            saveReview(text);
        }

        function saveReview(reviewText) {
            const name = $('#reviewerName').val().trim() || 'Anonymous';
            
            $.ajax({
                url: '<?= url("/api/public/review?action=submit") ?>',
                method: 'POST',
                data: {
                    slug: cardSlug,
                    rating: 5,
                    customer_name: name,
                    review_body: reviewText
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        console.log('Review saved successfully');
                    }
                },
                error: function() {
                    console.log('Failed to save review');
                }
            });
        }

        function reviewOnGoogle() {
            if (isExpired || !selectedReviewText) {
                showToast('⚠️ Please select a review first.');
                return;
            }
            
            // Show loading state
            const btn = $('#googleReviewBtn');
            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Redirecting...').prop('disabled', true);
            
            // Copy the review first
            if (navigator.clipboard) {
                navigator.clipboard.writeText(selectedReviewText).then(() => {
                    showToast('✅ Review copied! Redirecting to Google...');
                    setTimeout(() => {
                        window.open(googleReviewUrl, '_blank');
                        btn.html('<i class="fab fa-google mr-3 text-xl"></i> Review us on Google').prop('disabled', false);
                    }, 1500);
                }).catch(() => {
                    // Fallback copy
                    const textarea = document.createElement('textarea');
                    textarea.value = selectedReviewText;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    showToast('✅ Review copied! Redirecting to Google...');
                    setTimeout(() => {
                        window.open(googleReviewUrl, '_blank');
                        btn.html('<i class="fab fa-google mr-3 text-xl"></i> Review us on Google').prop('disabled', false);
                    }, 1500);
                });
            } else {
                // Fallback for non-clipboard browsers
                const textarea = document.createElement('textarea');
                textarea.value = selectedReviewText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showToast('✅ Review copied! Redirecting to Google...');
                setTimeout(() => {
                    window.open(googleReviewUrl, '_blank');
                    btn.html('<i class="fab fa-google mr-3 text-xl"></i> Review us on Google').prop('disabled', false);
                }, 1500);
            }
        }

        function submitFeedback() {
            if (isExpired) {
                showToast('⚠️ Membership expired. Please renew to submit feedback.');
                return;
            }
            
            const message = $('#feedbackMessage').val().trim();
            if (!message) {
                showToast('Please tell us about your experience');
                $('#feedbackMessage').focus();
                return;
            }
            
            const btn = $('#submitFeedbackBtn');
            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...').prop('disabled', true);
            
            const name = $('#feedbackName').val().trim() || 'Anonymous';
            const phone = $('#feedbackPhone').val().trim();
            
            $.ajax({
                url: '<?= url("/api/public/review?action=feedback") ?>',
                method: 'POST',
                data: {
                    slug: cardSlug,
                    rating: selectedRating,
                    customer_name: name,
                    phone: phone,
                    message: message
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#feedbackForm').addClass('hidden');
                        $('#feedbackSuccess').removeClass('hidden').addClass('fade-in');
                        showToast('✅ Feedback submitted successfully!');
                    } else {
                        showToast('Error: ' + (response.error || 'Unknown error'));
                        btn.html('<i class="fas fa-paper-plane mr-2"></i> Submit Feedback').prop('disabled', false);
                    }
                },
                error: function() {
                    showToast('An error occurred. Please try again.');
                    btn.html('<i class="fas fa-paper-plane mr-2"></i> Submit Feedback').prop('disabled', false);
                }
            });
        }

        function showToast(message) {
            $('#toastMessage').text(message);
            $('#toast').removeClass('hidden').fadeIn(300);
            setTimeout(() => {
                $('#toast').fadeOut(300);
            }, 3000);
        }

        // Keyboard support
        $(document).keydown(function(e) {
            if (e.key >= '1' && e.key <= '5' && !isExpired) {
                selectRating(parseInt(e.key));
            }
        });

        // Auto-validate feedback form on input
        $(document).on('input', '#feedbackMessage', function() {
            validateFeedbackForm();
        });
    </script>
</body>
</html>
<?php
$content = ob_get_clean();
echo $content;
?>