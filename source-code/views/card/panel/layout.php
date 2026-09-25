<?php

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

global $conn;
$slug = $_GET['route_params']['slug'] ?? '';

// Get card details and verify ownership
$stmt = $conn->prepare("SELECT * FROM review_cards WHERE slug = ? AND user_id = ?");
$stmt->execute([$slug, $_SESSION['user_id']]);
$card = $stmt->fetch();

if (!$card) {
    header('Location: ' . url('/dashboard'));
    exit;
}

// Set current page for active menu highlighting
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
    <title><?= htmlspecialchars($card['name']) ?> - Panel | <?= env("APP_NAME", "App") ?></title>
    <style>
        .sidebar-open { overflow: hidden; }
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.active { transform: translateX(0); }
            .sidebar-overlay { display: none; }
            .sidebar-overlay.active { display: block; }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <!-- Mobile Menu Toggle -->
                    <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700 mr-3">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <a href="<?= url('/dashboard') ?>" class="text-gray-400 hover:text-gray-600 mr-2 sm:mr-4">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-base sm:text-xl font-bold text-gray-900 truncate max-w-[150px] sm:max-w-[300px]">
                            <?= htmlspecialchars($card['name']) ?>
                        </h1>
                    </div>
                    <span class="hidden sm:inline ml-3 px-2 py-1 text-xs font-medium rounded-full 
                        <?= $card['membership'] === 'trial' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                        <?= ucfirst($card['membership']) ?>
                    </span>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <a href="<?= url('/review/' . $card['slug']) ?>" target="_blank"
                        class="text-xs sm:text-sm text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-external-link-alt mr-1"></i>
                        <span class="hidden sm:inline">View Page</span>
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
            <aside id="sidebar" class="sidebar fixed lg:static inset-y-0 left-0 w-64 bg-white lg:bg-transparent z-40 lg:z-auto shadow-xl lg:shadow-none flex-shrink-0 overflow-y-auto lg:overflow-visible">
                <div class="p-4 lg:p-0">
                    <!-- Mobile Sidebar Header -->
                    <div class="flex items-center justify-between mb-4 lg:hidden">
                        <h3 class="font-bold text-gray-900">Menu</h3>
                        <button onclick="toggleSidebar()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <nav class="space-y-1">
                        <a href="<?= url('/edit/' . $card['slug']) ?>"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'home' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-home w-5 h-5 mr-3"></i>
                            Home
                        </a>
                        <a href="<?= url('/edit/' . $card['slug'] . '/membership') ?>"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'membership' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-crown w-5 h-5 mr-3"></i>
                            Membership
                        </a>
                        <a href="<?= url('/edit/' . $card['slug'] . '/reviews?filter=reviews') ?>"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'reviews' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-star w-5 h-5 mr-3"></i>
                            Reviews
                        </a>
                        <a href="<?= url('/edit/' . $card['slug'] . '/settings') ?>"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'settings' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-cog w-5 h-5 mr-3"></i>
                            Settings
                        </a>
                    </nav>
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