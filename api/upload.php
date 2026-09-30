<?php
// ═══════════════════════════════════════════════════════════════
//  api/upload.php — Studio/site image uploads → storage/uploads/
//  POST multipart field "image". Returns {success, url} where url is
//  served via /upload.php?u=<hash> (storage/ is blocked by .htaccess,
//  same pattern as avatar.php). Max 8MB, real-image validated.
// ═══════════════════════════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once dirname(__DIR__) . '/config.php';

$file = $_FILES['image'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Please choose an image file.']);
    exit;
}
if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Image must be under 8MB.']);
    exit;
}
$info = @getimagesize($file['tmp_name']);
$map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
if (!$info || !isset($map[$info['mime']])) {
    echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, GIF or WebP images allowed.']);
    exit;
}

$dir = (defined('STORAGE_DIR') ? STORAGE_DIR : dirname(__DIR__) . '/storage') . '/uploads';
if (!is_dir($dir)) @mkdir($dir, 0755, true);

try {
    $hash = bin2hex(random_bytes(16));
} catch (Throwable $e) {
    $hash = md5(uniqid((string)mt_rand(), true));
}
$dest = $dir . '/' . $hash . '.' . $map[$info['mime']];
if (!@move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'error' => 'Could not save image.']);
    exit;
}

// Prune oldest beyond newest 300 (uploads folder hygiene)
try {
    $all = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];
    if (count($all) > 300) {
        usort($all, fn($a, $b) => filemtime($a) - filemtime($b));
        foreach (array_slice($all, 0, count($all) - 300) as $old) @unlink($old);
    }
} catch (Throwable $e) {}

$base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
echo json_encode([
    'success' => true,
    'url'     => $base . '/upload.php?u=' . $hash,
    'name'    => preg_replace('/[^a-zA-Z0-9._-]/', '_', (string)($file['name'] ?? 'image')),
    'mime'    => $info['mime'],
    'bytes'   => filesize($dest)
]);
