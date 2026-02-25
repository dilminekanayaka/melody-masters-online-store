<?php
include_once __DIR__ . '/../includes/init.php';
include_once __DIR__ . '/../includes/db.php';

// Allowed roles for the admin panel
$allowed_roles = ['admin', 'superadmin', 'staff'];

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: /melody-masters-online-store/login.php");
    exit;
}

// Convenience role helpers available on every admin page
define('IS_SUPERADMIN', ($_SESSION['role'] === 'superadmin'));
define('IS_ADMIN',      ($_SESSION['role'] === 'admin'));
define('IS_STAFF',      ($_SESSION['role'] === 'staff'));

// Higher-privilege check (admin or superadmin — NOT staff)
define('IS_MANAGER',    (IS_SUPERADMIN || IS_ADMIN));

/**
 * Call at the top of any page that staff should NOT access.
 * Redirects staff to the dashboard with an access-denied flash.
 */
function require_manager(): void {
    if (IS_STAFF) {
        header("Location: /melody-masters-online-store/admin/dashboard.php?access_denied=1");
        exit;
    }
}

/**
 * Call on actions (e.g. delete) that staff cannot perform.
 * Returns false for staff so callers can skip the action.
 */
function can_manage(): bool {
    return IS_MANAGER;
}