<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';

start_session();

$code  = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (APP_DEBUG) {
  error_log("OAuth Callback - Code: " . ($code ? 'present' : 'missing'));
  error_log("OAuth Callback - State from URL: $state");
  error_log("OAuth Callback - State from Session: " . ($_SESSION['oauth_state'] ?? 'missing'));
}

if (!$code || !$state || empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
  unset($_SESSION['oauth_state']);
  set_flash('error', 'Google authentication failed or was cancelled.');
  header('Location: ' . BASE_URL . '/auth/login.php');
  exit;
}
unset($_SESSION['oauth_state']);

// Exchange code for tokens
$tokenEndpoint = 'https://oauth2.googleapis.com/token';

$post = [
  'code' => $code,
  'client_id' => GOOGLE_CLIENT_ID,
  'client_secret' => GOOGLE_CLIENT_SECRET,
  'redirect_uri' => GOOGLE_REDIRECT_URI,
  'grant_type' => 'authorization_code'
];

if (APP_DEBUG) {
  error_log("Google OAuth Request: " . print_r($post, true));
}

$ch = curl_init($tokenEndpoint);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query($post),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_SSL_VERIFYPEER => false // For local development only
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if (APP_DEBUG) {
  error_log("Google OAuth Response (HTTP $httpCode): $resp");
  if ($curlError) {
    error_log("cURL Error: $curlError");
  }
}

$data = json_decode($resp, true);
$idToken = $data['id_token'] ?? null;

if (!$idToken) {
  http_response_code(400);
  echo "<h3>Google Authentication Failed</h3>";
  echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
  if ($curlError) {
    echo "<p><strong>cURL Error:</strong> " . htmlspecialchars($curlError) . "</p>";
  }
  if (isset($data['error'])) {
    echo "<p><strong>Error:</strong> " . htmlspecialchars($data['error']) . "</p>";
  }
  if (isset($data['error_description'])) {
    echo "<p><strong>Description:</strong> " . htmlspecialchars($data['error_description']) . "</p>";
  }
  if (APP_DEBUG) {
    echo "<hr><p><strong>Full Response:</strong></p><pre>" . htmlspecialchars($resp) . "</pre>";
    echo "<p><strong>Request Data:</strong></p><pre>" . htmlspecialchars(print_r($post, true)) . "</pre>";
  }
  exit;
}

// Validate token / read claims via tokeninfo (simple approach)
$tokenInfo = json_decode(file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken)), true);

$sub   = $tokenInfo['sub'] ?? null;
$email = $tokenInfo['email'] ?? null;
$given = $tokenInfo['given_name'] ?? '';
$family= $tokenInfo['family_name'] ?? '';

if (!$sub || !$email) {
  http_response_code(400);
  echo "Google token missing data.";
  exit;
}

// Find existing user by google_sub OR email
$stmt = db()->prepare("SELECT * FROM users WHERE google_sub = ? OR email = ? LIMIT 1");
$stmt->execute([$sub, $email]);
$u = $stmt->fetch();

if ($u) {
  // Link Google to existing account if not linked
  if (empty($u['google_sub'])) {
    db()->prepare("UPDATE users SET google_sub=?, provider='GOOGLE' WHERE id=?")->execute([$sub, (int)$u['id']]);
  }
  if ((int)$u['is_active'] !== 1) {
    set_flash('error', 'Your account is inactive. Please contact support.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
  }
  login_user($u);
  set_flash('success', 'Welcome back, ' . e($u['first_name']) . '!');
  header('Location: ' . BASE_URL . role_home($u['role']));
  exit;
} else {
  // New Google user: redirect to complete registration with role selection
  $_SESSION['google_temp'] = [
    'sub' => $sub,
    'email' => $email,
    'given_name' => $given,
    'family_name' => $family
  ];
  header('Location: ' . BASE_URL . '/auth/complete_registration.php');
  exit;
}
