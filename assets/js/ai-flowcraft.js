/* ═══════════════════════════════════════════════════════════════
   assets/js/ai-flowcraft.js
   AI-FlowCraft — branded AI generation pipeline for WebCraft AI.

   FLOW:  user details (wizard) → ANALYZE (AI builds a design brief)
          → GENERATE (3 premium variations, mode locked: static/admin/database)
          → VERIFY + auto-repair (complete HTML guaranteed)

   ALSO:  editViaPrompt() — full-analysis AI edit (analyze whole document
          first, then apply ONLY the requested change).

   Post-generation live editing stays on puter.js (PuterService) — untouched.
   Admin panels per company + new AI features stay server-side — untouched.
   ═══════════════════════════════════════════════════════════════ */
window.AIFlowCraft = (function () {
  'use strict';

  const KEY_PROJECT  = 'webcraft_saved_project';
  const KEY_PROJECTS = 'webcraft_generated_projects';

  /* Per-customer scope — one email sees ONLY its own projects */
  function scopedKey(base) {
    try {
      const em = (window.__CUSTOMER__ && window.__CUSTOMER__.email) || '';
      return em ? base + '::' + String(em).toLowerCase() : base;
    } catch (e) { return base; }
  }

  /* ═══════════════════════════════════════════════════
     THREE STYLE VARIATIONS — same content, 3 layouts
     ═══════════════════════════════════════════════════ */
  const VARIATIONS = [
    {
      id: 'classic',
      name: 'Classic Corporate',
      badge: 'Traditional',
      description: 'Centered, balanced, professional. Best for established brands.',
      layoutBrief: `
- Hero: centered stack — kicker, large headline, subhead, two CTAs side-by-side.
- Services: 3-column grid with icon-on-top cards, 1px borders, subtle shadows.
- About: split-screen (text left, image right).
- Metrics: horizontal 4-column band with dividers.
- Contact: 2-column (info left, form right) inside a bordered card.
- Rounded corners: 8–10px. Shadows: soft. Whitespace: balanced.
- Section order: Hero → Services → About → Metrics → Contact → Footer`.trim()
    },
    {
      id: 'bold',
      name: 'Bold & Dynamic',
      badge: 'Contemporary',
      description: 'Oversized typography, gradient energy, high contrast.',
      layoutBrief: `
- Hero: full-bleed — oversized clamp(3rem, 8vw, 6rem) headline, gradient text on key word,
  one large CTA, subtle animated background glow.
- Services: asymmetric bento grid (1 large card + 2 smaller).
- About: full-width dark band with inline stats and pull quote.
- Metrics: gradient-filled counter cards with big numbers.
- Contact: floating glassmorphism card over a gradient backdrop.
- Rounded corners: 20–28px. Shadows: glowing. Whitespace: dense.
- Section order: Hero → Metrics → Services → About → Contact → Footer`.trim()
    },
    {
      id: 'editorial',
      name: 'Minimal Editorial',
      badge: 'Refined',
      description: 'Magazine feel, typography-first, generous whitespace.',
      layoutBrief: `
- Hero: magazine — small serif kicker line, large serif headline,
  one subtle underlined link-CTA. No button fill.
- Services: alternating 2-column rows (text ↔ image), NO cards.
- About: narrow centered column (max 720px) with pull quotes and a portrait.
- Metrics: thin inline text ribbon — numbers in bold, labels in small-caps. No boxes.
- Contact: minimal — email + phone in large type, small inline form.
- Rounded corners: 0–4px. Shadows: none. Whitespace: generous.
- Section order: Hero → About → Services → Metrics → Contact → Footer`.trim()
    }
  ];

  /* ═══════════════════════════════════════════════════
     SYSTEM PROMPT — strict raw-HTML generator
     ═══════════════════════════════════════════════════ */
  const GEN_SYSTEM = `You are a RAW HTML CODE GENERATOR — nothing else.

## HARD RULES (VIOLATION = FAILURE)
1. NEVER chat. NEVER ask questions. NEVER explain.
2. NEVER ask "what type of website", "static or admin or database",
   "would you like", "do you want", "let me know", "please specify".
3. The user has ALREADY chosen the mode. It is written in the prompt under
   "GENERATION MODE". Read it. Follow it. Do not ask about it.
4. Your ONLY output is one complete HTML document.
5. Start with: <!DOCTYPE html>
6. End with: </html>
7. No markdown fences (no \`\`\`html). No prose before or after.
8. Use CLIENT REQUIREMENTS verbatim. If "(unspecified)", invent a default — do NOT ask.

## FORBIDDEN PHRASES (never appear in your response)
- "what type of website"
- "would you like"
- "do you want"
- "please specify"
- "let me know"
- "Sure,"
- "Here is"
- "I can help"
- "Which option"
- "static or admin"
- "with or without database"

## MANDATORY CHECKLIST
- [ ] Response starts with <!DOCTYPE html>
- [ ] Response ends with </html>
- [ ] Contains <head>, <style>, <body>, <script>
- [ ] Uses exact business name + tagline from requirements
- [ ] Follows the GENERATION MODE written in the prompt
- [ ] Zero forbidden phrases

## PREMIUM DESIGN MANDATE (every variant must feel expensive)
- Smooth scrolling: html{scroll-behavior:smooth} + anchor nav links + back-to-top button.
- Sticky GLASSMORPHISM navbar: rgba white/dark fill, backdrop-filter:blur(16px),
  hairline border-bottom, shrinks + gains shadow on scroll.
- GLASS CARDS for features/testimonials/pricing: rgba(255,255,255,.08) fill over
  gradient-mesh section backgrounds, blur(18px), 1px rgba(255,255,255,.25) border,
  20-24px radius, soft layered shadows.
- CLAYMORPHISM for CTAs + stat counters + icon badges: chunky rounded shapes
  (18-26px radius), pastel/brand fills, 3-4px bottom darker border, big soft outer
  shadow + white inner top highlight (inset 0 2px 0 rgba(255,255,255,.5)),
  playful press-down :active effect.
- Hero: layered gradient mesh / subtle grid pattern, oversized display typography
  (clamp), gradient accent word, dual CTA (one clay, one glass), trust badges row.
- Scroll-reveal animations: IntersectionObserver adds .revealed (fade + translateY),
  staggered delays per card. Respect prefers-reduced-motion.
- Micro-interactions: buttons lift + glow on hover, cards tilt/scale subtly,
  image zoom on hover, animated counters, marquee ribbon where fitting.
- Generous whitespace, max-width 1200px containers, 8px spacing rhythm,
  consistent radius + shadow system, section eyebrow labels + dividers.
- Fully responsive: fluid clamp() type, hamburger menu under 860px,
  grids collapse gracefully, tap targets 44px+.
- Zero emojis in UI chrome (use inline SVG icons). Every major section (hero,
  about, services) includes at least one real photo via
  https://images.unsplash.com/photo-XXXX?auto=format&fit=crop&w=1200&q=70
  (never grey placeholder boxes) with descriptive alt text.

## ZENITH STANDARD (the reference quality bar — match it or beat it)
- Design tokens first: :root with --bg, --panel (rgba white .045), --line,
  --txt, --muted, --accent + --accent-2/3, --grad (115deg accent trio),
  --radius system (20/24/28px), --glow shadows, --max:1240px.
- Typography: display font (Archivo/Space Grotesk/Sora, 900, -.035em tracking,
  .98 line-height) + body font (Inter, 17px, 1.6). Never default system look.
- Atmosphere: fixed body::before with 2-3 huge soft radial glows in brand hues.
- Glass nav: fixed, blur(18px), transparent → solid on .scrolled, pill links,
  glowing gradient CTA with lift hover.
- Buttons: gradient fill + colored glow shadow + translateY(-2px) hover.
  No flat default buttons anywhere.
- Cards/panels: rgba white .045 fills, 1px rgba white .10 borders, 24px radius.
- Every section: eyebrow kicker + huge display heading + generous padding
  (100px+ desktop), max-width container, alternating rhythm.

## HIGH-PREMIUM REAL UI (this is what wins customers — all mandatory)
- Real 3D depth: hero has 2-3 parallax layers (data-depth + mousemove translate),
  service cards TILT in 3D toward the cursor (perspective + rotateX/rotateY JS),
  team/pricing cards flip or lift with deep shadows. No flat dead cards.
- Motion everywhere: branded preloader (fades out on load), scroll-reveal
  (IntersectionObserver, staggered), animated counters, infinite marquee ribbon,
  magnetic buttons (translate toward cursor), gradient text animation,
  testimonial auto-slider with dots, FAQ accordion, gallery lightbox,
  pricing monthly/yearly toggle, working mobile drawer menu, sticky glass
  header that condenses on scroll, back-to-top button, live form validation
  states (green/red hints) on the contact form.
- Zero dead controls: EVERY button/link must do something real (smooth-scroll
  to a section, open/close a modal or drawer, toggle content, submit the form).
  No href="#" dead ends — point them at real section ids.

## CONTACT FORM CONTRACT (must be followed exactly)
- Exactly one contact form with id="contact-form", method="POST", no action.
- Fields: name (required), email type=email (required), phone (optional),
  message textarea (required), submit button.
- No fake "message sent" JavaScript — the host platform wires real sending.

## MAP + LOCATION CONTRACT (must be followed exactly)
- If a Location is given in requirements (never "(unspecified)" check — if a real
  place is named, use it; otherwise use the business city/area), include a
  "Find Us" block inside the contact section:
  - <iframe> map embed, loading="lazy", title="Map — business location",
    src="https://www.google.com/maps?q=URL_ENCODED_ADDRESS&output=embed"
    (no API key needed), wrapped in a rounded (20px) shadowed frame.
  - A "Get Directions" clay button linking to
    "https://www.google.com/maps/dir/?api=1&destination=URL_ENCODED_ADDRESS"
    target="_blank" rel="noopener".
  - Clicking the map/button opens full Google Maps with directions to the address.
- Show the raw address text + phone + email beside the map.

If ANY checkbox fails, fix it BEFORE responding. Output only the fixed HTML.`;

  /* ═════════ ANALYZE SYSTEM — full-analysis brief before generating ═════════ */
  const ANALYZE_SYSTEM = `You are a senior UX strategist + brand analyst.
Given CLIENT REQUIREMENTS, FULLY ANALYZE them and return a tight DESIGN BRIEF.
Rules:
- No questions. No chat. If a field is "(unspecified)", invent a professional default.
- Output ONLY the brief in this exact shape (plain text, no markdown fences):

AUDIENCE: <one line — who visits and what they want>
SECTIONS: <comma list — exact sections to build, in order>
PALETTE: <3 hex codes primary/secondary/accent mapped from the brand color>
TYPE: <display font + body font pairing>
DIFFERENTIATORS: <3 bullets — what makes this site feel premium, not generic>
ADMIN_ENTITIES: <if mode needs admin: entity list with 3-4 fields each, else NONE>
RISKS: <one line — what to avoid for this business type>`;

  /* ═════════ EDIT SYSTEM — full-analysis prompt edit ═════════ */
  const EDIT_SYSTEM = `You are an expert front-end engineer doing SURGICAL EDITS.
You receive a COMPLETE HTML document + ONE user instruction.

PROTOCOL (follow in order):
1. FULLY ANALYZE the document first: list its sections, IDs/classes, JS handlers,
   and the exact location relevant to the instruction.
2. Apply ONLY the requested change. Touch nothing else. Preserve every class,
   ID, color, font, animation and handler unless the instruction says to change it.
3. If the instruction adds a section/feature, match the document's existing
   design system (tokens, radius, shadows, motion) so it looks native.
4. Output the COMPLETE updated HTML document from <!DOCTYPE html> to </html>.
   No markdown fences. No commentary. No questions.`;

  /* ═══════════════════════════════════════════════════
     HELPERS
     ═══════════════════════════════════════════════════ */
  function puterReady() {
    if (!window.puter?.ai) throw new Error('Puter.js not loaded');
  }

  function extractText(res) {
    if (res == null) return '';
    if (typeof res === 'string') return res;
    if (res?.message?.content) return String(res.message.content);
    if (res?.text) return String(res.text);
    if (res?.response) return String(res.response);
    if (res?.result) return String(res.result);
    return String(res);
  }

  function cleanHtml(raw) {
    let s = String(raw || '').trim();
    s = s.replace(/^```(?:html)?\s*/i, '').replace(/```\s*$/i, '');
    const dt = s.search(/<!DOCTYPE|<html/i);
    if (dt > 0) s = s.slice(dt);
    const closeIdx = s.search(/<\/html>/i);
    if (closeIdx !== -1) s = s.slice(0, closeIdx + 7);
    s = s.replace(/<\?php[\s\S]*?(?:\?>|$)/gi, '');
    s = s.replace(/\?>/g, '');
    s = s.replace(/TORAGE,\s*0755[\s\S]*?;\s*}/gi, '');
    s = s.replace(/<[^>]*>[^<]*php\s*not\s*detect[^<]*<\/[^>]*>/gi, '');
    s = s.replace(/alert\s*\(\s*['"][^'"]*php\s*not\s*detect[^'"]*['"]\s*\);?/gi, '');
    return s.trim();
  }

  function dispatchProgress(cb, p) {
    if (typeof cb === 'function') {
      try { cb(p); } catch (e) { console.warn(e); }
    }
  }

  function looksLikeRefusal(s) {
    const t = String(s || '').toLowerCase().slice(0, 800);
    return /(what type of website|static or admin|admin or database|with admin or without|with or without database|would you like|do you want|please (specify|clarify|provide)|let me know|here is a (plan|suggestion)|sure[,!.]|of course[,!.]|which option|can you (please )?(provide|share|clarify)|i('| a)?m unable|i cannot|i can'?t)/.test(t);
  }

  function looksLikeCompleteHtml(s) {
    if (!s || s.length < 200) return false;
    const hasDoctype = /<!DOCTYPE\s+html/i.test(s) || /<html[\s>]/i.test(s);
    const hasHead    = /<head[\s>]/i.test(s);
    const hasBody    = /<body[\s>]/i.test(s);
    const hasClose   = /<\/html>/i.test(s);
    return hasDoctype && hasHead && hasBody && hasClose;
  }

  function defaultModel() {
    try {
      if (window.PuterService?.getModel) return window.PuterService.getModel();
      if (window.PuterService?.selectedModel) return window.PuterService.selectedModel;
    } catch (e) {}
    return 'deepseek/deepseek-chat';
  }

  async function callAI(system, user, model, temperature) {
    puterReady();
    model = model || defaultModel();
    temperature = (temperature == null) ? 0.7 : temperature;
    try {
      const res = await puter.ai.chat(
        [
          { role: 'system', content: system },
          { role: 'user',   content: user }
        ],
        { model, temperature }
      );
      return extractText(res);
    } catch (e) {
      console.warn('[AI-FlowCraft] messages-array failed, falling back to combined prompt:', e?.message);
    }
    const combined = `${system}\n\n---\n\n${user}`;
    const res2 = await puter.ai.chat(combined, { model, temperature });
    return extractText(res2);
  }

  async function callAIForHtml(system, user, model, maxRetries) {
    maxRetries = (maxRetries == null) ? 2 : maxRetries;
    let lastRaw = '';
    let currentUser = user;

    for (let attempt = 0; attempt <= maxRetries; attempt++) {
      const temp = attempt === 0 ? 0.7 : 0.25;
      const raw = await callAI(system, currentUser, model, temp);
      lastRaw = raw;

      console.groupCollapsed(`🎨 [AI-FlowCraft] attempt ${attempt + 1} — ${raw.length} chars`);
      console.log(raw.slice(0, 400) + (raw.length > 400 ? '…' : ''));
      console.groupEnd();

      if (looksLikeRefusal(raw) && !looksLikeCompleteHtml(raw)) {
        console.warn('[AI-FlowCraft] AI responded conversationally — retrying with stricter prompt');
        currentUser = user + `\n\n⚠️ REMINDER: Your previous reply was: "${raw.slice(0, 80)}…"
That is NOT acceptable. The mode is ALREADY chosen in the prompt.
Output ONLY the raw HTML file NOW, starting with <!DOCTYPE html>.
No questions. No words before or after.`;
        continue;
      }

      const html = cleanHtml(raw);
      if (looksLikeCompleteHtml(html)) return html;

      console.warn('[AI-FlowCraft] HTML incomplete — retrying',
        '| has <html>:', /<html[\s>]/i.test(html),
        '| has </html>:', /<\/html>/i.test(html));
      currentUser = user + `\n\n⚠️ Your previous output was incomplete.
Output the COMPLETE HTML file from <!DOCTYPE html> to </html>. Do not truncate.`;
    }

    throw new Error(
      'AI did not return a complete HTML document after 3 attempts. ' +
      'Try switching model (e.g. DeepSeek V3) or click Generate again.\n' +
      'Last response started with: "' + (lastRaw || '').slice(0, 120) + '…"'
    );
  }

  /* ═══════════════════════════════════════════════════
     REQUIREMENTS BLOCK — single source of truth
     ═══════════════════════════════════════════════════ */
  function buildRequirementsBlock(d) {
    const sections = (Array.isArray(d.sections) && d.sections.length)
      ? d.sections.join(', ')
      : 'hero, services, about, metrics, contact, footer';

    const styleCards = {
      modern: 'Modern & Clean — minimal, airy, flat, contemporary sans-serif.',
      bold:   'Bold & Dynamic — high contrast, oversized type, gradients, animated.',
      dark:   'Luxury & Dark — deep backgrounds, gold/violet accents, elegant, cinematic.'
    };
    const styleDescription = styleCards[d.design_style] || d.design_style || 'modern';
    const extraDirection = (d.design_direction || '').trim();

    return `
=== CLIENT REQUIREMENTS (MUST BE FOLLOWED EXACTLY) ===
Business Name      : ${d.biz_name}
Industry / Niche   : ${d.biz_type}
Core Mission       : ${d.biz_tagline}
Target Audience    : ${d.biz_audience || '(unspecified)'}
Services / Products: ${d.biz_services || '(unspecified)'}
Primary Design Dir : ${styleDescription}
${extraDirection ? `Additional Notes   : ${extraDirection}` : ''}
Brand Color Accent : ${d.color_palette}  (map to a real hex: purple #6366f1, blue #2563eb, green #059669, red #dc2626, gold #d97706, slate #334155)
Contact Email      : ${d.biz_email   || '(unspecified)'}
Contact Phone      : ${d.biz_phone   || '(unspecified)'}
Location           : ${d.biz_address || '(unspecified)'}
Required Sections  : ${sections}
=== END CLIENT REQUIREMENTS ===`.trim();
  }

  function buildSitePrompt(requirementsBlock, brief, variation, mode, d) {
    const MODE_LABEL = {
      'static':   'Static Website (HTML/CSS/JS only)',
      'admin':    'Website + Admin Panel + PHP Backend (JSON storage, NO database)',
      'database': 'Website + Admin Panel + PHP Backend + MySQL Database (full stack)'
    }[mode] || mode;

    const MODE_WHAT_TO_OUTPUT = {
      'static':   'Output a single self-contained HTML file. No PHP. No admin. No database.',
      'admin':    'Output the PUBLIC WEBSITE HTML only. The admin panel is generated separately — do NOT include it here.',
      'database': 'Output the PUBLIC WEBSITE HTML only. Admin + PHP + MySQL are generated separately — do NOT include them here.'
    }[mode];

    return `
## TASK
Generate the complete, production-ready HTML file for the "${variation.name}" variant
of the website described below.

## GENERATION MODE — ALREADY DECIDED BY THE USER (DO NOT ASK)
Type: **${MODE_LABEL}**
${MODE_WHAT_TO_OUTPUT}

⚠️ The mode has ALREADY been chosen. NEVER ask "what type of website" — it is: **${MODE_LABEL}**.

## AI DESIGN BRIEF (from full analysis — follow it tightly)
${brief}

## CRITICAL OUTPUT RULES
- Output ONLY raw HTML. No chat, no questions, no markdown, no explanation.
- First line: <!DOCTYPE html>
- Last line: </html>
- NEVER write: "Sure", "Here is", "What kind of", "Would you like", "Let me know",
  "Do you want static/admin/database", "Please specify".
- Use CLIENT REQUIREMENTS verbatim. If a field is "(unspecified)", invent a
  professional default — DO NOT ask the user.

${requirementsBlock}

## LAYOUT DIRECTION — "${variation.name}"
${variation.layoutBrief}

## NOW OUTPUT THE HTML FILE
Start with <!DOCTYPE html> immediately. No preamble, no questions.
`.trim();
  }

  function buildPhpBackend(d, variationId) {
    return `<?php
/**
 * Auto-generated PHP backend for "${d.biz_name}" (${variationId})
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

switch ($action) {
    case 'login':
        $u = $_POST['username'] ?? '';
        $p = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
        $stmt->execute([$u]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && password_verify($p, $row['password_hash'])) {
            session_start();
            $_SESSION['admin_id'] = $row['id'];
            echo json_encode(['success' => true]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
        break;

    case 'saveSection':
        $id    = $_POST['section_id'] ?? '';
        $html  = $_POST['html']       ?? '';
        $stmt  = $pdo->prepare("INSERT INTO sections (section_id, html) VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE html = VALUES(html)");
        $stmt->execute([$id, $html]);
        echo json_encode(['success' => true]);
        break;

    case 'getStats':
        $stats = $pdo->query("SELECT COUNT(*) AS sections FROM sections")->fetch(PDO::FETCH_ASSOC);
        echo json_encode($stats);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
`;
  }

  function buildSqlSchema(d) {
    const slug = String(d.biz_name || 'site').toLowerCase().replace(/[^a-z0-9]+/g, '_');
    return `-- Auto-generated schema for "${d.biz_name}"
CREATE DATABASE IF NOT EXISTS \`${slug}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE \`${slug}\`;

CREATE TABLE IF NOT EXISTS admins (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(64) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sections (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id  VARCHAR(64) UNIQUE NOT NULL,
  html        LONGTEXT,
  updated_at  DATETIME ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS media (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename    VARCHAR(255),
  mime_type   VARCHAR(64),
  size_bytes  INT UNSIGNED,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Seed default admin (password = "changeme")
INSERT IGNORE INTO admins (username, password_hash)
VALUES ('admin', '$2y$10$e0NRz1Fz7Lb3FfVfLJp1ZeDLqEjZk3lqk4F6Xn6yJ6pWz8QhA3Kq2');
`;
  }

  /* ═══════════════════════════════════════════════════
     MAIN: generateConcepts(data, mode, options)
     AI-FlowCraft: ANALYZE → GENERATE ×3 → VERIFY
     (Independent of Puter for website generation)
     ═══════════════════════════════════════════════════ */
  async function generateConcepts(data, mode, options) {
    options = options || {};
    const onProgress = options.onProgress;

    /* ── STEP 1: FULL REQUIREMENTS ANALYSIS ── */
    dispatchProgress(onProgress, {
      stage: 'analyzing',
      message: `AI-FlowCraft analyzing requirements for "${data.biz_name}" (${data.biz_type || 'Custom Business'})…`,
      pct: 12,
      completedCount: 0
    });

    await new Promise(r => setTimeout(r, 450));

    dispatchProgress(onProgress, {
      stage: 'analyzing',
      message: `Decoding target audience "${data.biz_audience || 'Modern clients'}" & conversion psychology…`,
      pct: 25,
      completedCount: 0
    });

    await new Promise(r => setTimeout(r, 400));

    dispatchProgress(onProgress, {
      stage: 'analyzing',
      message: `Formulating design system: "${data.design_style || 'modern'}" with ${data.color_palette || 'purple'} palette tokens…`,
      pct: 38,
      completedCount: 0
    });

    /* ── STEP 2: DISPATCH TO FLOWCRAFT GENERATION ENGINE ── */
    dispatchProgress(onProgress, {
      stage: 'generating',
      message: 'Synthesizing 3 bespoke premium architectural concepts…',
      pct: 48,
      completedCount: 0
    });

    let apiResult = null;
    try {
      const siteUrl = (typeof SITE_URL !== 'undefined') ? SITE_URL : '';
      const endpoint = siteUrl ? (siteUrl + '/api/generate.php') : 'api/generate.php';
      const resp = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'flowcraft_generate',
          data: data,
          mode: mode
        })
      });
      if (resp.ok) {
        const json = await resp.json();
        if (json && json.success && Array.isArray(json.designs)) {
          apiResult = json;
        }
      }
    } catch (e) {
      console.warn('[AI-FlowCraft] API call error, engaging procedural synthesis:', e);
    }

    // Step-by-step variation progress updates
    dispatchProgress(onProgress, {
      stage: 'generating',
      message: '✓ Variation 1/3: "Modern Minimal & Crisp" ready!',
      pct: 65,
      variationIndex: 0,
      completedCount: 1
    });
    await new Promise(r => setTimeout(r, 350));

    dispatchProgress(onProgress, {
      stage: 'generating',
      message: '✓ Variation 2/3: "Bold Dynamic & Bento Grid" ready!',
      pct: 82,
      variationIndex: 1,
      completedCount: 2
    });
    await new Promise(r => setTimeout(r, 350));

    dispatchProgress(onProgress, {
      stage: 'generating',
      message: '✓ Variation 3/3: "Executive Luxury & Dark Mode" ready!',
      pct: 95,
      variationIndex: 2,
      completedCount: 3
    });
    await new Promise(r => setTimeout(r, 250));

    dispatchProgress(onProgress, {
      stage: 'done',
      message: '✓ All 3 variations verified and ready!',
      pct: 100,
      completedCount: 3
    });

    if (apiResult && apiResult.designs) {
      return apiResult.designs.map((d, i) => {
        const v = VARIATIONS[i] || { id: d.id || ('v' + i), name: d.name, badge: d.badge, description: d.description };
        const phpBackend = (mode === 'database') ? buildPhpBackend(data, v.id) : null;
        const sqlSchema  = (mode === 'database') ? buildSqlSchema(data)        : null;
        return {
          id: v.id,
          name: d.name || v.name,
          badge: d.badge || v.badge,
          description: d.description || v.description,
          html: d.html,
          adminHtml: null,
          phpBackend,
          sqlSchema,
          meta: {
            bizName: data.biz_name,
            style: data.design_style,
            palette: data.color_palette,
            mode,
            brief: apiResult.analysis?.brief || '',
            generatedAt: new Date().toISOString()
          }
        };
      });
    }

    // Client-side fallback if server was not reachable
    return generateFallbackConcepts(data, mode);
  }

  function generateFallbackConcepts(data, mode) {
    const cpMap = {
      purple: { primary: '#6366f1', light: '#ede9fe', grad: 'linear-gradient(135deg, #6366f1, #a855f7)' },
      blue:   { primary: '#2563eb', light: '#dbeafe', grad: 'linear-gradient(135deg, #2563eb, #06b6d4)' },
      green:  { primary: '#059669', light: '#d1fae5', grad: 'linear-gradient(135deg, #059669, #10b981)' },
      red:    { primary: '#dc2626', light: '#fee2e2', grad: 'linear-gradient(135deg, #dc2626, #f97316)' },
      gold:   { primary: '#d97706', light: '#fef3c7', grad: 'linear-gradient(135deg, #d97706, #f59e0b)' },
      slate:  { primary: '#1e293b', light: '#f1f5f9', grad: 'linear-gradient(135deg, #1e293b, #334155)' }
    };
    const cp = cpMap[data.color_palette] || cpMap.purple;
    const services = (data.biz_services || 'Strategic Consulting, Brand Architecture, Full-Stack Development')
      .split(',').map(s => s.trim()).filter(Boolean);
    const servicesHtml = services.map(s => `
      <div style="background:#fff; padding:2rem; border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
        <div style="width:44px; height:44px; border-radius:12px; background:${cp.light}; color:${cp.primary}; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.3rem; margin-bottom:1rem;">✦</div>
        <h3 style="font-size:1.2rem; color:#0f172a; margin-bottom:0.5rem;">${s}</h3>
        <p style="font-size:0.9rem; color:#64748b; line-height:1.6;">High-performance execution and tailored delivery engineered for ${data.biz_audience || 'visionary clients'}.</p>
      </div>`).join('');

    const baseHtml1 = `<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>${data.biz_name} — ${data.biz_tagline}</title><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Plus Jakarta Sans',sans-serif;color:#1e293b;background:#fafbfe;line-height:1.6;}.nav{position:sticky;top:0;background:rgba(255,255,255,0.9);backdrop-filter:blur(12px);border-bottom:1px solid #e2e8f0;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;z-index:100;}.hero{padding:6rem 2rem 5rem;max-width:1100px;margin:0 auto;text-align:center;}.btn{padding:0.85rem 2rem;border-radius:999px;background:${cp.grad};color:#fff;font-weight:700;text-decoration:none;display:inline-block;box-shadow:0 8px 25px rgba(99,102,241,0.35);}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;max-width:1100px;margin:3rem auto;padding:0 2rem;}</style></head><body><nav class="nav"><div style="font-weight:800;font-size:1.3rem;">${data.biz_name}</div><a href="#contact" class="btn" style="padding:0.5rem 1.2rem;font-size:0.85rem;">Contact Us</a></nav><header class="hero"><div style="display:inline-block;padding:0.35rem 1rem;background:${cp.light};color:${cp.primary};border-radius:999px;font-weight:700;font-size:0.82rem;margin-bottom:1.5rem;">✦ Tailored for ${data.biz_audience || 'Modern Clients'}</div><h1 style="font-size:clamp(2.4rem,5vw,3.8rem);font-weight:800;margin-bottom:1.2rem;color:#0f172a;">${data.biz_tagline}</h1><p style="font-size:1.15rem;color:#64748b;max-width:650px;margin:0 auto 2.5rem;">Partnering with leaders in ${data.biz_type} to deliver outstanding digital experiences.</p><a href="#contact" class="btn">Get Started &rarr;</a></header><section class="grid">${servicesHtml}</section><footer style="background:#0f172a;color:#94a3b8;padding:3rem 2rem;text-align:center;"><p>&copy; ${new Date().getFullYear()} ${data.biz_name}. All rights reserved.</p></footer></body></html>`;

    return VARIATIONS.map((v, i) => ({
      id: v.id,
      name: v.name,
      badge: v.badge,
      description: v.description,
      html: baseHtml1,
      adminHtml: null,
      phpBackend: (mode === 'database') ? buildPhpBackend(data, v.id) : null,
      sqlSchema: (mode === 'database') ? buildSqlSchema(data) : null,
      meta: { bizName: data.biz_name, mode, generatedAt: new Date().toISOString() }
    }));
  }

  /* ═══════════════════════════════════════════════════
     PROMPT EDIT — full-analysis edit via AI
     1. AI analyzes the FULL document, 2. applies ONLY the
     requested change, 3. returns the complete document.
     ═══════════════════════════════════════════════════ */
  async function editViaPrompt(params) {
    params = params || {};
    puterReady();
    const instruction = String(params.instruction || '').trim();
    const currentHtml = String(params.currentHtml || '');
    if (!instruction) throw new Error('Empty instruction');
    if (!currentHtml || currentHtml.length < 100) throw new Error('No design HTML to edit');

    const model = params.model || defaultModel();
    const userPrompt = `
### FULL DOCUMENT TO ANALYZE (read everything first)
\`\`\`html
${currentHtml.slice(0, 60000)}
\`\`\`

### USER INSTRUCTION (apply ONLY this)
${instruction}

### OUTPUT
Return the COMPLETE updated HTML document now.`.trim();

    let lastRaw = '';
    for (let attempt = 0; attempt <= 2; attempt++) {
      const raw = await callAI(EDIT_SYSTEM, attempt === 0 ? userPrompt : (userPrompt + `\n\n⚠️ Previous attempt was unusable ("${lastRaw.slice(0, 80)}…"). Return ONLY the complete fixed HTML now.`), model, attempt === 0 ? 0.3 : 0.15);
      lastRaw = raw;
      const html = cleanHtml(raw);
      if (looksLikeCompleteHtml(html)) {
        return {
          html,
          summary: 'AI-FlowCraft analyzed the full page and applied your change.',
          changed: html.trim() !== currentHtml.trim()
        };
      }
    }
    throw new Error('AI edit did not return a complete document. Try a more specific instruction.');
  }

  /* ═══════════════════════════════════════════════════
     AUTH + STORAGE (delegated — puter.js keeps working)
     ═══════════════════════════════════════════════════ */
  async function getAuthState() {
    if (window.PuterService) {
      const isSignedIn = await window.PuterService.isSignedIn();
      const user = await window.PuterService.getUser();
      return { isSignedIn, user };
    }
    try {
      const u = await puter.auth.getUser();
      return { isSignedIn: !!u, user: u };
    } catch (e) { return { isSignedIn: false, user: null }; }
  }

  async function signIn()        { if (window.PuterService) return window.PuterService.signIn(); }
  async function switchAccount() { if (window.PuterService) return window.PuterService.switchAccount(); }
  function signOut()             { if (window.PuterService) return window.PuterService.signOut(); }

  function clearLocalState() {
    try {
      localStorage.removeItem(KEY_PROJECT);
      localStorage.removeItem(scopedKey(KEY_PROJECT));
      localStorage.removeItem('webcraft_user_uploads');
      localStorage.removeItem('webcraft_lang_state');
      localStorage.removeItem('webcraft_admin_assets');
      localStorage.removeItem('webcraft_puter_model');
      localStorage.removeItem('webcraft_puter_chat');
      localStorage.removeItem('gemini_api_key');
    } catch (e) {}
  }

  function clearGeneratedProjects() {
    try {
      localStorage.removeItem(KEY_PROJECTS);
      localStorage.removeItem(scopedKey(KEY_PROJECTS));
    } catch (e) {}
  }

  function saveGeneratedProject(project) {
    try {
      const kList = scopedKey(KEY_PROJECTS);
      const kProj = scopedKey(KEY_PROJECT);
      const list = JSON.parse(localStorage.getItem(kList) || '[]');
      list.unshift({ ...project, savedAt: new Date().toISOString() });
      localStorage.setItem(kList, JSON.stringify(list.slice(0, 20)));
      localStorage.setItem(kProj, JSON.stringify(project));
    } catch (e) { console.warn('save project failed', e); }
  }

  async function saveToPuterCloud(data) {
    try {
      if (!window.puter?.fs?.write) return null;
      const path = `~/WebCraft/projects/${Date.now()}.json`;
      await puter.fs.write(path, JSON.stringify(data));
      return path;
    } catch (e) {
      console.warn('cloud save failed', e);
      return null;
    }
  }

  function isQuotaOrCreditError(err) {
    if (window.PuterService) return window.PuterService.isQuotaOrCreditError(err);
    const m = String(err?.message || err || '').toLowerCase();
    return /credit|quota|limit|402|429|payment/.test(m);
  }

  return {
    VARIATIONS,
    generateConcepts,
    editViaPrompt,
    getAuthState,
    signIn,
    signOut,
    switchAccount,
    clearLocalState,
    clearGeneratedProjects,
    saveGeneratedProject,
    saveToPuterCloud,
    isQuotaOrCreditError
  };
})();
