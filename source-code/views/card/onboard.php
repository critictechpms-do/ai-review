<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <title>Create Review Card | <?= env("APP_NAME", "App") ?></title>
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl border border-gray-100">

        <!-- Header -->
        <div class="mb-8 text-center">
            <div class="flex justify-center mb-4">
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                Create Your Review Card
            </h1>
            <p class="text-gray-500 mt-2">Start collecting AI-powered reviews in minutes.</p>
        </div>

        <!-- Error Message Box -->
        <?php if (isset($_SESSION['onboard_error'])): ?>
            <div class="mb-6 flex items-center p-4 text-sm text-red-800 border-t-4 border-red-300 bg-red-50 rounded-lg" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                </svg>
                <div>
                    <?= htmlspecialchars($_SESSION['onboard_error']) ?>
                </div>
            </div>
            <?php unset($_SESSION['onboard_error']); ?>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if (isset($_SESSION['onboard_success'])): ?>
            <div class="mb-6 flex items-center p-4 text-sm text-green-800 border-t-4 border-green-300 bg-green-50 rounded-lg" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                </svg>
                <div>
                    <?= htmlspecialchars($_SESSION['onboard_success']) ?>
                </div>
            </div>
            <?php unset($_SESSION['onboard_success']); ?>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?= url("/api/card?action=create") ?>" method="POST" id="onboardForm" class="space-y-5">
            
            <!-- Business Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Business Name</label>
                <input type="text" id="name" name="name" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                    placeholder="e.g., Food Bites"
                    maxlength="255">
            </div>

            <!-- Slug (Hidden) -->
            <input type="hidden" id="slug" name="slug" value="">

            <!-- Slug Preview -->
            <div id="slugPreview" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Your Review Page URL</label>
                <div class="flex items-center bg-gray-50 px-4 py-3 rounded-xl border border-gray-200">
                    <span class="text-gray-500 text-sm"><?= env('APP_URL') ?>/review/</span>
                    <span id="slugText" class="text-blue-600 font-medium text-sm"></span>
                </div>
            </div>

            <!-- Trial Info -->
            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">3-Day Free Trial</p>
                        <p class="text-xs text-blue-600">No credit card required. Upgrade anytime.</p>
                    </div>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                id="submitBtn" disabled>
                Create Review Card
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-8 text-center border-t border-gray-100 pt-6">
            <p class="text-sm text-gray-600">
                <a href="<?= url("/dashboard") ?>" class="text-blue-600 font-bold hover:underline">Back to Dashboard</a>
            </p>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#name').on('input', function() {
                let name = $(this).val().trim();
                
                if (name.length > 0) {
                    // Generate slug
                    let slug = name
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')  // Remove special characters
                        .replace(/\s+/g, '-')          // Replace spaces with hyphens
                        .replace(/-+/g, '-')           // Replace multiple hyphens with single
                        .replace(/^-|-$/g, '');        // Remove leading/trailing hyphens
                    
                    $('#slug').val(slug);
                    $('#slugText').text(slug);
                    $('#slugPreview').removeClass('hidden');
                    $('#submitBtn').prop('disabled', false);
                } else {
                    $('#slug').val('');
                    $('#slugPreview').addClass('hidden');
                    $('#submitBtn').prop('disabled', true);
                }
            });
        });
    </script>

</body>

</html>