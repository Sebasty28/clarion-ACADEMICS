<?php
require_once __DIR__ . '/../core/auth.php';
logout_user();
set_flash('success', 'You have been logged out.');
redirect('/auth/login.php');
