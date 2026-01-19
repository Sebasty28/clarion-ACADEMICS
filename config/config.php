<?php
// config/config.php

// IMPORTANT: Update these if your DB differs
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'clarion_db');
// define('DB_USER', 'root');
// define('DB_PASS', ''); 

// Supabase (server-side only)
// putenv('SUPABASE_URL=https://vnfybjefldsjmwugvvgh.supabase.co');
// putenv('SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZuZnliamVmbGRzam13dWd2dmdoIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2ODc1MDkzNiwiZXhwIjoyMDg0MzI2OTM2fQ.wb7-uIdDwmjdLOHHnRZTy3t2anXl88cIT-0wBuHu9yQ');
// putenv('SUPABASE_PDF_BUCKET=pdf');
// putenv('SUPABASE_THUMB_BUCKET=thumbs');

// define('DB_HOST', getenv('DB_HOST') ?: 'switchback.proxy.rlwy.net');
// define('DB_PORT', (int)(getenv('DB_PORT') ?: 46176));
// define('DB_NAME', getenv('DB_NAME') ?: 'clarion_db');
// define('DB_USER', getenv('DB_USER') ?: 'root');
// define('DB_PASS', getenv('DB_PASS') ?: 'mUgxbRFGdWMibqRxanhGfhpyUyMmRSGy');

define('DB_HOST', getenv('DB_HOST') ?: 'switchback.proxy.rlwy.net');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 46176));
define('DB_NAME', getenv('DB_NAME') ?: 'clarion_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('SUPABASE_URL', getenv('https://vnfybjefldsjmwugvvgh.supabase.co') ?: '');
define('SUPABASE_SERVICE_ROLE_KEY', getenv('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZuZnliamVmbGRzam13dWd2dmdoIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2ODc1MDkzNiwiZXhwIjoyMDg0MzI2OTM2fQ.wb7-uIdDwmjdLOHHnRZTy3t2anXl88cIT-0wBuHu9yQ') ?: '');
define('SUPABASE_PDF_BUCKET', getenv('SUPABASE_PDF_BUCKET') ?: 'pdf');
define('SUPABASE_THUMB_BUCKET', getenv('SUPABASE_THUMB_BUCKET') ?: 'thumbs');

//Google OAuth2 Credentials
// TODO: Replace with your actual Google OAuth credentials from https://console.cloud.google.com/
// 1. Create OAuth 2.0 Client ID
// 2. Add authorized redirect URI: http://localhost/clarion/auth/google_callback.php
define('GOOGLE_CLIENT_ID', '388224077371-g9fqu0tl31f0qqcoua40obu0h58rvpi0.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
//define('GOOGLE_REDIRECT_URI', 'https://clarion-academics.onrender.com/auth/google_callback.php');// change on production
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: 'https://clarion-academics.onrender.com/auth/google_callback.php');

define('APP_NAME', 'Clarion');
define('BASE_URL', ''); // if project folder is /htdocs/clarion

define('GMAIL_SMTP_USER', 'clarionlessonviewsystem@gmail.com');
define('GMAIL_SMTP_APP_PASS', 'APP_PASSWORD');

// Security
define('SESSION_NAME', 'clarion_session');

// Debug (set false in production)
define('APP_DEBUG', false);

date_default_timezone_set('Asia/Manila');

?>