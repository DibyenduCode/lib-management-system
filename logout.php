<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();
set_flash_message('info', 'You have been logged out successfully.');
header("Location: " . BASE_URL . "login.php");
exit();
