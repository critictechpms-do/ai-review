<?php
global $conn;

// Get stats
$stmt = $conn->query("SELECT COUNT(*) as count FROM review_cards");
$totalCards = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM reviews");
$totalReviews = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT AVG(rating) as avg FROM reviews");
$avgRating = $stmt->fetch()['avg'] ?? 0;

// Get recent reviews
$stmt = $conn->query("
    SELECT r.*, rc.name as business_name 
    FROM reviews r 
    JOIN review_cards rc ON r.card_id = rc.id 
    ORDER BY r.created_at DESC 
    LIMIT 6
");
$recentReviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title><?= env('APP_NAME', 'ReviewAI') ?> - AI-Powered Google Review Collector</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-text { background: linear-gradient(135deg, #7c3aed, #6d28d9); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-gradient { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #4f46e5 100%); }
        .feature-card:hover { transform: translateY(-5px); }
        .review-card:hover { transform: translateY(-5px); }
        .pricing-card:hover { transform: translateY(-10px); }
        .star-rating { color: #f59e0b; }
        .bg-gradient-purple { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
    </style>
</head>
<body class="bg-white">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 bg-white/90 backdrop-blur-md border-b border-gray-100 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="<?= url('/') ?>" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-star text-white text-sm"></i>
                    </div>
                    <span class="text-xl font-extrabold text-gray-900"><?= env('APP_NAME', 'ReviewAI') ?></span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="<?= url('/login') ?>" class="text-sm font-medium text-gray-700 hover:text-purple-600 transition-colors">Login</a>
                    <a href="<?= url('/signup') ?>" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-sm font-medium rounded-full hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg shadow-purple-200">
                        Get Started Free
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-24 pb-16 sm:pt-32 sm:pb-24 hero-gradient relative overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="max-w-3xl mx-auto">
                <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm mb-6">
                    <i class="fas fa-sparkles mr-2"></i>
                    AI-Powered Review Collection
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white mb-6 leading-tight">
                    Collect More <span class="text-yellow-300">Google Reviews</span> with AI
                </h1>
                <p class="text-lg sm:text-xl text-white/90 mb-8 leading-relaxed">
                    Automatically generate AI-powered review responses, collect feedback, and boost your 
                    Google rating. Perfect for restaurants, hotels, and service businesses.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="<?= url('/signup') ?>" 
                       class="w-full sm:w-auto px-8 py-4 bg-white text-purple-600 font-bold rounded-full hover:bg-gray-100 transition-all shadow-xl text-lg">
                        Start Free Trial
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                    <a href="#how-it-works" 
                       class="w-full sm:w-auto px-8 py-4 bg-white/20 backdrop-blur-sm text-white font-bold rounded-full hover:bg-white/30 transition-all border border-white/30 text-lg">
                        See How It Works
                    </a>
                </div>
                
                <!-- Trust Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-16 max-w-3xl mx-auto">
                    <div>
                        <p class="text-3xl sm:text-4xl font-extrabold text-white"><?= number_format($totalUsers) ?>+</p>
                        <p class="text-sm text-white/70 mt-1">Happy Users</p>
                    </div>
                    <div>
                        <p class="text-3xl sm:text-4xl font-extrabold text-white"><?= number_format($totalCards) ?>+</p>
                        <p class="text-sm text-white/70 mt-1">Businesses</p>
                    </div>
                    <div>
                        <p class="text-3xl sm:text-4xl font-extrabold text-white"><?= number_format($totalReviews) ?>+</p>
                        <p class="text-sm text-white/70 mt-1">Reviews Collected</p>
                    </div>
                    <div>
                        <p class="text-3xl sm:text-4xl font-extrabold text-white"><?= number_format($avgRating, 1) ?>⭐</p>
                        <p class="text-sm text-white/70 mt-1">Average Rating</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4">
                    Why Choose <span class="gradient-text">ReviewAI</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Everything you need to collect more reviews and improve your online reputation
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white rounded-2xl shadow-lg border border-gray-100 p-6 transition-all duration-300">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-robot text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">AI-Powered Reviews</h3>
                    <p class="text-gray-600 text-sm">Generate personalized review responses using AI. Save time and get more 5-star reviews.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-white rounded-2xl shadow-lg border border-gray-100 p-6 transition-all duration-300">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-qrcode text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Smart QR Codes</h3>
                    <p class="text-gray-600 text-sm">Generate unique QR codes for your business. Customers scan and leave reviews instantly.</p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-white rounded-2xl shadow-lg border border-gray-100 p-6 transition-all duration-300">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-star text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Smart Rating System</h3>
                    <p class="text-gray-600 text-sm">Collect feedback with a simple star rating. Low ratings get a feedback form, high ratings get AI review.</p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-white rounded-2xl shadow-lg border border-gray-100 p-6 transition-all duration-300">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-whatsapp text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">WhatsApp Integration</h3>
                    <p class="text-gray-600 text-sm">Connect your WhatsApp number and let customers contact you directly from the review page.</p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-white rounded-2xl shadow-lg border border-gray-100 p-6 transition-all duration-300">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Real-time Analytics</h3>
                    <p class="text-gray-600 text-sm">Track your reviews, ratings, and feedback in real-time. Make data-driven decisions.</p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-white rounded-2xl shadow-lg border border-gray-100 p-6 transition-all duration-300">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-copy text-indigo-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">One-Click Copy</h3>
                    <p class="text-gray-600 text-sm">Generated reviews can be copied with one click. Post directly to Google Reviews.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-16 sm:py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4">How It Works</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Get more reviews in 3 simple steps</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                <div class="text-center">
                    <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-extrabold gradient-text">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Create Your Card</h3>
                    <p class="text-gray-600 text-sm">Sign up and create your business review card with your logo and details</p>
                </div>
                
                <div class="text-center">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-extrabold gradient-text">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Share with Customers</h3>
                    <p class="text-gray-600 text-sm">Print the QR code or share the link with your customers</p>
                </div>
                
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-extrabold gradient-text">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Collect Reviews</h3>
                    <p class="text-gray-600 text-sm">Customers rate, AI generates reviews, and you get more Google reviews</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Reviews -->
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4">
                    Recent <span class="gradient-text">Reviews</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    See what businesses are saying about their experience
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (count($recentReviews) > 0): ?>
                    <?php foreach ($recentReviews as $review): ?>
                        <div class="review-card bg-white rounded-2xl shadow-lg border border-gray-100 p-6 transition-all duration-300">
                            <div class="flex items-center mb-3">
                                <div class="flex star-rating">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <i class="fas fa-star <?= $i < $review['rating'] ? 'text-yellow-400' : 'text-gray-200' ?> text-sm"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="ml-2 text-xs text-gray-400"><?= $review['rating'] ?>★</span>
                            </div>
                            <p class="text-gray-700 text-sm mb-3 line-clamp-2">"<?= htmlspecialchars($review['body'] ?? 'Great experience!') ?>"</p>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($review['customer_name'] ?? 'Anonymous') ?></p>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($review['business_name']) ?></p>
                                </div>
                                <span class="text-xs text-gray-400"><?= date('M d', strtotime($review['created_at'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-3 text-center py-12">
                        <p class="text-gray-500">No reviews yet. Be the first to collect reviews!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 sm:py-24 hero-gradient relative overflow-hidden">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">
                Ready to Get More Reviews?
            </h2>
            <p class="text-lg text-white/90 mb-8 max-w-2xl mx-auto">
                Join thousands of businesses already using <?= env('APP_NAME', 'ReviewAI') ?> to collect more reviews and grow their reputation.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="<?= url('/signup') ?>" 
                   class="w-full sm:w-auto px-8 py-4 bg-white text-purple-600 font-bold rounded-full hover:bg-gray-100 transition-all shadow-xl text-lg">
                    Start Free Trial
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
                <a href="<?= url('/login') ?>" 
                   class="w-full sm:w-auto px-8 py-4 bg-white/20 backdrop-blur-sm text-white font-bold rounded-full hover:bg-white/30 transition-all border border-white/30 text-lg">
                    Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-star text-white text-sm"></i>
                        </div>
                        <span class="text-xl font-extrabold"><?= env('APP_NAME', 'ReviewAI') ?></span>
                    </div>
                    <p class="text-gray-400 text-sm max-w-md">
                        AI-powered review collection platform that helps businesses get more Google reviews and improve their online reputation.
                    </p>
                </div>
                
                <div>
                    <h4 class="font-bold text-sm uppercase text-gray-500 mb-4">Product</h4>
                    <ul class="space-y-2">
                        <li><a href="#how-it-works" class="text-gray-400 hover:text-white text-sm transition-colors">How It Works</a></li>
                        <li><a href="<?= url('/signup') ?>" class="text-gray-400 hover:text-white text-sm transition-colors">Sign Up</a></li>
                        <li><a href="<?= url('/login') ?>" class="text-gray-400 hover:text-white text-sm transition-colors">Login</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-sm uppercase text-gray-500 mb-4">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 text-center">
                <p class="text-gray-500 text-sm">&copy; <?= date('Y') ?> <?= env('APP_NAME', 'ReviewAI') ?>. All rights reserved. Made with ❤️ for businesses.</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.hash);
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 800);
            }
        });

        // Add shadow to nav on scroll
        $(window).scroll(function() {
            if ($(window).scrollTop() > 0) {
                $('nav').addClass('shadow-lg');
            } else {
                $('nav').removeClass('shadow-lg');
            }
        });
    </script>
</body>
</html>