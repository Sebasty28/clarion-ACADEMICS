<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/security.php';

start_session();
$_SESSION['oauth_state'] = bin2hex(random_bytes(16));
$state = $_SESSION['oauth_state'];
session_write_close(); // Ensure session is saved before redirect

$params = [
  'client_id' => GOOGLE_CLIENT_ID,
  'redirect_uri' => GOOGLE_REDIRECT_URI,
  'response_type' => 'code',
  'scope' => 'openid email profile',
  'state' => $state,
  'access_type' => 'online',
  'prompt' => 'select_account'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
header("Location: $authUrl");
exit;
