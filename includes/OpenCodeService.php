<?php
// ═══════════════════════════════════════════════════════════════
//  includes/OpenCodeService.php — OpenCode Zen AI gateway client
//
//  CHAIN: OpenCode (all free models w/ fallback) → Gemini → Puter.js
//         → server templates. Nothing ever comes back empty.
//
//  Free models work inside OpenCode; via raw HTTP they may return
//  FreeTierError (no session) and paid models may return
//  "Insufficient account funds". Every function treats those as
//  normal fallback signals — never fatal.
//
//  FlowCraft skills are FED to the AI via system prompts:
//   - ANALYZE: requirements → design brief (Skills 1-2)
//   - GENERATE: premium raw-HTML generator (Skills 3-21)
//   - EDIT: surgical full-document edit (Skills 27-28)
// ═══════════════════════════════════════════════════════════════

require_once dirname(__DIR__) . '/config.php';

// ── Live model ids from Zen (file-cached 6h, [] on failure) ────
// NOTE: never calls opencode_free_models() (avoids recursion).
function opencode_live_ids(): array {
    static $ids = null;
    if ($ids !== null) return $ids;
    $ids = [];
    if (!opencode_is_configured()) return $ids;
    $cacheFile = (defined('STORAGE_DIR') ? STORAGE_DIR : dirname(__DIR__) . '/storage') . '/opencode_models.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 21600)) {
        $j = json_decode(@file_get_contents($cacheFile), true);
        foreach ((array)($j['data'] ?? []) as $m) {
            if (!empty($m['id'])) $ids[] = $m['id'];
        }
        if (!empty($ids)) return $ids;
    }
    $ch = curl_init(defined('OPENCODE_MODELS_ENDPOINT') ? OPENCODE_MODELS_ENDPOINT : 'https://opencode.ai/zen/v1/models');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . opencode_api_key()],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $j = json_decode((string)$res, true);
    if (!empty($j['data']) && is_array($j['data'])) {
        foreach ($j['data'] as $m) {
            if (!empty($m['id'])) $ids[] = $m['id'];
        }
        @file_put_contents($cacheFile, json_encode(['data' => $j['data'], 'fetched_at' => date('c')]));
    }
    return $ids;
}

// ── Free-model registry (dynamic: live Zen list + hardcoded) ───
// Working models first (verified via Zen API). Auto-picks up new
// free models without code changes.
function opencode_free_models(): array {
    $defaults = [
        'space-bunny-free', // verified working via Zen API
        'big-pickle',
        'muse-spark-1.3-contributor-free',
        'muse-spark-1.2-contributor-free',
        'mimo-v2.5-free',
        'mimo-v2.6-flash-free',
        'nemotron-3.5-lightning-free',
        'jev-1.13-free',
        'longcat-2.5-preview-free',
    ];
    if (defined('OPENCODE_FREE_MODELS')) {
        $raw = OPENCODE_FREE_MODELS;
        $cfg = is_array($raw) ? $raw : (json_decode((string)$raw, true) ?: []);
        if (!empty($cfg)) $defaults = array_values(array_unique($cfg));
    }
    $merged = [];
    foreach (opencode_live_ids() as $id) {
        if ($id === '' || $id === 'test') continue;
        if (stripos($id, 'free') !== false || $id === 'big-pickle') $merged[] = $id;
    }
    foreach ($defaults as $d) {
        if (!in_array($d, $merged, true)) $merged[] = $d;
    }
    $first = ['space-bunny-free', 'big-pickle'];
    usort($merged, function ($a, $b) use ($first) {
        $pa = array_search($a, $first, true);
        $pb = array_search($b, $first, true);
        $pa = ($pa === false) ? 99 : $pa;
        $pb = ($pb === false) ? 99 : $pb;
        return $pa <=> $pb;
    });
    return array_values(array_unique($merged));
}

function opencode_api_key(): string {
    $k = defined('OPENCODE_API_KEY') ? (string)OPENCODE_API_KEY : '';
    if ($k === '' || stripos($k, 'YOUR_') !== false) return '';
    return trim($k);
}

function opencode_is_configured(): bool {
    return strlen(opencode_api_key()) > 10;
}

// ── Live model list (cached 6h, falls back to registry) ───────
function opencode_list_models(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $live = opencode_live_ids();
    if (!empty($live)) {
        $data = array_map(fn($id) => ['id' => $id, 'object' => 'model', 'owned_by' => 'opencode'], $live);
        $cache = ['data' => $data, 'free' => opencode_free_models()];
        return $cache;
    }
    $ids = opencode_free_models();
    $data = array_map(fn($id) => ['id' => $id, 'object' => 'model', 'owned_by' => 'opencode'], $ids);
    $cache = ['data' => $data, 'free' => opencode_free_models()];
    return $cache;
}

// ── Answer extractor: content first, reasoning-draft as fallback ──
// Reasoning models (e.g. space-bunny-free) may draft the page inside
// `reasoning_content` and leave `content` empty (esp. on length cut).
function opencode_extract_answer(array $msg): string {
    $c = trim((string)($msg['content'] ?? ''));
    if ($c !== '') return $c;
    $r = (string)($msg['reasoning_content'] ?? ($msg['reasoning'] ?? ''));
    if ($r === '' || !is_string($r)) return '';
    $start = -1;
    foreach (['<!DOCTYPE', '<html'] as $needle) {
        $p = stripos($r, $needle);
        if ($p !== false && ($start === -1 || $p < $start)) $start = $p;
    }
    if ($start === -1) return '';
    $html = substr($r, $start);
    if (preg_match('/<\/html>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
        $html = substr($html, 0, $m[0][1] + 7);
    }
    return trim($html);
}

// ── Usage accumulator (token transparency: WHY tokens are spent) ──
function opencode_add_usage(array $total, $u): array {
    return ['prompt' => $total['prompt'] + (int)(is_array($u) ? ($u['prompt'] ?? 0) : 0),
            'completion' => $total['completion'] + (int)(is_array($u) ? ($u['completion'] ?? 0) : 0)];
}

// ── Single chat/completions call ───────────────────────────────
// Returns [ok(bool), text(string), err(string|null), fatal(bool), usage[]]
// fatal=true means don't retry other models (bad request, not quota).
function opencode_chat_once(string $model, array $messages, float $temperature = 0.7, int $maxTokens = 12000, ?int $timeout = null): array {
    $key = opencode_api_key();
    if ($key === '') return [false, '', 'OpenCode API key not configured', true, ['prompt' => 0, 'completion' => 0]];
    $endpoint = defined('OPENCODE_CHAT_ENDPOINT') ? OPENCODE_CHAT_ENDPOINT : 'https://opencode.ai/zen/v1/chat/completions';
    $payload = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        // Verified: reasoning models burn the whole budget on thinking
        // (2500/2500 reasoning tok, empty answer). Capping reasoning →
        // real HTML in `content` in ~22s.
        'reasoning_effort' => 'low',
        'reasoning' => ['effort' => 'low', 'max_tokens' => 300],
    ];
    if ($timeout === null) $timeout = defined('OPENCODE_TIMEOUT') ? (int)OPENCODE_TIMEOUT : 90;
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $key],
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') return [false, '', 'Network error: ' . $curlErr, false, ['prompt' => 0, 'completion' => 0]];
    $j = json_decode((string)$res, true);
    $uj = (isset($j['usage']) && is_array($j['usage'])) ? $j['usage'] : [];
    $usage = ['prompt' => (int)($uj['prompt_tokens'] ?? 0), 'completion' => (int)($uj['completion_tokens'] ?? 0)];
    $msg = (isset($j['choices'][0]['message']) && is_array($j['choices'][0]['message'])) ? $j['choices'][0]['message'] : [];
    $answer = opencode_extract_answer($msg);
    if ($code === 200 && $answer !== '') {
        return [true, $answer, null, false, $usage];
    }
    if ($code === 200) {
        error_log('[OpenCodeService] model=' . $model . ' code=200 empty content (cap hit?)');
        return [false, '', $model . ': empty content (output cap hit)', false, $usage];
    }
    $emsg = '';
    if (is_array($j)) {
        $emsg = $j['error']['message'] ?? $j['error']['error']['message'] ?? '';
        if ($emsg === '' && isset($j['error']) && is_string($j['error'])) $emsg = $j['error'];
    }
    if ($emsg === '') $emsg = 'HTTP ' . $code . ': ' . substr((string)$res, 0, 200);
    // Retryable signals: free-tier lock, no funds, rate limits, upstream hiccups
    $retryable = (bool)preg_match('/FreeTier|funds|quota|rate|limit|429|402|403|overload|timeout|upstream|server_error/i', $emsg . ' ' . $code);
    error_log('[OpenCodeService] model=' . $model . ' code=' . $code . ' err=' . substr($emsg, 0, 200));
    return [false, '', $model . ': ' . $emsg, !$retryable, $usage];
}

// ── Chat with fallback across ALL free models ─────────────────
// Returns [ok, text, modelUsed|null, errors[], usage{prompt,completion}]
// $patient=false → no 8s 429-wait (for 30s-guillotine chunk requests).
function opencode_chat_fallback(?string $preferred, array $messages, float $temperature = 0.7, int $maxTokens = 12000, ?int $timeout = null, bool $patient = true): array {
    $free = opencode_free_models();
    $queue = [];
    if ($preferred && !in_array($preferred, $queue, true)) $queue[] = $preferred;
    foreach ($free as $m) {
        if (!in_array($m, $queue, true)) $queue[] = $m;
    }
    $errors = [];
    $total = ['prompt' => 0, 'completion' => 0];
    foreach ($queue as $model) {
        [$ok, $text, $err, $fatal, $u] = opencode_chat_once($model, $messages, $temperature, $maxTokens, $timeout);
        $total = opencode_add_usage($total, $u);
        if ($ok) return [true, $text, $model, $errors, $total];
        $errors[] = $err;
        if ($fatal) break;
        // Rate-limit (429): WAIT + retry the SAME model once instead of
        // burning through all models (saves tokens + respects free tier).
        // Skipped when !$patient (chunk requests under Apache's 30s guillotine).
        if ($patient && preg_match('/429|rate[\s\-_]*limit|too many requests/i', (string)$err)) {
            sleep(8);
            [$ok2, $text2, $err2, $f2, $u2] = opencode_chat_once($model, $messages, $temperature, $maxTokens, $timeout);
            $total = opencode_add_usage($total, $u2);
            if ($ok2) return [true, $text2, $model, $errors, $total];
            $errors[] = $err2;
        }
    }
    return [false, '', null, $errors, $total];
}

// ── HTML validators (mirror ai-flowcraft.js) ──────────────────
function opencode_clean_html(string $raw): string {
    $s = trim($raw);
    $s = preg_replace('/^```(?:html)?\s*/i', '', $s);
    $s = preg_replace('/```\s*$/', '', $s);
    $dt = -1;
    if (preg_match('/<!DOCTYPE|<html/i', $s, $m, PREG_OFFSET_CAPTURE)) $dt = $m[0][1];
    if ($dt > 0) $s = substr($s, $dt);
    if (preg_match('/<\/html>/i', $s, $m, PREG_OFFSET_CAPTURE)) {
        $s = substr($s, 0, $m[0][1] + 7);
    }
    $s = preg_replace('/<\?php[\s\S]*?(?:\?>|$)/i', '', $s);
    $s = str_replace('?>', '', $s);
    return trim($s);
}

function opencode_is_complete_html(string $s): bool {
    if (strlen($s) < 200) return false;
    return (bool)(preg_match('/<!DOCTYPE\s+html|<html[\s>]/i', $s)
        && preg_match('/<head[\s>]/i', $s)
        && preg_match('/<body[\s>]/i', $s)
        && preg_match('/<\/html>/i', $s));
}

function opencode_is_refusal(string $s): bool {
    $t = strtolower(substr($s, 0, 800));
    return (bool)preg_match('/what type of website|static or admin|admin or database|with or without database|would you like|do you want|please (specify|clarify|provide)|let me know|here is a (plan|suggestion)|sure[,!.]|of course[,!.]|which option|i(\'| a)?m unable|i cannot/i', $t);
}

// ═══════════════════════════════════════════════════════════════
//  FlowCraft skills fed to the AI (ANALYZE → GENERATE → EDIT)
// ═══════════════════════════════════════════════════════════════

function opencode_analyze_system(): string {
    return <<<'SYS'
You are a senior UX strategist + brand analyst (AI-FlowCraft Skills 1-2: Requirements Discussion + PRD Generation).
Given CLIENT REQUIREMENTS, FULLY ANALYZE them and return a tight DESIGN BRIEF.
Rules:
- No questions. No chat. If a field is "(unspecified)", invent a professional default.
- Output ONLY the brief in this exact shape (plain text, no markdown fences):

AUDIENCE: <one line — who visits and what they want>
SECTIONS: <comma list — exact sections to build, in order>
PALETTE: <3 hex codes primary/secondary/accent mapped from the brand color>
TYPE: <display font + body font pairing>
DIFFERENTIATORS: <3 bullets — what makes this site feel premium, not generic>
PRODUCTS: <if shop: "Name | Price" lines verbatim, else NONE>
ADMIN_ENTITIES: <if mode needs admin: entity list with 3-4 fields each, else NONE>
RISKS: <one line — what to avoid for this business type>

BOUNDARY GUARDRAILS: stay inside the analyst role — no code, no architecture. Every requirement maps to Acceptance Criteria IDs in format {FEATURE}-AC-{NNN} covering Happy Path, Edge & Error, and Business Rules.
SYS;
}

function opencode_gen_system(): string {
    return opencode_gen_system_compact();
}

// Compact premium generator for the free lane: a complete premium page
// that fits in ~2500 output tokens. Same quality bar, strict economy:
// shared classes, CSS variables, zero repetition, zero filler.
function opencode_gen_system_compact(): string {
    return <<<'SYS'
You are a RAW HTML CODE GENERATOR — nothing else.
HARD RULES: NEVER chat, ask, or explain. ONLY output: one complete HTML document, first line <!DOCTYPE html>, last line </html>. No markdown fences. No forbidden phrases ("would you like", "do you want", "Sure,", "Here is", "let me know"). Use CLIENT REQUIREMENTS verbatim; "(unspecified)" → invent a pro default, never ask. Mode under GENERATION MODE is already decided — follow it, don't ask.
ECONOMY (strict — you have a tight output budget): shared CSS classes + :root variables only; one font family; no comments; no repeated blocks; concise copy; tiny JS. Still complete: every required section + working interactive JS.
DESIGN (must feel premium): sticky glass navbar (blur, shrinks on scroll) + hamburger under 860px; hero with gradient-mesh/glow, clamp() display type with gradient accent word, 2 CTAs, trust badges; glass cards; clay-style CTAs; scroll-reveal (IntersectionObserver, staggered); animated counters; marquee ribbon; hover lift/glow; back-to-top; responsive clamp() type, max-width 1200px. Real Unsplash photos (https://images.unsplash.com/photo-XXXX?auto=format&fit=crop&w=1200&q=70) with alt text in hero/about. Zero dead buttons — every link scrolls somewhere real or toggles something.
CONTACT: exactly one <form id="contact-form" method="POST"> (name required, email required, phone optional, message required, submit). If a location is given: map iframe (https://www.google.com/maps?q=ENCODED_ADDRESS&output=embed, rounded frame) + Get Directions link (https://www.google.com/maps/dir/?api=1&destination=ENCODED_ADDRESS). Shop section + cart ONLY when PRODUCTS list is non-empty.
JS (one small <script>): smooth anchor scroll, mobile drawer toggle, navbar shrink on scroll, IntersectionObserver reveal, animated counters, FAQ toggle fn, back-to-top show/click, contact submit → success toast.
Tanglish/Tamil/English understood; code stays English.
SYS;
}

function opencode_gen_system_full(): string {
    return <<<'SYS'
You are a RAW HTML CODE GENERATOR — nothing else (AI-FlowCraft Skills 3-21: architecture → implementation).

## HARD RULES (VIOLATION = FAILURE)
1. NEVER chat. NEVER ask questions. NEVER explain.
2. NEVER ask "what type of website", "static or admin or database", "would you like", "do you want", "let me know", "please specify".
3. The user has ALREADY chosen the mode. It is written in the prompt under "GENERATION MODE". Read it. Follow it. Do not ask about it.
4. Your ONLY output is one complete HTML document.
5. Start with: <!DOCTYPE html>
6. End with: </html>
7. No markdown fences (no ```html). No prose before or after.
8. Use CLIENT REQUIREMENTS verbatim. If "(unspecified)", invent a default — do NOT ask.

## FORBIDDEN PHRASES (never appear in your response)
- "what type of website" / "would you like" / "do you want" / "please specify" / "let me know" / "Sure," / "Here is" / "I can help" / "Which option" / "static or admin"

## MANDATORY CHECKLIST
- [ ] Response starts with <!DOCTYPE html> / ends with </html>
- [ ] Contains <head>, <style>, <body>, <script>
- [ ] Uses exact business name + tagline from requirements
- [ ] Follows the GENERATION MODE written in the prompt
- [ ] Zero forbidden phrases

## PREMIUM DESIGN MANDATE (every variant must feel expensive)
- Smooth scrolling + anchor nav + back-to-top button. Sticky GLASSMORPHISM navbar (blur 16px, hairline border, shadow on scroll).
- GLASS CARDS over gradient-mesh backgrounds, CLAYMORPHISM CTAs + counters, oversized display typography (clamp), gradient accent word, dual CTA, trust badges.
- Scroll-reveal via IntersectionObserver (staggered), micro-interactions (lift/glow/tilt/counters/marquee), max-width 1200px, 8px rhythm, eyebrow labels.
- Fully responsive (clamp type, hamburger under 860px, 44px+ tap targets). Zero emojis in UI chrome (inline SVG). Real photos via https://images.unsplash.com/photo-XXXX?auto=format&fit=crop&w=1200&q=70 with alt text.
- Real 3D depth (parallax layers, card tilt), motion everywhere (preloader, counters, marquee, magnetic buttons, testimonial slider, FAQ accordion, mobile drawer, form validation states). Zero dead controls — every button does something real.
- Contact form: exactly one <form id="contact-form" method="POST"> with name/email/phone/message + submit. Map block when a location is given (iframe https://www.google.com/maps?q=ENCODED&output=embed + Get Directions link). Shop section + working cart ONLY when PRODUCTS list is non-empty.

## LANGUAGE SUPPORT
You understand Tanglish (Tamil in English letters), English, and Tamil script. Business content stays in the customer's language; code stays in English.
SYS;
}

function opencode_edit_system(): string {
    return <<<'SYS'
You are an expert front-end engineer doing SURGICAL EDITS (AI-FlowCraft Skills 27-28: Feature Evolution + Bug Fix).
You receive a COMPLETE HTML document + ONE user instruction (may be English, Tanglish, or Tamil).

PROTOCOL (follow in order):
1. FULLY ANALYZE the document first: list its sections, IDs/classes, JS handlers, and the exact location relevant to the instruction.
2. Apply ONLY the requested change. Touch nothing else. Preserve every class, ID, color, font, animation and handler unless told to change it.
3. New sections must match the document's design system (tokens, radius, shadows, motion).
4. Output the COMPLETE updated HTML document from <!DOCTYPE html> to </html>. No markdown fences. No commentary. No questions.
5. Tanglish examples: "hero section color maathu" = change hero color; "button periya aakku" = make button bigger; "footer la WhatsApp add pannu" = add WhatsApp to footer.
SYS;
}

function opencode_requirements_block(array $d): string {
    $sections = (!empty($d['sections']) && is_array($d['sections'])) ? implode(', ', $d['sections']) : 'hero, services, about, metrics, contact, footer';
    $styles = [
        'modern' => 'Modern & Clean — minimal, airy, flat, contemporary sans-serif.',
        'bold'   => 'Bold & Dynamic — high contrast, oversized type, gradients, animated.',
        'dark'   => 'Luxury & Dark — deep backgrounds, gold/violet accents, elegant, cinematic.',
    ];
    $styleDesc = $styles[$d['design_style'] ?? ''] ?? ($d['design_style'] ?? 'modern');
    $g = fn($k) => trim((string)($d[$k] ?? ''));
    $bullets = function ($raw) {
        $raw = trim((string)$raw);
        if ($raw === '') return '(none)';
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)));
        return "\n" . implode("\n", array_map(fn($s) => '  • ' . $s, $lines));
    };
    $shopOn = trim((string)($d['biz_products'] ?? '')) !== '' || in_array('shop', array_map('strtolower', (array)($d['sections'] ?? [])), true);
    $extra = $g('design_direction') !== '' ? "Additional Notes   : " . $g('design_direction') . "\n" : '';
    return "=== CLIENT REQUIREMENTS (MUST BE FOLLOWED EXACTLY) ===\n"
        . "Business Name      : " . ($g('biz_name') ?: 'Apex Studio') . "\n"
        . "Industry / Niche   : " . ($g('biz_type') ?: 'Creative Agency') . "\n"
        . "Core Mission       : " . ($g('biz_tagline') ?: 'Elevate your digital presence.') . "\n"
        . "Target Audience    : " . ($g('biz_audience') ?: '(unspecified)') . "\n"
        . "Services / Products: " . ($g('biz_services') ?: '(unspecified)') . "\n"
        . "Client Reviews     :" . $bullets($d['biz_reviews'] ?? '') . "\n"
        . "Shop Products      :" . $bullets($d['biz_products'] ?? '') . "\n"
        . "Shop Mode          : " . ($shopOn ? 'ON — build PRODUCTS section + working cart' : 'OFF') . "\n"
        . "Primary Design Dir : " . $styleDesc . "\n" . $extra
        . "Brand Color Accent : " . ($g('color_palette') ?: 'purple') . " (purple #6366f1, blue #2563eb, green #059669, red #dc2626, gold #d97706, slate #334155)\n"
        . "Contact Email      : " . ($g('biz_email') ?: '(unspecified)') . "\n"
        . "Contact Phone      : " . ($g('biz_phone') ?: '(unspecified)') . "\n"
        . "Location           : " . ($g('biz_address') ?: '(unspecified)') . "\n"
        . "Required Sections  : " . $sections . "\n"
        . "=== END CLIENT REQUIREMENTS ===";
}

function opencode_layout_brief(string $variationId, int $slot = -1): string {
    $briefs = [
        'classic' => "Classic Corporate — centered hero stack, 3-col service cards, split about, 4-col metrics band, 2-col contact card. Radius 8-10px. Order: Hero → Services → About → Metrics → Contact → Footer.",
        'bold' => "Bold & Dynamic — full-bleed oversized clamp headline with gradient word, bento services grid, dark about band, gradient metric cards, glass contact card. Radius 20-28px. Order: Hero → Metrics → Services → About → Contact → Footer.",
        'editorial' => "Minimal Editorial — serif magazine hero, alternating text/image service rows (no cards), narrow about column, inline metrics ribbon, minimal contact. Radius 0-4px. Order: Hero → About → Services → Metrics → Contact → Footer.",
    ];
    // Per-slot layout directions — 3 distinct AI layouts inside ONE style
    $slots = [
        'classic' => [
            "Conversion & Split Hero — split hero (copy left, photo with floating stat card right), 3-col service cards, metrics bar, 2-col contact.",
            "Bento Grid & Media Showcase — asymmetric bento services grid (1 large + 2 small cards), featured showcase band, hover lift states.",
            "Editorial Authority — magazine headline, trust credential strip, alternating text/image service rows, endorsement quotes.",
        ],
        'bold' => [
            "Neo-Brutalist High Impact — thick dark borders, hard offset shadows, vibrant badges, oversized CTA buttons.",
            "Gradient Aurora Cyber — gradient-mesh background, glowing glass panels, 3D tilt cards, tech-forward atmosphere.",
            "Growth Funnel Velocity — announcement bar, conversion mockup card, metric badges, fast-action lead capture.",
        ],
        'editorial' => [
            "Sovereign Minimal Gold — deep dark background, gold accents, serif display type, flagship showcase frame.",
            "Frosted Glass Aurora — ambient glows, frosted glass cards, luxury accolades, calm generous rhythm.",
            "Private Concierge Split — split executive layout, case-study rows, direct inquiry form, location map.",
        ],
    ];
    if ($slot >= 0 && $slot <= 2 && isset($slots[$variationId][$slot])) {
        return $slots[$variationId][$slot];
    }
    return $briefs[$variationId] ?? $briefs['classic'];
}

// ── Brand palette resolver: name → exact hexes (HARD constraint) ──
// Lite models ignore vague color hints, so the prompt carries exact
// hexes + forbidden colors. Blue must come out BLUE, never brown.
function opencode_palette_map(string $name): array {
    $maps = [
        'purple' => ['primary' => '#6366f1', 'secondary' => '#a855f7', 'accent' => '#38bdf8', 'light' => '#ede9fe', 'label' => 'PURPLE'],
        'blue'   => ['primary' => '#2563eb', 'secondary' => '#06b6d4', 'accent' => '#38bdf8', 'light' => '#dbeafe', 'label' => 'BLUE'],
        'green'  => ['primary' => '#059669', 'secondary' => '#10b981', 'accent' => '#84cc16', 'light' => '#d1fae5', 'label' => 'GREEN'],
        'red'    => ['primary' => '#dc2626', 'secondary' => '#f97316', 'accent' => '#fbbf24', 'light' => '#fee2e2', 'label' => 'RED'],
        'gold'   => ['primary' => '#d97706', 'secondary' => '#f59e0b', 'accent' => '#ef4444', 'light' => '#fef3c7', 'label' => 'GOLD'],
        'slate'  => ['primary' => '#1e293b', 'secondary' => '#475569', 'accent' => '#6366f1', 'light' => '#f1f5f9', 'label' => 'SLATE'],
    ];
    $key = strtolower(trim($name));
    return $maps[$key] ?? $maps['purple'];
}

function opencode_palette_block(string $paletteName): string {
    $p = opencode_palette_map($paletteName);
    return "## BRAND COLOR — HARD CONSTRAINT (HIGHEST PRIORITY, overrides everything)\n"
        . "The customer's brand color is {$p['label']}. Use these EXACT hex codes:\n"
        . "Primary: {$p['primary']} | Secondary: {$p['secondary']} | Accent: {$p['accent']} | Tint: {$p['light']}\n"
        . "- Paint ALL buttons, links, headings accents, badges, icons and active states with {$p['primary']}.\n"
        . "- Page backgrounds: white / #F8FAFC only.\n"
        . "- FORBIDDEN anywhere on this page: brown (#92400e #78350f #451a03), orange, gold, beige, gray-brown.\n"
        . "- Using any other primary color = FAILED output. When in doubt, use {$p['primary']}.\n"
        . "REMEMBER: PRIMARY IS {$p['primary']} ({$p['label']}).";
}

// ── Blank-page guard: complete HTML shell with no visible text ──
// (space-bunny sometimes returns an empty shell → must retry/fallback)
function opencode_has_content(string $html): bool {
    $t = preg_replace('/<script[\s\S]*?<\/script>/i', ' ', $html);
    $t = preg_replace('/<style[\s\S]*?<\/style>/i', ' ', $t);
    $t = html_entity_decode(strip_tags((string)$t), ENT_QUOTES, 'UTF-8');
    $t = trim(preg_replace('/\s+/', ' ', (string)$t));
    return strlen($t) >= 100;
}

// ── Stitch helper: append a continuation chunk without duplicating ──
function opencode_stitch_chunk(string $full, string $chunk): string {
    $chunk = trim($chunk);
    if ($chunk === '') return $full;
    // Model restarted instead of continuing (full doc / body / header upfront)
    // → use the newer copy instead of duplicating the page behind it.
    if (preg_match('/<!DOCTYPE|<html[\s>]|<head[\s>]|<body[\s>]|<header[\s>]|<nav[\s>]|<main[\s>]/i', substr($chunk, 0, 300))) {
        return $chunk;
    }
    return rtrim($full) . "\n" . ltrim($chunk);
}

// ── Structural duplicate guard: double <body>/</html> or repeated ──
// element ids (stitch seams). Duplicated ids make #anchor nav links
// die (browser jumps to the first/hidden copy) and repeat sections.
// NOTE: multiple <header>/<nav> tags alone are legal (top-bar + main
// nav + footer nav), so only ids + body/html are checked here.
function opencode_has_duplicate_structure(string $html): bool {
    if (preg_match_all('/<body[\s>]/i', $html) > 1) return true;
    if (preg_match_all('/<\/html>/i', $html) > 1) return true;
    if (preg_match_all('/\sid="([^"]+)"/i', $html, $m)) {
        $ids = array_map('strtolower', $m[1]);
        if (count($ids) !== count(array_unique($ids))) return true;
    }
    return false;
}

// ── High-level: analyze requirements → brief (non-fatal) ──────
// Returns [ok, brief, modelUsed|null, errors[], usage]
function opencode_analyze_requirements(array $data, ?string $model = null): array {
    $req = opencode_requirements_block($data);
    [$ok, $text, $used, $errors, $usage] = opencode_chat_fallback(
        $model,
        [
            ['role' => 'system', 'content' => opencode_analyze_system()],
            ['role' => 'user', 'content' => $req],
        ],
        0.5, 1000, 28, false
    );
    if ($ok) return [true, trim($text), $used, [], $usage];
    $biz = trim((string)($data['biz_name'] ?? 'Apex Studio'));
    $type = trim((string)($data['biz_type'] ?? 'Business'));
    $aud = trim((string)($data['biz_audience'] ?? 'Modern clients'));
    $fallback = "AUDIENCE: {$aud} seeking premium {$type} services\nSECTIONS: hero, services, about, metrics, contact, footer\nPALETTE: brand-mapped primary/secondary/accent\nTYPE: Plus Jakarta Sans display + Inter body\nDIFFERENTIATORS: glassmorphism nav; clay CTAs; scroll-reveal + counters\nPRODUCTS: NONE\nADMIN_ENTITIES: NONE\nRISKS: avoid generic stock look for {$biz}";
    return [false, $fallback, null, $errors, $usage ?? ['prompt' => 0, 'completion' => 0]];
}

// ── High-level: generate ONE premium variation via AI (CHUNKED) ──
// Free-lane reality: slow models + per-response output cap + reasoning
// budgets. Strategy: compact prompt (fits under the cap) + 2500-token
// chunks + "continue where you stopped" stitching (max 3 chunks).
// Returns [ok, html, modelUsed|null, errors[], usage]
function opencode_generate_variation(array $data, string $variationId, string $mode, string $brief, ?string $model = null, int $slot = -1): array {
    $modeLabel = ['static' => 'Static Website (HTML/CSS/JS only)', 'admin' => 'Website + Admin Panel + PHP Backend (JSON storage, NO database)', 'database' => 'Website + Admin Panel + PHP Backend + MySQL Database (full stack)'][$mode] ?? $mode;
    $user = "## TASK\nGenerate the complete, production-ready HTML file for the \"{$variationId}\" variant.\n\n"
        . "## GENERATION MODE — ALREADY DECIDED (DO NOT ASK)\nType: **{$modeLabel}**\nOutput the PUBLIC WEBSITE HTML only.\n\n"
        . "## AI DESIGN BRIEF (follow tightly)\n{$brief}\n\n"
        . "## CRITICAL OUTPUT RULES\n- Output ONLY raw HTML. No chat, no questions, no markdown.\n- First line: <!DOCTYPE html> / Last line: </html>\n\n"
        . opencode_requirements_block($data) . "\n\n"
        . "## LAYOUT DIRECTION — \"{$variationId}\"\n" . opencode_layout_brief($variationId, $slot) . "\n\n"
        . opencode_palette_block((string)($data['color_palette'] ?? 'purple')) . "\n\n"
        . "## FREE-LANE OUTPUT BUDGET (follow strictly)\n"
        . "- The page MUST be complete but efficient: all required sections, working nav/form/menu JS, responsive CSS.\n"
        . "- Write tight CSS (shared classes, no repeated 10-line blocks, no filler comments). Target a file that fits comfortably — quality over length.\n"
        . "- If the budget runs out, end cleanly after the last FULL section (never mid-tag).\n\n## NOW OUTPUT THE HTML FILE";
    $lastRaw = '';
    $errors = [];
    $genTimeout = defined('OPENCODE_GEN_TIMEOUT') ? (int)OPENCODE_GEN_TIMEOUT : 180;
    $CHUNK = 2500;
    $MAXCHUNKS = 3;
    $strictNote = '';
    $full = '';
    $usedModel = null;
    $usageTotal = ['prompt' => 0, 'completion' => 0];
    for ($c = 0; $c < $MAXCHUNKS * 2; $c++) {
        if ($c === 0 || $full === '') {
            $u = $user . $strictNote;
            $temp = 0.7;
            $fresh = true;
        } else {
            $tail = substr($full, -2000);
            $u = "You are continuing the SAME HTML file (same requirements, same layout direction). "
               . "Do NOT restart. Do NOT repeat anything already written. "
               . "Output ONLY the CONTINUATION as a raw HTML fragment — no markdown fences, no commentary. "
               . "Finish the file properly so it ends with </html>.\n\n"
               . "ALREADY WRITTEN TAIL (context only, do NOT repeat):\n{$tail}\n\nCONTINUE NOW:";
            $temp = 0.3;
        }
        [$ok, $text, $used, $errs, $cu] = opencode_chat_fallback(
            $model,
            [
                ['role' => 'system', 'content' => opencode_gen_system()],
                ['role' => 'user', 'content' => $u],
            ],
            $temp, $CHUNK, $genTimeout
        );
        $errors = array_merge($errors, $errs);
        $usageTotal = opencode_add_usage($usageTotal, $cu);
        if (!$ok) {
            // One patient retry (free-tier rate limits) before giving up
            sleep(5);
            [$ok, $text, $used, $errs, $cu] = opencode_chat_fallback(
                $model,
                [
                    ['role' => 'system', 'content' => opencode_gen_system()],
                    ['role' => 'user', 'content' => $u],
                ],
                $temp, $CHUNK, $genTimeout
            );
            $errors = array_merge($errors, $errs);
            $usageTotal = opencode_add_usage($usageTotal, $cu);
            if (!$ok) break; // chunk failed — validate whatever we have below
        }
        $usedModel = $used ?: $usedModel;
        $lastRaw = $text;
        $piece = opencode_clean_html($text);
        if ($piece === '') { $errors[] = ($used ?: 'ai') . ': empty chunk'; continue; }
        if (opencode_is_refusal($text) && $fresh && !opencode_is_complete_html($piece)) {
            $errors[] = ($used ?: 'ai') . ': conversational reply, aborting';
            $full = '';
            break;
        }
        $full = $fresh ? $piece : opencode_stitch_chunk($full, $piece);
        if (opencode_is_complete_html($full)) {
            if (!opencode_has_content($full)) {
                $errors[] = ($usedModel ?: 'ai') . ': BLANK page (no visible text) — strict rebuild…';
            } elseif (opencode_has_duplicate_structure($full)) {
                $errors[] = ($usedModel ?: 'ai') . ': DUPLICATE structure (double body / repeated ids — nav anchors would die) — strict rebuild…';
            } else {
                return [true, $full, $usedModel, [], $usageTotal];
            }
            $full = '';
            $strictNote = "\n\nSTRICT REMINDER: your previous output was an EMPTY shell. This time FILL every section with REAL visible text — headlines, paragraphs, service names, testimonials, contact details. A page with no readable words is a FAILURE.";
            continue; // → fresh strict rebuild
        }
        $errors[] = ($used ?: 'ai') . ': chunk ' . ($c + 1) . ' appended (' . strlen($full) . ' chars), continuing…';
        sleep(2); // be gentle on free-tier rate limits
    }
    if (opencode_is_complete_html($full) && opencode_has_content($full) && !opencode_has_duplicate_structure($full)) return [true, $full, $usedModel, [], $usageTotal];
    if ($full === '') return [false, '', null, $errors, $usageTotal];
    return [false, '', null, array_merge($errors, ['incomplete/blank/duplicate after chunks (' . strlen($full) . ' chars): ' . substr($lastRaw, 0, 120)]), $usageTotal];
}

// ── High-level: surgical AI edit of a full document ───────────
// Returns [ok, html, modelUsed|null, errors[], usage]
function opencode_edit_html(string $currentHtml, string $instruction, string $bizName = 'Website', ?string $model = null): array {
    if (trim($instruction) === '' || strlen($currentHtml) < 100) {
        return [false, '', null, ['Empty instruction or HTML'], ['prompt' => 0, 'completion' => 0]];
    }
    $user = "### FULL DOCUMENT TO ANALYZE (read everything first)\n```html\n" . substr($currentHtml, 0, 60000) . "\n```\n\n"
        . "### USER INSTRUCTION (apply ONLY this)\n" . trim($instruction) . "\n\n"
        . "### OUTPUT\nReturn the COMPLETE updated HTML document now. Business: {$bizName}.";
    $usageTotal = ['prompt' => 0, 'completion' => 0];
    for ($attempt = 0; $attempt < 2; $attempt++) {
        [$ok, $text, $used, $errs, $cu] = opencode_chat_fallback(
            $model,
            [
                ['role' => 'system', 'content' => opencode_edit_system()],
                ['role' => 'user', 'content' => $user],
            ],
            $attempt === 0 ? 0.3 : 0.15, 12000,
            defined('OPENCODE_GEN_TIMEOUT') ? (int)OPENCODE_GEN_TIMEOUT : 180
        );
        $usageTotal = opencode_add_usage($usageTotal, $cu);
        if (!$ok) return [false, '', null, $errs, $usageTotal];
        $html = opencode_clean_html($text);
        if (opencode_is_complete_html($html)) {
            if (!opencode_has_content($html)) continue;
            if (opencode_has_duplicate_structure($html)) { $errs[] = 'duplicate structure in edit'; continue; }
            return [true, $html, $used, [], $usageTotal];
        }
    }
    return [false, '', null, ['AI edit did not return a complete document'], $usageTotal];
}
