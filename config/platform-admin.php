<?php
// ═══════════════════════════════════════════════════════════════
//  config/platform-admin.php — Platform Admin Credentials
//  ⚠️  CHANGE the password after first login!
// ═══════════════════════════════════════════════════════════════

// Platform admin username (can be changed here)
if (!defined('PLATFORM_ADMIN_USERNAME')) {
    define('PLATFORM_ADMIN_USERNAME', 'superadmin');
}

// Platform admin password hash
// To change password, run: echo password_hash('YourNewPassword', PASSWORD_DEFAULT);
// then paste the result below.
if (!defined('PLATFORM_ADMIN_PASSWORD_HASH')) {
    define('PLATFORM_ADMIN_PASSWORD_HASH', password_hash('WebCraft@2026!', PASSWORD_DEFAULT));
}

// Secret key for session tokens (change this!)
if (!defined('PLATFORM_ADMIN_SECRET')) {
    define('PLATFORM_ADMIN_SECRET', 'wc-adm-secret-2026-change-me');
}

// How many days before payment is "overdue"
if (!defined('SUBSCRIPTION_PERIOD_DAYS')) {
    define('SUBSCRIPTION_PERIOD_DAYS', 30);
}

// Days before due date to start showing "renewal due soon" warning
if (!defined('RENEWAL_WARNING_DAYS')) {
    define('RENEWAL_WARNING_DAYS', 5);
}

// Platform admin session name
if (!defined('PLATFORM_ADMIN_SESSION')) {
    define('PLATFORM_ADMIN_SESSION', 'wc_platform_admin');
}
