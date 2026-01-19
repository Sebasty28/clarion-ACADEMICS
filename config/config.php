<?php
// config/config.php

// IMPORTANT: Update these if your DB differs
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'clarion_db');
// define('DB_USER', 'root');
// define('DB_PASS', ''); 

define('DB_HOST', getenv('DB_HOST') ?: 'switchback.proxy.rlwy.net');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 46176));
define('DB_NAME', getenv('DB_NAME') ?: 'clarion_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'mUgxbRFGdWMibqRxanhGfhpyUyMmRSGy');

//Google OAuth2 Credentials
// TODO: Replace with your actual Google OAuth credentials from https://console.cloud.google.com/
// 1. Create OAuth 2.0 Client ID
// 2. Add authorized redirect URI: http://localhost/clarion/auth/google_callback.php
define('GOOGLE_CLIENT_ID', '388224077371-g9fqu0tl31f0qqcoua40obu0h58rvpi0.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'http://localhost/clarion/auth/google_callback.php'); // change on production

define('APP_NAME', 'Clarion');
define('BASE_URL', '/clarion'); // if project folder is /htdocs/clarion

define('GMAIL_SMTP_USER', 'clarionlessonviewsystem@gmail.com');
define('GMAIL_SMTP_APP_PASS', 'APP_PASSWORD');

// Security
define('SESSION_NAME', 'clarion_session');

// Debug (set false in production)
define('APP_DEBUG', false);

date_default_timezone_set('Asia/Manila');

?>