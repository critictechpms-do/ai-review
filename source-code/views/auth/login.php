<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= env("APP_NAME", "App") ?></title>
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl border border-gray-100">

        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                Welcome Back
            </h1>
            <p class="text-gray-500 mt-2">Sign in to manage your digital menu.</p>
        </div>



        <!-- Form -->
        <form action="<?= url("/api/auth?action=login") ?>" method="POST" class="space-y-5">

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                    placeholder="name@company.com">
            </div>

            <!-- Password -->
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
                    <a href="<?= url("/forgot-password") ?>" class="text-xs text-blue-600 hover:underline">Forgot?</a>
                </div>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                    placeholder="••••••••">
            </div>

            <!-- Error Message Box -->
            <?php if (isset($_SESSION['auth_error'])): ?>
                <div class="mb-6 flex items-center p-4 text-sm text-red-800 border-t-4 border-red-300 bg-red-50 rounded-lg" role="alert">
                    <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                    </svg>
                    <div>
                        <?= htmlspecialchars($_SESSION['auth_error']) ?>
                    </div>
                </div>
                <?php unset($_SESSION['auth_error']); ?>
            <?php endif; ?>

            <!-- Submit Button -->
            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-all transform active:scale-95">
                Sign In
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-8 text-center border-t border-gray-100 pt-6">
            <p class="text-sm text-gray-600">
                New here?
                <a href="<?= url("/signup") ?>" class="text-blue-600 font-bold hover:underline">Create an account</a>
            </p>
        </div>
    </div>

</body>

</html>