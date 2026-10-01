<?php
// ═══════════════════════════════════════════════════════════════
//  api/opencode.php — OpenCode Zen AI lane (generation + chat edit)
//
//  POST JSON {action, ...}:
//   - models      → live model list + free list + recommended + benchmark
//   - test        → tiny chat probe across free models
//   - analyze     → {data} → design brief (FlowCraft Skills 1-2)
//   - generate_one→ {data, variation, mode, model?, slot?} → ONE premium HTML
//   - edit        → {current_html, instruction, biz_name?, model?} → full HTML
//
//  On ANY AI failure: success=false + errors[] + fallback hint.
//  Frontend then falls back to server templates (never empty).
// ═══════════════════════════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once dirname(__DIR__) . '/includes/OpenCodeService.php';

// Free-lane AI generations are slow (chunked, up to ~5 min for 3 chunks).
// Lift PHP's execution cap so Apache doesn't kill the request mid-stream.
@set_time_limit(600);

$raw = file_get_contents('php://input');
$req = json_decode($raw, true) ?: [];
$action = $req['action'] ?? 'test';
$model = isset($req['model']) && is_string($req['model']) ? trim($req['model']) : null;
if ($model === '') $model = null;

if ($action === 'models') {
    $list = opencode_list_models();
    $benchFile = (defined('STORAGE_DIR') ? STORAGE_DIR : dirname(__DIR__) . '/storage') . '/opencode_benchmark.json';
    $bench = file_exists($benchFile) ? (json_decode(@file_get_contents($benchFile), true) ?: null) : null;
    $free = opencode_free_models();
    $recommended = ($bench['recommended'] ?? null);
    if (!in_array($recommended, $free, true)) $recommended = $free[0] ?? null;
    echo json_encode([
        'success' => true,
        'configured' => opencode_is_configured(),
        'default_model' => defined('OPENCODE_DEFAULT_MODEL') ? OPENCODE_DEFAULT_MODEL : 'space-bunny-free',
        'free' => $free,
        'recommended' => $recommended,
        'benchmark' => $bench,
        'models' => $list['data'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'test') {
    if (!opencode_is_configured()) {
        echo json_encode(['success' => false, 'error' => 'OpenCode API key not configured (config/app.php → OPENCODE_API_KEY).', 'fallback' => 'templates']);
        exit;
    }
    [$ok, $text, $used, $errors] = opencode_chat_fallback($model, [
        ['role' => 'system', 'content' => 'Reply with exactly: OpenCode lane OK'],
        ['role' => 'user', 'content' => 'Probe: reply with exactly: OpenCode lane OK'],
    ], 0.2, 50);
    if ($ok) {
        echo json_encode(['success' => true, 'model' => $used, 'reply' => substr(trim($text), 0, 200)]);
    } else {
        echo json_encode(['success' => false, 'error' => 'All OpenCode models failed.', 'errors' => array_slice($errors, 0, 6), 'fallback' => 'templates']);
    }
    exit;
}

if ($action === 'analyze') {
    $data = is_array($req['data'] ?? null) ? $req['data'] : $req;
    [$ok, $brief, $used, $errors] = opencode_analyze_requirements($data, $model);
    echo json_encode([
        'success' => $ok,
        'brief' => $brief,
        'model' => $used,
        'ai' => $ok,
        'errors' => $ok ? [] : array_slice($errors, 0, 6),
        'fallback' => $ok ? null : 'template-brief',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'generate_one') {
    $data = is_array($req['data'] ?? null) ? $req['data'] : [];
    $variation = preg_replace('/[^a-z]/', '', strtolower((string)($req['variation'] ?? 'classic')));
    if (!in_array($variation, ['classic', 'bold', 'editorial'], true)) $variation = 'classic';
    $mode = in_array(($req['mode'] ?? 'static'), ['static', 'admin', 'database'], true) ? $req['mode'] : 'static';
    $slot = isset($req['slot']) ? max(-1, min(2, (int)$req['slot'])) : -1;
    $brief = trim((string)($req['brief'] ?? ''));
    if ($brief === '') {
        [$bok, $brief, $bused] = opencode_analyze_requirements($data, $model);
    }
    [$ok, $html, $used, $errors, $usage] = opencode_generate_variation($data, $variation, $mode, $brief, $model, $slot);
    if ($ok) {
        echo json_encode([
            'success' => true, 'engine' => 'opencode', 'model' => $used,
            'variation' => $variation, 'slot' => $slot, 'html' => $html, 'brief' => $brief,
            'usage' => $usage,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false, 'engine' => 'opencode-failed',
            'variation' => $variation, 'brief' => $brief,
            'error' => 'OpenCode AI could not generate this variation.',
            'errors' => array_slice($errors, 0, 6),
            'fallback' => 'templates',
            'hint' => 'Call api/generate.php action=flowcraft_generate for the premium template trio.',
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($action === 'edit') {
    $html = (string)($req['current_html'] ?? '');
    $instruction = trim((string)($req['instruction'] ?? ''));
    $biz = trim((string)($req['biz_name'] ?? 'Website')) ?: 'Website';
    if ($html === '' || $instruction === '') {
        echo json_encode(['success' => false, 'error' => 'Missing current_html or instruction']);
        exit;
    }
    [$ok, $out, $used, $errors, $usage] = opencode_edit_html($html, $instruction, $biz, $model);
    if ($ok) {
        $changed = trim($out) !== trim($html);
        echo json_encode([
            'success' => true, 'engine' => 'opencode', 'model' => $used,
            'html' => $out, 'changed' => $changed,
            'usage' => $usage,
            'response_msg' => '✨ OpenCode AI applied your change' . ($used ? " ({$used})" : '') . '.',
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false, 'engine' => 'opencode-failed',
            'error' => 'OpenCode AI edit failed.',
            'errors' => array_slice($errors, 0, 6),
            'fallback' => 'gemini-then-smart-engine',
            'hint' => 'Retry via api/generate.php action=refine (Gemini → smart engine).',
            'html' => $html,
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
