<?php
// ═══════════════════════════════════════════════════════════════
//  includes/GeminiService.php — Google Gemini FAST lane
//
//  gemini-3.5-flash-lite: verified 1.4s tiny / full pages ~10-20s.
//  (gemini-2.x retired 404; gemini-3.8-flash overloaded ~32s.)
//  Single-shot full pages fit Apache's 30s guillotine — no chunking.
//  Prompts + validators reused from OpenCodeService (compact system).
// ═══════════════════════════════════════════════════════════════

require_once __DIR__ . '/OpenCodeService.php';

function gemini_fast_models(): array {
    $models = [];
    if (defined('GEMINI_FAST_MODEL') && GEMINI_FAST_MODEL) $models[] = GEMINI_FAST_MODEL;
    $models[] = 'gemini-3.5-flash-lite';
    $models[] = 'gemini-3.8-flash';
    if (defined('GEMINI_MODEL') && GEMINI_MODEL) $models[] = GEMINI_MODEL;
    return array_values(array_unique(array_filter($models)));
}

function gemini_api_key(): string {
    $k = defined('GEMINI_API_KEY') ? (string)GEMINI_API_KEY : '';
    if ($k === '' || stripos($k, 'YOUR_') !== false) return '';
    return trim($k);
}

// Single Gemini call. Returns [ok, text, err|null, usage[]]
function gemini_chat_once(string $model, string $system, string $user, float $temperature = 0.7, int $maxTokens = 8000, int $timeout = 28): array {
    $key = gemini_api_key();
    if ($key === '') return [false, '', 'Gemini API key not configured', ['prompt' => 0, 'completion' => 0]];
    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($key);
    $payload = [
        'system_instruction' => ['parts' => [['text' => $system]]],
        'contents' => [['parts' => [['text' => $user]]]],
        'generationConfig' => ['temperature' => $temperature, 'maxOutputTokens' => $maxTokens],
    ];
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($curlErr !== '') return [false, '', 'Network error: ' . $curlErr, ['prompt' => 0, 'completion' => 0]];
    $j = json_decode((string)$res, true);
    $usage = ['prompt' => 0, 'completion' => 0];
    if (isset($j['usageMetadata'])) {
        $usage = ['prompt' => (int)($j['usageMetadata']['promptTokenCount'] ?? 0),
                  'completion' => (int)($j['usageMetadata']['candidatesTokenCount'] ?? 0)];
    }
    $text = $j['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if ($code === 200 && trim((string)$text) !== '') return [true, (string)$text, null, $usage];
    $err = '';
    if (is_array($j) && isset($j['error'])) $err = (string)(($j['error']['message'] ?? '') ?: json_encode($j['error']));
    if ($err === '') $err = 'HTTP ' . $code;
    error_log('[GeminiService] model=' . $model . ' code=' . $code . ' err=' . substr($err, 0, 200));
    return [false, '', $model . ': ' . $err, $usage];
}

// ── FAST: one premium variation, single shot (fits 30s) ────────
// Returns [ok, html, modelUsed|null, errors[], usage]
function gemini_generate_one(array $data, string $variationId, string $mode, string $brief, int $slot = -1, ?string $preferred = null): array {
    $modeLabel = ['static' => 'Static Website (HTML/CSS/JS only)', 'admin' => 'Website + Admin Panel + PHP Backend (JSON storage, NO database)', 'database' => 'Website + Admin Panel + PHP Backend + MySQL Database (full stack)'][$mode] ?? $mode;
    $user = "## TASK\nGenerate the complete, production-ready HTML file for the \"{$variationId}\" variant.\n\n"
        . "## GENERATION MODE — ALREADY DECIDED (DO NOT ASK)\nType: **{$modeLabel}**\nOutput the PUBLIC WEBSITE HTML only.\n\n"
        . "## AI DESIGN BRIEF (follow tightly)\n{$brief}\n\n"
        . "## CRITICAL OUTPUT RULES\n- Output ONLY raw HTML. No chat, no questions, no markdown.\n- First line: <!DOCTYPE html> / Last line: </html>\n\n"
        . opencode_requirements_block($data) . "\n\n"
        . "## LAYOUT DIRECTION — \"{$variationId}\"\n" . opencode_layout_brief($variationId, $slot) . "\n\n"
        . opencode_palette_block((string)($data['color_palette'] ?? 'purple')) . "\n\n"
        . "## OUTPUT BUDGET\n- Complete but efficient: all required sections, working nav/form/menu JS, responsive CSS.\n- Tight CSS (shared classes, no repetition, no filler). If the budget runs out, end cleanly after the last FULL section.\n\n## NOW OUTPUT THE HTML FILE";
    $queue = [];
    if ($preferred) $queue[] = $preferred;
    foreach (gemini_fast_models() as $m) {
        if (!in_array($m, $queue, true)) $queue[] = $m;
    }
    $errors = [];
    $usageTotal = ['prompt' => 0, 'completion' => 0];
    foreach ($queue as $model) {
        [$ok, $text, $err, $u] = gemini_chat_once($model, opencode_gen_system(), $user, 0.7, 8000, 28);
        $usageTotal = opencode_add_usage($usageTotal, $u);
        if (!$ok) { $errors[] = $err; continue; }
        if (opencode_is_refusal($text)) { $errors[] = $model . ': conversational reply'; continue; }
        $html = opencode_clean_html($text);
        if (opencode_is_complete_html($html)) {
            if (!opencode_has_content($html)) { $errors[] = $model . ': blank page'; continue; }
            if (opencode_has_duplicate_structure($html)) { $errors[] = $model . ': duplicate structure (double body / repeated ids)'; continue; }
            return [true, $html, $model, [], $usageTotal];
        }
        $errors[] = $model . ': incomplete HTML (' . strlen($html) . ' chars)';
    }
    return [false, '', null, $errors, $usageTotal];
}

// ── FAST: full-document edit, single shot ─────────────────────
// Returns [ok, html, modelUsed|null, errors[], usage]
function gemini_edit_html(string $currentHtml, string $instruction, string $bizName = 'Website', ?string $preferred = null): array {
    if (trim($instruction) === '' || strlen($currentHtml) < 100) {
        return [false, '', null, ['Empty instruction or HTML'], ['prompt' => 0, 'completion' => 0]];
    }
    $user = "### FULL DOCUMENT TO ANALYZE (read everything first)\n```html\n" . substr($currentHtml, 0, 40000) . "\n```\n\n"
        . "### USER INSTRUCTION (apply ONLY this)\n" . trim($instruction) . "\n\n"
        . "### OUTPUT\nReturn the COMPLETE updated HTML document now. Business: {$bizName}.";
    $queue = [];
    if ($preferred) $queue[] = $preferred;
    foreach (gemini_fast_models() as $m) {
        if (!in_array($m, $queue, true)) $queue[] = $m;
    }
    $errors = [];
    $usageTotal = ['prompt' => 0, 'completion' => 0];
    foreach ($queue as $model) {
        [$ok, $text, $err, $u] = gemini_chat_once($model, opencode_edit_system(), $user, 0.3, 12000, 28);
        $usageTotal = opencode_add_usage($usageTotal, $u);
        if (!$ok) { $errors[] = $err; continue; }
        $html = opencode_clean_html($text);
        if (opencode_is_complete_html($html)) {
            if (!opencode_has_content($html)) { $errors[] = $model . ': blank edit'; continue; }
            if (opencode_has_duplicate_structure($html)) { $errors[] = $model . ': duplicate structure in edit'; continue; }
            return [true, $html, $model, [], $usageTotal];
        }
        $errors[] = $model . ': incomplete edit (' . strlen($html) . ' chars)';
    }
    return [false, '', null, $errors, $usageTotal];
}
