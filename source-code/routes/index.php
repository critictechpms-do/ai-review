<?php

Router::get('/', 'views/main/home.php');
Router::get('/login', 'views/auth/login.php');
Router::get('/signup', 'views/auth/signup.php');
Router::get('/forgot-password', 'views/auth/forget.php');

// card Onboarding & Dashboard
Router::get('/onboard', 'views/card/onboard.php');
Router::get('/dashboard', 'views/card/list.php');

// Pricing Page
Router::get('/pricing', 'views/site/pricing.php');

// Public Menu Pages - Order matters! More specific routes first

// Restaurant Panel
Router::get('/edit/{slug}', 'views/card/panel/home.php');
Router::get('/edit/{slug}/reviews', 'views/card/panel/review.php');
Router::get('/edit/{slug}/settings', 'views/card/panel/settings.php');
Router::get('/edit/{slug}/membership', 'views/card/panel/membership.php');

// Owner Panel Routes
Router::get('/owner', 'views/owner/home.php');
Router::get('/owner/users', 'views/owner/users.php');
Router::get('/owner/cards', 'views/owner/cards.php');
Router::get('/owner/plans', 'views/owner/plans.php');
Router::get('/owner/settings', 'views/owner/settings.php');





Router::get('/review/{slug}', 'views/card/public.php');
Router::get('/review/{slug}/cart', 'views/card/cart.php');


Router::get('/{slug}', 'views/card/public.php');
Router::get('/{slug}/cart', 'views/card/cart.php');

// APIs// Auth routes
Router::post('/api/resetPassword', 'routes/api/auth.php');
Router::post('/api/auth', 'routes/api/auth.php');
Router::get('/api/auth', 'routes/api/auth.php');

Router::post('/api/card', 'routes/api/card.php');
Router::post('/api/review', 'routes/api/review.php');
Router::post('/api/public/review', 'routes/api/review.php');
Router::post('/api/feedback', 'routes/api/feedback.php');
Router::post('/api/public/feedback', 'routes/api/feedback.php');
Router::get('/api/review', 'routes/api/review.php');
Router::get('/api/public/review', 'routes/api/review.php');
Router::get('/api/feedback', 'routes/api/feedback.php');
Router::get('/api/public/feedback', 'routes/api/feedback.php');

// Owner API Routes
Router::get('/api/owner', 'routes/api/owner/plans.php');
Router::post('/api/owner/plans', 'routes/api/owner/plans.php');
Router::post('/api/owner/cards', 'routes/api/owner/cards.php');
Router::post('/api/owner/settings', 'routes/api/owner/settings.php');
// Dispatch
Router::dispatch();