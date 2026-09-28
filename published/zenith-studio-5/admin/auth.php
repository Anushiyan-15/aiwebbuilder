<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAdmin(): void {
    if (empty($_SESSION['admin_auth'])) {
        $redirect = 'login.php';
        header("Location: $redirect");
        exit;
    }
}

function getAuthData(): array {
    $file = __DIR__ . '/.auth.json';
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

/**
 * SECTION 18.5 — VISIBILITY & DISPLAY RULE GUARD
 * Evaluates whether Requirement Builder features may be rendered.
 */
function canShowRequirementBuilder(string $role, string $mode, $recordId): bool {
    return ($role === 'admin' && $mode === 'edit' && !empty($recordId));
}