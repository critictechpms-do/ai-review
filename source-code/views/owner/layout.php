<?php
if (!isOwner()) {
    header('Location: ' . url('/login'));
    exit;
}

global $conn;

// Get owner stats
$stmt = $conn->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM review_cards");
$totalCards = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM reviews");
$totalReviews = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM feedbacks");
$totalFeedbacks = $stmt->fetch()['count'];

$currentPage = $page ?? 'home';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Owner Panel | <?= env("APP_NAME", "App") ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .sidebar-open {
            overflow: hidden;
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: none;
            }

            .sidebar-overlay.active {
                display: block;
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-gray-900 text-white sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3 sm:space-x-8">
                    <!-- Mobile Menu Toggle -->
                    <button onclick="toggleSidebar()" class="lg:hidden text-gray-400 hover:text-white">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <a href="<?= url('/owner') ?>" class="text-lg sm:text-xl font-bold tracking-tight">
                        <span class="hidden sm:inline"><?= env("APP_NAME", "App") ?></span>
                        <span class="sm:hidden"><?= substr(env("APP_NAME", "App"), 0, 1) ?>P</span>
                        <span class="text-purple-500">Owner</span>
                    </a>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <a
                        href="https://wa.me/918455838503?text=I%20want%20more%20source%20code%20like%20this"
                        class="inline-flex items-center text-xs sm:text-sm text-gray-400 hover:text-white transition-colors"
                        target="_blank"
                        rel="noopener noreferrer">
                        <i class="fas fa-external-link-alt mr-1"></i>
                        <span class="hidden sm:inline">More Software like this</span>
                    </a>
                    <span class="text-gray-600 hidden sm:inline">|</span>
                    <span class="text-xs sm:text-sm text-gray-400 hidden sm:inline"><?= htmlspecialchars($_SESSION['user_email']) ?></span>
                    <a href="<?= url('/api/auth?action=logout') ?>" class="text-xs sm:text-sm text-red-400 hover:text-red-300 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar Overlay (Mobile) -->
    <div id="sidebarOverlay" class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden" onclick="toggleSidebar()"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 lg:py-8">
        <div class="flex gap-4 lg:gap-8">

            <!-- Sidebar -->
            <aside id="sidebar" class="sidebar fixed lg:static inset-y-0 left-0 w-64 z-40 lg:z-auto flex-shrink-0">
                <div class="bg-white rounded-none lg:rounded-2xl shadow-xl lg:shadow-sm border-0 lg:border border-gray-100 p-4 h-full lg:h-auto overflow-y-auto">
                    <!-- Mobile Sidebar Header -->
                    <div class="flex items-center justify-between mb-4 lg:hidden">
                        <h3 class="font-bold text-gray-900">Owner Menu</h3>
                        <button onclick="toggleSidebar()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Quick Stats (Mobile Only) -->
                    <div class="lg:hidden mb-4 p-3 bg-gray-50 rounded-xl">
                        <div class="grid grid-cols-2 gap-2 text-center">
                            <div>
                                <p class="text-lg font-bold text-gray-900"><?= number_format($totalUsers) ?></p>
                                <p class="text-xs text-gray-500">Users</p>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-gray-900"><?= number_format($totalCards) ?></p>
                                <p class="text-xs text-gray-500">Cards</p>
                            </div>
                        </div>
                    </div>

                    <nav class="space-y-1">
                        <a href="<?= url('/owner') ?>"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'home' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <i class="fas fa-home w-5 h-5 mr-3"></i>
                            Dashboard
                        </a>

                        <a href="<?= url('/owner/users') ?>"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'users' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <i class="fas fa-users w-5 h-5 mr-3"></i>
                            Users
                        </a>

                        <a href="<?= url('/owner/cards') ?>"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'cards' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <i class="fas fa-id-card w-5 h-5 mr-3"></i>
                            Review Cards
                        </a>

                        <a href="<?= url('/owner/plans') ?>"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'plans' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <i class="fas fa-crown w-5 h-5 mr-3"></i>
                            Plans
                        </a>

                        <a href="<?= url('/owner/settings') ?>"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'settings' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <i class="fas fa-cog w-5 h-5 mr-3"></i>
                            Settings
                        </a>
                    </nav>

                    <!-- Quick Stats (Desktop) -->
                    <div class="hidden lg:block mt-6 pt-6 border-t border-gray-100">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">Quick Stats</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Users</span>
                                <span class="text-sm font-bold text-gray-900"><?= number_format($totalUsers) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Review Cards</span>
                                <span class="text-sm font-bold text-gray-900"><?= number_format($totalCards) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 min-w-0">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            $('#sidebar').toggleClass('active');
            $('#sidebarOverlay').toggleClass('active');
            $('body').toggleClass('sidebar-open');
        }

        // Close sidebar when clicking a link on mobile
        $(document).ready(function() {
            $('#sidebar a').on('click', function() {
                if ($(window).width() < 1024) {
                    toggleSidebar();
                }
            });
        });

        // Close sidebar on window resize to desktop
        $(window).resize(function() {
            if ($(window).width() >= 1024) {
                $('#sidebar').removeClass('active');
                $('#sidebarOverlay').removeClass('active');
                $('body').removeClass('sidebar-open');
            }
        });
    </script>

</body>

</html>