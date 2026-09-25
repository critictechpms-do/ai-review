<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Reset Password | <?= env("APP_NAME", "App") ?></title>
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl border border-gray-100">

        <!-- Header -->
        <div class="mb-8 text-center">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                Reset Password
            </h1>
            <p class="text-gray-500 mt-2">Enter your email and new password to reset.</p>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['auth_success'])): ?>
            <div class="mb-6 flex items-center p-4 text-sm text-green-800 border-t-4 border-green-300 bg-green-50 rounded-lg" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div><?= htmlspecialchars($_SESSION['auth_success']) ?></div>
            </div>
            <?php unset($_SESSION['auth_success']); ?>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($_SESSION['auth_error'])): ?>
            <div class="mb-6 flex items-center p-4 text-sm text-red-800 border-t-4 border-red-300 bg-red-50 rounded-lg" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div><?= htmlspecialchars($_SESSION['auth_error']) ?></div>
            </div>
            <?php unset($_SESSION['auth_error']); ?>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?= url("/api/auth?action=resetPassword") ?>" method="POST" class="space-y-5" onsubmit="return validateForm()">
            
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                    placeholder="name@company.com">
            </div>

            <!-- New Password -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                    placeholder="••••••••">
                <p class="text-xs text-gray-400 mt-2">At least 8 characters required.</p>
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                    placeholder="••••••••">
                <p id="passwordError" class="text-xs text-red-500 mt-1 hidden">Passwords do not match.</p>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-all transform active:scale-95">
                Reset Password
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-8 text-center border-t border-gray-100 pt-6">
            <p class="text-sm text-gray-600">
                Remember your password?
                <a href="<?= url("/login") ?>" class="text-blue-600 font-bold hover:underline">Sign in</a>
            </p>
        </div>
    </div>

    <script>
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const errorElement = document.getElementById('passwordError');
            
            if (password !== confirmPassword) {
                errorElement.classList.remove('hidden');
                return false;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters.');
                return false;
            }
            
            errorElement.classList.add('hidden');
            return true;
        }
        
        // Real-time password match check
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const errorElement = document.getElementById('passwordError');
            
            if (confirmPassword && password !== confirmPassword) {
                errorElement.classList.remove('hidden');
            } else {
                errorElement.classList.add('hidden');
            }
        });
    </script>

</body>

</html>