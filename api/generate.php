<?php
// ═══════════════════════════════════════════════════════════════
//  api/generate.php — Multi-Design AI Website Generator Engine
//  FIXED VERSION:
//   - Correct Gemini model name (gemini-2.5-flash)
//   - Server-side change detection — never lies "updated"
//   - Higher maxOutputTokens for full HTML documents
//   - Better Gemini error reporting
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

require_once dirname(__DIR__) . '/config.php';

$raw  = file_get_contents('php://input');
$req  = json_decode($raw, true) ?: [];
$action = $req['action'] ?? 'generate_3';

// Resolve API key: user-supplied first, then server config
$apiKey = trim($req['api_key'] ?? '');
if (empty($apiKey) || $apiKey === 'YOUR_GEMINI_API_KEY_HERE') {
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
}

// ── CRITICAL FIX: use a REAL Gemini model name ──
// 'gemini-3.6-flash' does NOT exist. Use a real model:
$resolvedModel = (defined('GEMINI_MODEL') && GEMINI_MODEL && GEMINI_MODEL !== 'gemini-3.6-flash')
    ? GEMINI_MODEL
    : 'gemini-2.5-flash';

// ── Sanitize helper ───────────────────────────────────────────
$sanitize = function ($v) {
    return htmlspecialchars(strip_tags(trim((string)($v ?? ''))), ENT_QUOTES, 'UTF-8');
};

$d = $req['data'] ?? [];
$bizName     = $sanitize($d['biz_name'] ?? 'Apex Studio');
if (empty($bizName)) $bizName = 'Apex Studio';
$bizType     = $sanitize($d['biz_type'] ?? 'Creative Agency');
$bizTagline  = $sanitize($d['biz_tagline'] ?? 'Elevate your digital presence with modern web solutions.');
$bizAudience = $sanitize($d['biz_audience'] ?? 'Modern businesses, entrepreneurs, and clients seeking premium quality.');
$bizServices = $sanitize($d['biz_services'] ?? 'Web Design, Brand Strategy, Digital Marketing, Custom Development');
$style       = $sanitize($d['design_style'] ?? 'modern');
$palette     = $sanitize($d['color_palette'] ?? 'purple');
$bizPhone    = $sanitize($d['biz_phone'] ?? '+1 (555) 234-5678');
$bizEmail    = $sanitize($d['biz_email'] ?? 'contact@' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $bizName)) . '.com');
$bizAddress  = $sanitize($d['biz_address'] ?? '100 Innovation Blvd, Suite 400, Tech City');

$colorPresets = [
    'purple' => ['primary' => '#6366f1', 'secondary' => '#a855f7', 'accent' => '#38bdf8', 'gradient' => 'linear-gradient(135deg, #6366f1 0%, #a855f7 100%)', 'light' => '#ede9fe'],
    'blue'   => ['primary' => '#2563eb', 'secondary' => '#06b6d4', 'accent' => '#38bdf8', 'gradient' => 'linear-gradient(135deg, #2563eb 0%, #06b6d4 100%)', 'light' => '#dbeafe'],
    'green'  => ['primary' => '#059669', 'secondary' => '#10b981', 'accent' => '#84cc16', 'gradient' => 'linear-gradient(135deg, #059669 0%, #10b981 100%)', 'light' => '#d1fae5'],
    'red'    => ['primary' => '#dc2626', 'secondary' => '#f97316', 'accent' => '#fbbf24', 'gradient' => 'linear-gradient(135deg, #dc2626 0%, #f97316 100%)', 'light' => '#fee2e2'],
    'gold'   => ['primary' => '#d97706', 'secondary' => '#f59e0b', 'accent' => '#ef4444', 'gradient' => 'linear-gradient(135deg, #b45309 0%, #f59e0b 100%)', 'light' => '#fef3c7'],
    'slate'  => ['primary' => '#1e293b', 'secondary' => '#475569', 'accent' => '#6366f1', 'gradient' => 'linear-gradient(135deg, #1e293b 0%, #334155 100%)', 'light' => '#f1f5f9'],
];
$cp = $colorPresets[$palette] ?? $colorPresets['purple'];

// ═══════════════════════════════════════════════════════════════
//  Helper: call Gemini API once, returns [ok, text, httpCode, errMsg]
// ═══════════════════════════════════════════════════════════════
function callGemini(string $apiKey, string $model, string $prompt, int $maxTokens = 32000): array {
    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'
              . $model . ':generateContent?key=' . urlencode($apiKey);

    $payload = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => $maxTokens,
        ]
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if (!empty($curlErr)) {
        return [false, '', $code, 'Network error: ' . $curlErr];
    }
    if ($code !== 200) {
        $j = json_decode($res, true);
        $msg = $j['error']['message'] ?? ('HTTP ' . $code);
        error_log('[generate.php] Gemini HTTP ' . $code . ': ' . substr($res, 0, 500));
        return [false, '', $code, 'Gemini API error (' . $code . '): ' . $msg];
    }

    $parsed = json_decode($res, true);
    $text   = $parsed['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (empty($text)) {
        return [false, '', $code, 'Gemini returned empty response'];
    }
    return [true, $text, $code, null];
}

// ═══════════════════════════════════════════════════════════════
//  Shared JS bundle injected into every generated design
// ═══════════════════════════════════════════════════════════════
function getSharedJS($bizName) {
    return <<<JS
<script>
document.querySelectorAll('a').forEach(anchor => {
  anchor.addEventListener('click', function(e) {
    let targetId = this.getAttribute('href') || '';
    let targetEl = null;
    if (targetId && targetId.startsWith('#') && targetId.length > 1) {
      try { targetEl = document.querySelector(targetId); } catch(err) {}
    }
    if (!targetEl) {
      const rawText = this.innerText.trim().toLowerCase();
      const cleanSlug = rawText.replace(/[^a-z0-9]/g, '');
      if (cleanSlug.length >= 2) {
        targetEl = document.getElementById(cleanSlug) ||
                   document.getElementById(cleanSlug.replace('us', '')) ||
                   document.querySelector('section[id*="' + cleanSlug + '"]');
        if (!targetEl) {
          const headings = document.querySelectorAll('section h1, section h2, section h3, header h1, header h2');
          for (const h of headings) {
            const hText = h.innerText.trim().toLowerCase().replace(/[^a-z0-9]/g, '');
            if (hText.includes(cleanSlug) || cleanSlug.includes(hText)) {
              targetEl = h.closest('section') || h.closest('header') || h;
              break;
            }
          }
        }
      }
    }
    if (targetEl) {
      e.preventDefault();
      targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
      if (targetId === '#contact' || targetEl.id === 'contact') {
        const firstInput = targetEl.querySelector('input, textarea');
        if (firstInput) setTimeout(() => firstInput.focus(), 600);
      }
      const mob = document.getElementById('mobile-drawer');
      if (mob) mob.classList.remove('open');
    }
  });
});
function toggleMobileMenu() {
  const drawer = document.getElementById('mobile-drawer');
  if (drawer) drawer.classList.toggle('open');
}
const btt = document.getElementById('back-to-top');
if (btt) {
  window.addEventListener('scroll', () => {
    btt.style.display = (window.scrollY > 400) ? 'flex' : 'none';
  });
  btt.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}
function handleContactSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const name = form.querySelector('[name="name"], [type="text"]')?.value || 'Friend';
  const email = form.querySelector('[type="email"]')?.value || '';
  const btn = form.querySelector('button[type="submit"]');
  if (btn) {
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ Sending Message…';
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = origText;
      form.reset();
      let modal = document.getElementById('form-success-banner');
      if (!modal) {
        modal = document.createElement('div');
        modal.id = 'form-success-banner';
        modal.style.cssText = 'position:fixed;bottom:2rem;right:2rem;z-index:9999;background:#059669;color:#fff;padding:1.25rem 1.75rem;border-radius:14px;box-shadow:0 15px 35px rgba(0,0,0,0.3);display:flex;align-items:center;gap:1rem;font-size:0.95rem;font-weight:600;transition:all 0.3s;max-width:420px;';
        document.body.appendChild(modal);
      }
      modal.innerHTML = '<div style="font-size:1.8rem">🎉</div><div><div style="font-weight:700">Thank you, ' + name + '!</div><div style="font-size:0.85rem;opacity:0.9">Your message was delivered to {$bizName}. We will reply to ' + (email || 'your email') + ' within 24 hours.</div></div>';
      modal.style.opacity = '1';
      modal.style.transform = 'translateY(0)';
      setTimeout(() => {
        modal.style.opacity = '0';
        modal.style.transform = 'translateY(20px)';
      }, 5000);
    }, 800);
  }
}
function toggleFaq(el) {
  const body = el.nextElementSibling;
  const icon = el.querySelector('.faq-icon');
  if (body) {
    const isOpen = body.style.display === 'block';
    body.style.display = isOpen ? 'none' : 'block';
    if (icon) icon.textContent = isOpen ? '+' : '−';
  }
}
</script>
JS;
}

// ═══════════════════════════════════════════════════════════════
//  Design 1: Modern Minimal & Crisp (Light Theme)
// ═══════════════════════════════════════════════════════════════
function buildDesign1($bizName, $bizType, $bizTagline, $bizAudience, $bizServices, $bizPhone, $bizEmail, $bizAddress, $cp) {
    $servicesList = array_filter(array_map('trim', explode(',', $bizServices)));
    if (empty($servicesList)) $servicesList = ['Strategic Planning', 'Digital Excellence', 'Creative Design', 'Ongoing Support'];

    $cardsHtml = '';
    $icons = ['✦', '⚡', '❖', '◈', '★', '◉'];
    foreach ($servicesList as $idx => $s) {
        $ic = $icons[$idx % count($icons)];
        $cardsHtml .= "<div class='service-card'><div class='service-icon'>{$ic}</div><h3>" . htmlspecialchars($s) . "</h3><p>Bespoke execution and strategic delivery crafted specifically for {$bizAudience}.</p><a href='#contact' class='card-link'>Inquire Now &rarr;</a></div>";
    }
    $sharedJs = getSharedJS($bizName);
    $year = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$bizName} — {$bizTagline}</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; color: #1e293b; background: #fafbfe; line-height: 1.6; }
:root { --primary: {$cp['primary']}; --primary-gradient: {$cp['gradient']}; --primary-light: {$cp['light']}; --dark: #0f172a; }
a { text-decoration: none; color: inherit; }
.nav-wrap { position: sticky; top: 0; z-index: 100; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(12px); border-bottom: 1px solid #e2e8f0; }
.nav-container { max-width: 1200px; margin: 0 auto; padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between; }
.brand-logo { font-size: 1.3rem; font-weight: 800; color: var(--dark); display: flex; align-items: center; gap: 0.5rem; }
.brand-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--primary); }
.nav-links { display: flex; align-items: center; gap: 2rem; }
.nav-links a { font-size: 0.9rem; font-weight: 600; color: #475569; transition: color 0.2s; }
.nav-links a:hover { color: var(--primary); }
.btn-nav { padding: 0.55rem 1.25rem; border-radius: 999px; background: var(--dark); color: #fff !important; font-size: 0.85rem; font-weight: 600; transition: transform 0.2s, background 0.2s; }
.btn-nav:hover { transform: translateY(-1px); background: var(--primary); }
.hamburger { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--dark); }
#mobile-drawer { display: none; flex-direction: column; background: #fff; padding: 1.5rem; border-bottom: 1px solid #e2e8f0; gap: 1rem; font-weight: 600; }
#mobile-drawer.open { display: flex; }
.hero { padding: 6rem 1.5rem 5rem; max-width: 1200px; margin: 0 auto; text-align: center; }
.hero-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.4rem 1rem; border-radius: 999px; background: var(--primary-light); color: var(--primary); font-size: 0.82rem; font-weight: 700; margin-bottom: 1.5rem; }
.hero h1 { font-size: clamp(2.4rem, 5vw, 4rem); font-weight: 800; color: var(--dark); line-height: 1.15; max-width: 900px; margin: 0 auto 1.5rem; letter-spacing: -0.03em; }
.hero p { font-size: 1.15rem; color: #64748b; max-width: 650px; margin: 0 auto 2.5rem; line-height: 1.7; }
.hero-actions { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 3.5rem; }
.btn-primary { padding: 0.85rem 2rem; border-radius: 999px; background: var(--primary-gradient); color: #fff; font-weight: 700; font-size: 0.95rem; box-shadow: 0 10px 25px -5px rgba(99,102,241,0.4); transition: transform 0.2s, box-shadow 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 14px 30px -5px rgba(99,102,241,0.5); }
.btn-secondary { padding: 0.85rem 2rem; border-radius: 999px; background: #fff; color: var(--dark); font-weight: 700; font-size: 0.95rem; border: 1.5px solid #cbd5e1; transition: all 0.2s; }
.btn-secondary:hover { border-color: var(--primary); background: #f8fafc; }
.metrics-wrap { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; max-width: 850px; margin: 0 auto; padding: 2rem; background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
.metric-item h4 { font-size: 2rem; font-weight: 800; color: var(--dark); margin-bottom: 0.2rem; }
.metric-item p { font-size: 0.85rem; color: #64748b; font-weight: 500; }
.section { padding: 5.5rem 1.5rem; max-width: 1200px; margin: 0 auto; }
.section-header { text-align: center; max-width: 650px; margin: 0 auto 3.5rem; }
.section-tag { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--primary); letter-spacing: 0.08em; margin-bottom: 0.5rem; }
.section-title { font-size: 2.2rem; font-weight: 800; color: var(--dark); letter-spacing: -0.02em; }
.section-sub { color: #64748b; font-size: 1rem; margin-top: 0.6rem; }
.services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; }
.service-card { background: #fff; padding: 2.2rem; border-radius: 16px; border: 1px solid #e2e8f0; transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; }
.service-card:hover { transform: translateY(-4px); border-color: var(--primary); box-shadow: 0 12px 30px rgba(0,0,0,0.06); }
.service-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800; margin-bottom: 1.25rem; }
.service-card h3 { font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 0.6rem; }
.service-card p { font-size: 0.9rem; color: #64748b; margin-bottom: 1.25rem; line-height: 1.6; }
.card-link { font-size: 0.85rem; font-weight: 700; color: var(--primary); }
.about-section { background: #fff; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 5.5rem 1.5rem; }
.about-grid { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; }
.about-img-box { background: var(--primary-gradient); border-radius: 24px; padding: 3rem; color: #fff; min-height: 380px; display: flex; flex-direction: column; justify-content: flex-end; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
.about-img-box h3 { font-size: 1.8rem; font-weight: 800; line-height: 1.3; margin-bottom: 0.75rem; }
.about-img-box p { opacity: 0.9; font-size: 0.95rem; }
.about-content h2 { font-size: 2.2rem; font-weight: 800; color: var(--dark); margin-bottom: 1.25rem; }
.about-content p { color: #64748b; font-size: 1.05rem; line-height: 1.7; margin-bottom: 1.5rem; }
.contact-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 3rem; background: #fff; border-radius: 24px; padding: 3.5rem; border: 1px solid #e2e8f0; box-shadow: 0 10px 35px rgba(0,0,0,0.04); }
.c-info h3 { font-size: 1.6rem; font-weight: 800; color: var(--dark); margin-bottom: 1rem; }
.c-item { margin-top: 1.25rem; }
.c-item span { font-size: 0.8rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; display: block; margin-bottom: 0.2rem; }
.c-item strong { font-size: 1rem; color: var(--dark); }
.c-form input, .c-form textarea { width: 100%; padding: 0.85rem 1rem; border: 1.5px solid #cbd5e1; border-radius: 12px; margin-bottom: 1rem; font-family: inherit; font-size: 0.95rem; transition: border 0.2s; }
.c-form input:focus, .c-form textarea:focus { outline: none; border-color: var(--primary); }
.c-form textarea { min-height: 110px; resize: vertical; }
#back-to-top { display: none; position: fixed; bottom: 2rem; right: 2rem; z-index: 90; width: 44px; height: 44px; border-radius: 50%; background: var(--dark); color: #fff; border: none; cursor: pointer; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-size: 1.2rem; }
footer { background: var(--dark); color: #94a3b8; padding: 3.5rem 1.5rem 2rem; border-top: 1px solid #1e293b; }
.footer-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; }
.footer-links { display: flex; gap: 2rem; font-size: 0.9rem; }
.footer-links a:hover { color: #fff; }
@media (max-width: 768px) { .nav-links { display: none; } .hamburger { display: block; } .about-grid, .contact-grid { grid-template-columns: 1fr; padding: 2rem; } .metrics-wrap { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<nav class="nav-wrap">
  <div class="nav-container">
    <a href="#" class="brand-logo"><div class="brand-dot"></div>{$bizName}</a>
    <div class="nav-links">
      <a href="#services">Services</a>
      <a href="#about">About</a>
      <a href="#contact">Contact</a>
      <a href="#contact" class="btn-nav">Get in Touch</a>
    </div>
    <button class="hamburger" onclick="toggleMobileMenu()" aria-label="Toggle navigation">☰</button>
  </div>
  <div id="mobile-drawer">
    <a href="#services">Services</a>
    <a href="#about">About</a>
    <a href="#contact">Contact</a>
  </div>
</nav>

<header class="hero">
  <div class="hero-badge">✦ Tailored for {$bizAudience}</div>
  <h1>{$bizTagline}</h1>
  <p>Partnering with visionary clients to design, build, and accelerate high-performing digital solutions that produce tangible results.</p>
  <div class="hero-actions">
    <a href="#contact" class="btn-primary">Start a Project &rarr;</a>
    <a href="#services" class="btn-secondary">Explore Services</a>
  </div>
  <div class="metrics-wrap">
    <div class="metric-item"><h4>99.8%</h4><p>Client Satisfaction</p></div>
    <div class="metric-item"><h4>150+</h4><p>Successful Deliveries</p></div>
    <div class="metric-item"><h4>24/7</h4><p>Dedicated Support</p></div>
  </div>
</header>

<section class="section" id="services">
  <div class="section-header">
    <div class="section-tag">What We Do</div>
    <h2 class="section-title">Tailored Solutions for Your Growth</h2>
    <p class="section-sub">Comprehensive offerings crafted to elevate, convert, and scale your brand.</p>
  </div>
  <div class="services-grid">{$cardsHtml}</div>
</section>

<section class="about-section" id="about">
  <div class="about-grid">
    <div class="about-img-box">
      <h3>Built with precision.<br>Engineered for growth.</h3>
      <p>{$bizName} combines strategy, modern design, and technology to deliver outstanding digital experiences.</p>
    </div>
    <div class="about-content">
      <div class="section-tag">About {$bizName}</div>
      <h2>Dedicated to craft and outcome</h2>
      <p>We are a specialized {$bizType} team committed to empowering our clients with high-performing web platforms that drive real, measurable impact.</p>
      <p>From initial discovery to launch and ongoing evolution, we stay laser-focused on speed, quality, and conversion.</p>
      <a href="#contact" class="btn-primary">Work With Us</a>
    </div>
  </div>
</section>

<section class="section" id="contact">
  <div class="contact-grid">
    <div class="c-info">
      <div class="section-tag">Get In Touch</div>
      <h3>Let's build something remarkable together.</h3>
      <p style="color:#64748b;line-height:1.6">Have a question or ready to begin? Send us a note and our team will get back to you within 24 hours.</p>
      <div class="c-item"><span>Phone</span><strong>{$bizPhone}</strong></div>
      <div class="c-item"><span>Email</span><strong>{$bizEmail}</strong></div>
      <div class="c-item"><span>Office</span><strong>{$bizAddress}</strong></div>
    </div>
    <form class="c-form" onsubmit="handleContactSubmit(event)">
      <input type="text" name="name" placeholder="Your Full Name" required>
      <input type="email" name="email" placeholder="Your Email Address" required>
      <textarea name="message" placeholder="Tell us about your project..." required></textarea>
      <button type="submit" class="btn-primary" style="width:100%; border:none; cursor:pointer;">Send Message &rarr;</button>
    </form>
  </div>
</section>

<button id="back-to-top" title="Back to top">↑</button>

<footer>
  <div class="footer-container">
    <div><strong style="color:#fff;font-size:1.1rem">{$bizName}</strong><p style="font-size:0.85rem;margin-top:0.3rem">{$bizTagline}</p></div>
    <div class="footer-links"><a href="#services">Services</a><a href="#about">About</a><a href="#contact">Contact</a></div>
    <p style="font-size:0.8rem">&copy; {$year} {$bizName}. All rights reserved.</p>
  </div>
</footer>

{$sharedJs}
</body>
</html>
HTML;
}

// ═══════════════════════════════════════════════════════════════
//  Design 2: Bold Dynamic & High-Converting
// ═══════════════════════════════════════════════════════════════
function buildDesign2($bizName, $bizType, $bizTagline, $bizAudience, $bizServices, $bizPhone, $bizEmail, $bizAddress, $cp) {
    $servicesList = array_filter(array_map('trim', explode(',', $bizServices)));
    if (empty($servicesList)) $servicesList = ['Rapid Launch', 'High Conversion Strategy', 'Omnichannel Growth', 'Full Support'];

    $featCards = '';
    $emojis = ['🚀', '🔥', '💎', '📈', '🎯', '✨'];
    foreach ($servicesList as $idx => $s) {
        $em = $emojis[$idx % count($emojis)];
        $featCards .= "<div class='bold-card'><div class='bold-badge'>Feature " . ($idx + 1) . "</div><div class='bold-icon'>{$em}</div><h3>" . htmlspecialchars($s) . "</h3><p>Engineered for maximum velocity, user retention, and peak performance for {$bizAudience}.</p></div>";
    }
    $sharedJs = getSharedJS($bizName);
    $year = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$bizName} | Accelerate Your Vision</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; color: #0f172a; background: #ffffff; overflow-x: hidden; }
:root { --primary: {$cp['primary']}; --primary-gradient: {$cp['gradient']}; --primary-light: {$cp['light']}; --dark: #0f172a; }
h1, h2, h3, h4, .brand-font { font-family: 'Space Grotesk', sans-serif; }
a { text-decoration: none; color: inherit; }
.top-bar { background: var(--dark); color: #fff; font-size: 0.8rem; font-weight: 600; text-align: center; padding: 0.5rem 1rem; }
.top-bar span { color: #38bdf8; }
.nav { position: sticky; top: 0; z-index: 100; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-bottom: 2px solid #0f172a; padding: 1.1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
.logo { font-size: 1.4rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 0.4rem; }
.logo-badge { background: var(--primary-gradient); color: #fff; border-radius: 6px; padding: 0.15rem 0.5rem; font-size: 0.85rem; }
.nav-links { display: flex; align-items: center; gap: 2rem; font-weight: 600; font-size: 0.95rem; }
.nav-btn { background: var(--primary-gradient); color: #fff !important; padding: 0.6rem 1.4rem; border-radius: 12px; font-weight: 700; box-shadow: 4px 4px 0px #0f172a; border: 2px solid #0f172a; transition: transform 0.15s, box-shadow 0.15s; }
.nav-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px #0f172a; }
.hamburger { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #0f172a; }
#mobile-drawer { display: none; flex-direction: column; background: #fff; padding: 1.5rem; border-bottom: 2px solid #0f172a; gap: 1rem; font-weight: 700; }
#mobile-drawer.open { display: flex; }
.hero { padding: 5rem 2rem; background: radial-gradient(circle at 80% 20%, {$cp['light']} 0%, #ffffff 60%); }
.hero-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 3.5rem; align-items: center; }
.pill-badge { display: inline-flex; align-items: center; gap: 0.5rem; background: #f1f5f9; border: 2px solid #0f172a; padding: 0.35rem 0.9rem; border-radius: 999px; font-size: 0.82rem; font-weight: 700; box-shadow: 3px 3px 0px #0f172a; margin-bottom: 1.5rem; }
.hero h1 { font-size: clamp(2.5rem, 5vw, 4.2rem); font-weight: 800; line-height: 1.08; color: #0f172a; margin-bottom: 1.5rem; letter-spacing: -0.03em; }
.hero h1 span { background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.hero p { font-size: 1.15rem; color: #475569; line-height: 1.7; margin-bottom: 2.2rem; max-width: 580px; }
.hero-cta-group { display: flex; gap: 1.2rem; flex-wrap: wrap; }
.btn-bold { padding: 1rem 2.2rem; border-radius: 12px; background: var(--primary-gradient); color: #fff; font-size: 1rem; font-weight: 700; border: 2px solid #0f172a; box-shadow: 5px 5px 0px #0f172a; transition: transform 0.15s, box-shadow 0.15s; display: inline-flex; align-items: center; gap: 0.5rem; }
.btn-bold:hover { transform: translate(-2px, -2px); box-shadow: 7px 7px 0px #0f172a; }
.btn-outline-bold { padding: 1rem 2.2rem; border-radius: 12px; background: #fff; color: #0f172a; font-size: 1rem; font-weight: 700; border: 2px solid #0f172a; box-shadow: 5px 5px 0px #0f172a; transition: transform 0.15s, box-shadow 0.15s; }
.btn-outline-bold:hover { transform: translate(-2px, -2px); box-shadow: 7px 7px 0px #0f172a; background: #f8fafc; }
.hero-mockup { background: #0f172a; border: 3px solid #0f172a; border-radius: 20px; padding: 2rem; color: #fff; box-shadow: 12px 12px 0px var(--primary); }
.mockup-header { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; }
.mockup-dot { width: 12px; height: 12px; border-radius: 50%; background: #ef4444; }
.mockup-dot:nth-child(2) { background: #f59e0b; }
.mockup-dot:nth-child(3) { background: #10b981; }
.mockup-box { background: rgba(255,255,255,0.08); border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem; }
.mockup-stat { font-size: 2.2rem; font-weight: 800; color: #38bdf8; font-family: 'Space Grotesk'; }
.section { padding: 6rem 2rem; max-width: 1200px; margin: 0 auto; }
.section-title { font-size: 2.6rem; font-weight: 800; text-align: center; margin-bottom: 0.5rem; letter-spacing: -0.02em; }
.section-subtitle { text-align: center; color: #64748b; font-size: 1.1rem; margin-bottom: 3.5rem; }
.bold-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
.bold-card { background: #ffffff; border: 2.5px solid #0f172a; border-radius: 18px; padding: 2.2rem; box-shadow: 6px 6px 0px #0f172a; transition: transform 0.2s, box-shadow 0.2s; position: relative; }
.bold-card:hover { transform: translate(-3px, -3px); box-shadow: 9px 9px 0px #0f172a; }
.bold-badge { position: absolute; top: 1.2rem; right: 1.2rem; background: var(--primary-light); color: var(--primary); font-size: 0.72rem; font-weight: 800; padding: 0.2rem 0.6rem; border-radius: 999px; border: 1px solid var(--primary); }
.bold-icon { font-size: 2.5rem; margin-bottom: 1.25rem; }
.bold-card h3 { font-size: 1.35rem; font-weight: 700; margin-bottom: 0.6rem; }
.bold-card p { color: #64748b; font-size: 0.95rem; line-height: 1.6; }
.cta-banner { background: var(--primary-gradient); border: 3px solid #0f172a; border-radius: 24px; padding: 4.5rem 2rem; text-align: center; color: #fff; box-shadow: 10px 10px 0px #0f172a; margin: 4rem 2rem; }
.cta-banner h2 { font-size: clamp(2rem, 4vw, 3.2rem); font-weight: 800; margin-bottom: 1rem; }
.cta-banner p { font-size: 1.15rem; max-width: 600px; margin: 0 auto 2rem; opacity: 0.95; }
.btn-white { background: #ffffff; color: #0f172a; padding: 1rem 2.5rem; border-radius: 12px; font-weight: 800; font-size: 1rem; border: 2px solid #0f172a; box-shadow: 5px 5px 0px #0f172a; display: inline-block; cursor: pointer; transition: transform 0.15s; }
.btn-white:hover { transform: translate(-2px, -2px); box-shadow: 7px 7px 0px #0f172a; }
.contact-section { background: #f8fafc; border-top: 2px solid #0f172a; padding: 5rem 2rem; }
.contact-wrap { max-width: 1000px; margin: 0 auto; background: #fff; border: 3px solid #0f172a; border-radius: 20px; padding: 3rem; box-shadow: 8px 8px 0px #0f172a; display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; }
.contact-form input, .contact-form textarea { width: 100%; padding: 0.85rem; border: 2px solid #0f172a; border-radius: 10px; margin-bottom: 1rem; font-family: inherit; font-size: 0.95rem; }
.contact-form textarea { min-height: 120px; }
#back-to-top { display: none; position: fixed; bottom: 2rem; right: 2rem; z-index: 90; width: 44px; height: 44px; border-radius: 10px; background: #0f172a; color: #fff; border: 2px solid #0f172a; box-shadow: 3px 3px 0px #fff; cursor: pointer; align-items: center; justify-content: center; font-size: 1.2rem; }
footer { background: #0f172a; color: #fff; padding: 3rem 2rem; border-top: 2px solid #0f172a; text-align: center; }
@media (max-width: 850px) { .hero-container, .contact-wrap { grid-template-columns: 1fr; } .nav-links { display: none; } .hamburger { display: block; } }
</style>
</head>
<body>

<div class="top-bar">⚡ Special Launch: Discover modern capabilities built for <span>{$bizAudience}</span></div>

<nav class="nav">
  <a href="#" class="logo"><span class="logo-badge">✦</span>{$bizName}</a>
  <div class="nav-links">
    <a href="#services">Features</a>
    <a href="#contact">Contact</a>
    <a href="#contact" class="nav-btn">Get Started &rarr;</a>
  </div>
  <button class="hamburger" onclick="toggleMobileMenu()">☰</button>
</nav>

<div id="mobile-drawer">
  <a href="#services">Features</a>
  <a href="#contact">Contact</a>
</div>

<header class="hero">
  <div class="hero-container">
    <div>
      <div class="pill-badge">🚀 High-Performance {$bizType}</div>
      <h1>Accelerate your growth with <span>{$bizName}</span>.</h1>
      <p>{$bizTagline}</p>
      <div class="hero-cta-group">
        <a href="#contact" class="btn-bold">Claim Free Consultation &rarr;</a>
        <a href="#services" class="btn-outline-bold">View Capabilities</a>
      </div>
    </div>
    <div class="hero-mockup">
      <div class="mockup-header"><div class="mockup-dot"></div><div class="mockup-dot"></div><div class="mockup-dot"></div></div>
      <div class="mockup-box"><div style="font-size:0.85rem;color:#94a3b8">Growth Velocity</div><div class="mockup-stat">+340%</div></div>
      <div class="mockup-box"><div style="font-size:0.85rem;color:#94a3b8">Customer Conversion Rate</div><div class="mockup-stat" style="color:#10b981">4.8x</div></div>
      <p style="font-size:0.85rem;opacity:0.8">Empowering {$bizAudience} with outcomes that matter.</p>
    </div>
  </div>
</header>

<section class="section" id="services">
  <h2 class="section-title">High-Impact Capabilities</h2>
  <p class="section-subtitle">Everything you need to outpace competitors and turn visitors into loyal advocates.</p>
  <div class="bold-grid">{$featCards}</div>
</section>

<div class="cta-banner">
  <h2>Ready to transform your results?</h2>
  <p>Join forward-thinking partners who rely on {$bizName} for top-tier execution.</p>
  <a href="#contact" class="btn-white">Book Your Discovery Call &rarr;</a>
</div>

<div class="contact-section" id="contact">
  <div class="contact-wrap">
    <div>
      <h2 style="font-size:2rem;margin-bottom:1rem">Let's Connect</h2>
      <p style="color:#64748b;margin-bottom:2rem">Reach out directly and our specialists will formulate a personalized growth plan for your team.</p>
      <p style="margin-bottom:0.75rem"><strong>📞 Phone:</strong> {$bizPhone}</p>
      <p style="margin-bottom:0.75rem"><strong>📧 Email:</strong> {$bizEmail}</p>
      <p><strong>📍 Address:</strong> {$bizAddress}</p>
    </div>
    <form class="contact-form" onsubmit="handleContactSubmit(event)">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email Address" required>
      <textarea name="message" placeholder="How can we help you?" required></textarea>
      <button type="submit" class="btn-bold" style="width:100%; cursor:pointer;">Send Message Now &rarr;</button>
    </form>
  </div>
</div>

<button id="back-to-top" title="Back to top">↑</button>

<footer>
  <p style="font-weight:700;font-size:1.1rem;margin-bottom:0.5rem">{$bizName}</p>
  <p style="font-size:0.85rem;color:#94a3b8">&copy; {$year} {$bizName}. Designed to convert.</p>
</footer>

{$sharedJs}
</body>
</html>
HTML;
}

// ═══════════════════════════════════════════════════════════════
//  Design 3: Executive Luxury & Dark Mode
// ═══════════════════════════════════════════════════════════════
function buildDesign3($bizName, $bizType, $bizTagline, $bizAudience, $bizServices, $bizPhone, $bizEmail, $bizAddress, $cp) {
    $servicesList = array_filter(array_map('trim', explode(',', $bizServices)));
    if (empty($servicesList)) $servicesList = ['Bespoke Architecture', 'Private Advisory', 'Autonomous Systems', 'Elite Support'];

    $luxCards = '';
    $symbols = ['◈', '✦', '❖', '★', '◉', '▲'];
    foreach ($servicesList as $idx => $s) {
        $sy = $symbols[$idx % count($symbols)];
        $luxCards .= "<div class='glass-card'><div class='glass-icon'>{$sy}</div><h3>" . htmlspecialchars($s) . "</h3><p>Uncompromising craftsmanship and tailored precision tailored specifically for {$bizAudience}.</p></div>";
    }
    $sharedJs = getSharedJS($bizName);
    $year = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$bizName} — Executive Suite</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', -apple-system, sans-serif; color: #f1f5f9; background: #090d16; line-height: 1.7; overflow-x: hidden; }
:root { --primary: {$cp['primary']}; --primary-gradient: {$cp['gradient']}; --primary-accent: {$cp['accent']}; --gold: #f59e0b; }
h1, h2, .brand-font { font-family: 'Cinzel', serif; letter-spacing: 0.04em; }
a { text-decoration: none; color: inherit; }
.nav-bar { position: sticky; top: 0; z-index: 100; background: rgba(9, 13, 22, 0.88); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding: 1.25rem 2rem; display: flex; justify-content: space-between; align-items: center; }
.brand-title { font-size: 1.35rem; font-weight: 700; color: #fff; letter-spacing: 0.12em; text-transform: uppercase; display: flex; align-items: center; gap: 0.6rem; }
.brand-gem { width: 8px; height: 8px; transform: rotate(45deg); background: var(--primary); box-shadow: 0 0 10px var(--primary); }
.nav-menu { display: flex; gap: 2.5rem; align-items: center; font-size: 0.85rem; letter-spacing: 0.05em; text-transform: uppercase; color: #94a3b8; }
.nav-menu a:hover { color: #fff; }
.btn-lux { padding: 0.65rem 1.6rem; border-radius: 4px; background: rgba(255, 255, 255, 0.06); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); font-size: 0.8rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; transition: all 0.3s; }
.btn-lux:hover { background: #fff; color: #090d16; box-shadow: 0 0 20px rgba(255,255,255,0.4); }
.hamburger { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #fff; }
#mobile-drawer { display: none; flex-direction: column; background: #090d16; padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); gap: 1rem; font-size: 0.9rem; text-transform: uppercase; }
#mobile-drawer.open { display: flex; }
.hero { min-height: 88vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 6rem 2rem; position: relative; }
.hero::before { content: ''; position: absolute; top: 20%; left: 50%; transform: translate(-50%, -20%); width: 600px; height: 600px; border-radius: 50%; background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%); pointer-events: none; filter: blur(50px); }
.hero-content { max-width: 880px; position: relative; z-index: 2; }
.hero-pre { font-size: 0.8rem; font-weight: 700; letter-spacing: 0.25em; text-transform: uppercase; color: var(--primary); margin-bottom: 1.5rem; }
.hero h1 { font-size: clamp(2.4rem, 5.5vw, 4.4rem); font-weight: 700; color: #fff; line-height: 1.15; margin-bottom: 1.5rem; }
.hero p { font-size: 1.15rem; color: #94a3b8; max-width: 650px; margin: 0 auto 2.5rem; font-weight: 300; line-height: 1.8; }
.btn-gold { display: inline-block; padding: 0.95rem 2.5rem; border-radius: 4px; background: var(--primary-gradient); color: #fff; font-size: 0.85rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; box-shadow: 0 0 25px rgba(99,102,241,0.35); transition: all 0.3s; }
.btn-gold:hover { transform: translateY(-2px); box-shadow: 0 0 35px rgba(99,102,241,0.6); }
.section { padding: 6rem 2rem; max-width: 1200px; margin: 0 auto; position: relative; }
.section-tag { font-size: 0.75rem; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: var(--primary); text-align: center; margin-bottom: 0.6rem; }
.section-head { font-size: 2.2rem; text-align: center; color: #fff; margin-bottom: 3.5rem; }
.glass-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 1.8rem; }
.glass-card { background: rgba(255, 255, 255, 0.025); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 2.5rem; backdrop-filter: blur(12px); transition: all 0.3s; }
.glass-card:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.25); transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
.glass-icon { font-size: 1.8rem; color: var(--primary); margin-bottom: 1.25rem; }
.glass-card h3 { font-size: 1.15rem; font-weight: 600; color: #fff; margin-bottom: 0.75rem; letter-spacing: 0.02em; }
.glass-card p { font-size: 0.9rem; color: #94a3b8; line-height: 1.7; }
.contact-glass { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.09); border-radius: 16px; padding: 4rem 3rem; max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1.2fr; gap: 3rem; }
.contact-glass input, .contact-glass textarea { width: 100%; padding: 0.9rem 1.1rem; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 6px; color: #fff; margin-bottom: 1rem; font-family: inherit; font-size: 0.9rem; }
.contact-glass input:focus, .contact-glass textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 10px rgba(99,102,241,0.3); }
#back-to-top { display: none; position: fixed; bottom: 2rem; right: 2rem; z-index: 90; width: 44px; height: 44px; border-radius: 4px; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); cursor: pointer; align-items: center; justify-content: center; font-size: 1.2rem; }
footer { border-top: 1px solid rgba(255, 255, 255, 0.06); padding: 3.5rem 2rem; text-align: center; font-size: 0.85rem; color: #64748b; }
@media (max-width: 768px) { .nav-menu { display: none; } .hamburger { display: block; } .contact-glass { grid-template-columns: 1fr; padding: 2rem; } }
</style>
</head>
<body>

<nav class="nav-bar">
  <div class="brand-title"><div class="brand-gem"></div>{$bizName}</div>
  <div class="nav-menu">
    <a href="#services">Offerings</a>
    <a href="#contact">Inquiries</a>
    <a href="#contact" class="btn-lux">Consultation</a>
  </div>
  <button class="hamburger" onclick="toggleMobileMenu()">☰</button>
</nav>

<div id="mobile-drawer">
  <a href="#services">Offerings</a>
  <a href="#contact">Inquiries</a>
</div>

<header class="hero">
  <div class="hero-content">
    <div class="hero-pre">Bespoke {$bizType} Solutions</div>
    <h1>{$bizTagline}</h1>
    <p>Distinguished digital craftsmanship tailored for {$bizAudience} who demand unmatched sophistication and execution.</p>
    <a href="#contact" class="btn-gold">Request Private Briefing &rarr;</a>
  </div>
</header>

<section class="section" id="services">
  <div class="section-tag">Distinctive Capabilities</div>
  <h2 class="section-head">Crafted for Discerning Standards</h2>
  <div class="glass-grid">{$luxCards}</div>
</section>

<section class="section" id="contact">
  <div class="contact-glass">
    <div>
      <div class="section-tag" style="text-align:left">Inquiries</div>
      <h2 style="font-size:1.8rem;color:#fff;margin:0.5rem 0 1rem">Connect Privately</h2>
      <p style="color:#94a3b8;font-size:0.95rem;margin-bottom:1.5rem">Direct correspondence with our lead partners.</p>
      <p style="margin-bottom:0.75rem"><strong>Direct:</strong> {$bizPhone}</p>
      <p style="margin-bottom:0.75rem"><strong>Confidential:</strong> {$bizEmail}</p>
      <p><strong>Headquarters:</strong> {$bizAddress}</p>
    </div>
    <form onsubmit="handleContactSubmit(event)">
      <input type="text" name="name" placeholder="Your Distinguished Name" required>
      <input type="email" name="email" placeholder="Professional Email" required>
      <textarea name="message" rows="4" placeholder="Brief statement of requirements..." required></textarea>
      <button type="submit" class="btn-gold" style="width:100%; border:none; cursor:pointer;">Submit Inquiry</button>
    </form>
  </div>
</section>

<button id="back-to-top" title="Back to top">↑</button>

<footer>
  <p style="letter-spacing:0.1em;text-transform:uppercase">&copy; {$year} {$bizName}. All rights reserved.</p>
</footer>

{$sharedJs}
</body>
</html>
HTML;
}

// ═══════════════════════════════════════════════════════════════
//  ACTION: save_design
// ═══════════════════════════════════════════════════════════════
if ($action === 'save_design') {
    $bizName  = htmlspecialchars(trim($req['biz_name'] ?? 'Website'));
    $html     = $req['html'] ?? '';
    $designId = 'DSN-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

    $designsDir = defined('STORAGE_DIR') ? (STORAGE_DIR . '/designs') : (dirname(__DIR__) . '/storage/designs');
    if (!is_dir($designsDir)) mkdir($designsDir, 0755, true);
    file_put_contents($designsDir . '/' . $designId . '.html', $html);

    echo json_encode(['success' => true, 'design_id' => $designId, 'saved_at' => date('Y-m-d H:i:s')]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ACTION: generate_3
// ═══════════════════════════════════════════════════════════════
if ($action === 'generate_3' || $action === 'generate') {
    $design1 = buildDesign1($bizName, $bizType, $bizTagline, $bizAudience, $bizServices, $bizPhone, $bizEmail, $bizAddress, $cp);
    $design2 = buildDesign2($bizName, $bizType, $bizTagline, $bizAudience, $bizServices, $bizPhone, $bizEmail, $bizAddress, $cp);
    $design3 = buildDesign3($bizName, $bizType, $bizTagline, $bizAudience, $bizServices, $bizPhone, $bizEmail, $bizAddress, $cp);

    echo json_encode([
        'success' => true,
        'designs' => [
            ['id' => 1, 'name' => 'Concept 1 — Modern Minimal & Crisp', 'badge' => 'Clean & Professional',
             'description' => 'Light aesthetic with refined typography, balanced whitespace, interactive smooth navigation, and crisp subtle cards.',
             'style' => 'light', 'html' => $design1],
            ['id' => 2, 'name' => 'Concept 2 — Bold Dynamic & High-Converting', 'badge' => 'High-Impact & Conversion-Focused',
             'description' => 'Vibrant gradient accents, striking metrics badges, neo-brutalist buttons, dynamic contact flow, and energetic layout.',
             'style' => 'vibrant', 'html' => $design2],
            ['id' => 3, 'name' => 'Concept 3 — Executive Luxury & Dark Mode', 'badge' => 'Sleek Dark Glassmorphism',
             'description' => 'Deep dark aesthetics, glowing ambient highlights, frosted glass panels, and luxury typography for high-end brands.',
             'style' => 'dark', 'html' => $design3]
        ],
        'default_html' => $design1
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ACTION: update_details  (with change detection)
// ═══════════════════════════════════════════════════════════════
if ($action === 'update_details') {
    $currentHTML = $req['current_html'] ?? '';
    $details = $req['details'] ?? [];
    if (empty($currentHTML)) {
        echo json_encode(['success' => false, 'error' => 'Missing HTML']);
        exit;
    }

    $modified = $currentHTML;
    $changeCount = 0;

    if (!empty($details['phone'])) {
        $newPhone = $sanitize($details['phone']);
        $after = preg_replace('/(\+?[\d\s\-\(\)]{9,20})/', $newPhone, $modified, 4);
        if ($after !== $modified) { $modified = $after; $changeCount++; }
    }

    if (!empty($details['email'])) {
        $newEmail = $sanitize($details['email']);
        $after = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $newEmail, $modified, 4);
        if ($after !== $modified) { $modified = $after; $changeCount++; }
    }

    if (!empty($details['address'])) {
        $newAddr = $sanitize($details['address']);
        $after = preg_replace('/(<strong>Headquarters:<\/strong>\s*|<span>Office<\/span>\s*<strong>|<strong>📍 Address:<\/strong>\s*)[^<]+/i', '$1' . $newAddr, $modified, 2);
        if ($after !== $modified) { $modified = $after; $changeCount++; }
    }

    if (!empty($details['tagline'])) {
        $newTag = $sanitize($details['tagline']);
        $after = preg_replace('/(<header[^>]*>[\s\S]*?<h1[^>]*>)([\s\S]*?)(<\/h1>)/i', '$1' . $newTag . '$3', $modified, 1);
        if ($after !== $modified) { $modified = $after; $changeCount++; }
    }

    if (!empty($details['cta_text'])) {
        $newCta = $sanitize($details['cta_text']);
        $after = preg_replace('/(<a[^>]*class=["\'][^"\']*(?:btn-primary|btn-bold|btn-gold)[^"\']*["\'][^>]*>)([\s\S]*?)(<\/a>)/i', '$1' . $newCta . ' &rarr;$3', $modified, 1);
        if ($after !== $modified) { $modified = $after; $changeCount++; }
    }

    if (!empty($details['new_service'])) {
        $newSvc = $sanitize($details['new_service']);
        $newCard = "<div class='service-card'><div class='service-icon'>✦</div><h3>{$newSvc}</h3><p>Tailored high-impact solutions built to deliver measurable excellence and sustainable outcomes.</p><a href='#contact' class='card-link'>Inquire Now &rarr;</a></div>";
        $after = preg_replace('/(<\/div>\s*<\/section>)/i', $newCard . '$1', $modified, 1);
        if ($after !== $modified) { $modified = $after; $changeCount++; }
    }

    if ($changeCount === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'None of the provided details could be applied — the fields may already contain those values.',
            'html' => $currentHTML
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => true, 'html' => $modified, 'source' => 'batch_updater', 'changes' => $changeCount]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ACTION: rewrite_text  (with honest fallback)
// ═══════════════════════════════════════════════════════════════
if ($action === 'rewrite_text') {
    $text = trim($req['text'] ?? '');
    $tone = trim($req['tone'] ?? 'punchy');
    $instruction = trim($req['instruction'] ?? '');

    if (empty($text)) {
        echo json_encode(['success' => false, 'error' => 'No text provided to rewrite']);
        exit;
    }

    if (!empty($apiKey) && strlen($apiKey) > 10) {
        $prompt = "You are an elite conversion copywriter for websites. Rewrite the following text to make it more engaging.\n\n"
                . "ORIGINAL TEXT: \"{$text}\"\n"
                . "DESIRED TONE: {$tone}\n"
                . (!empty($instruction) ? "SPECIFIC INSTRUCTION: {$instruction}\n" : "")
                . "REQUIREMENTS:\n"
                . "1. Return ONLY the rewritten text string.\n"
                . "2. Do NOT output quotation marks around the text, markdown formatting, or commentary.\n"
                . "3. Keep roughly similar length unless the user specifically requested otherwise.";

        list($ok, $aiText, $code, $errMsg) = callGemini($apiKey, $resolvedModel, $prompt, 1024);
        if ($ok && !empty($aiText)) {
            $rewritten = trim(trim($aiText, "\"'` \n\r\t"));
            if (!empty($rewritten)) {
                echo json_encode(['success' => true, 'rewritten_text' => $rewritten, 'source' => 'gemini'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }

    // Smart offline rewrite
    $rewritten = $text;
    if ($tone === 'punchy') {
        $rewritten = "Engineered for Growth: " . rtrim($text, '.') . " — Built to Outperform.";
    } elseif ($tone === 'professional') {
        $rewritten = "Delivering strategic " . strtolower(rtrim($text, '.')) . " with proven industry excellence.";
    } elseif ($tone === 'luxury') {
        $rewritten = "The pinnacle of bespoke distinction: " . rtrim($text, '.') . ".";
    } elseif ($tone === 'friendly') {
        $rewritten = "We make " . strtolower(rtrim($text, '.')) . " simple, seamless, and rewarding.";
    } elseif ($tone === 'concise') {
        $words = explode(' ', $text);
        $rewritten = implode(' ', array_slice($words, 0, min(count($words), 6)));
    } else {
        $rewritten = "Transforming " . strtolower(rtrim($text, '.')) . " into measurable success.";
    }

    echo json_encode(['success' => true, 'rewritten_text' => $rewritten, 'source' => 'smart_engine'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ACTION: refine  — main AI-chat handler WITH CHANGE DETECTION
// ═══════════════════════════════════════════════════════════════
if ($action === 'refine') {
    $currentHTML = $req['current_html'] ?? '';
    $instruction = strip_tags(trim($req['instruction'] ?? ''));

    if (empty($currentHTML) || empty($instruction)) {
        echo json_encode(['success' => false, 'error' => 'Missing HTML or instruction']);
        exit;
    }

    $geminiError = null;

    // ── 1. Try Gemini first ──
    if (!empty($apiKey) && strlen($apiKey) > 10) {
        $prompt = "You are Google Gemini, an elite full-stack web developer and UI/UX designer. "
                . "The user wants to refine their single-file HTML website with the following instruction.\n\n"
                . "USER INSTRUCTION: {$instruction}\n\n"
                . "CRITICAL REQUIREMENTS:\n"
                . "1. Output the COMPLETE valid single-file HTML document (start with <!DOCTYPE html> and end with </html>).\n"
                . "2. Preserve all existing CSS styling, responsive layout (mobile/tablet/desktop), and colors unless explicitly asked to change.\n"
                . "3. Preserve all working JavaScript event handlers (smooth scroll, contact form submission banner, mobile menu drawer, FAQ accordion, back-to-top button).\n"
                . "4. Seamlessly incorporate the requested updates or sections with modern typography, smooth spacing, and accessible semantic HTML.\n"
                . "5. Return ONLY the raw HTML. Do NOT include markdown fences (no ```html). Output only HTML.\n\n"
                . "CURRENT HTML:\n" . substr($currentHTML, 0, 45000);

        list($ok, $aiText, $code, $errMsg) = callGemini($apiKey, $resolvedModel, $prompt, 32000);

        if (!$ok) {
            $geminiError = $errMsg;
            error_log('[generate.php refine] Gemini failed: ' . $errMsg);
        } else {
            // Strip markdown fences if Gemini added them anyway
            $aiText = preg_replace('/^```(?:html)?\s*/i', '', trim($aiText));
            $aiText = preg_replace('/\s*```$/', '', $aiText);
            $aiText = trim($aiText);

            // Must be valid HTML
            $validHtml = (stripos($aiText, '<!DOCTYPE') !== false || stripos($aiText, '<html') !== false)
                      && stripos($aiText, '</html>') !== false;

            if ($validHtml) {
                // ⚠️ CRITICAL: verify Gemini actually changed something
                if (trim($aiText) === trim($currentHTML)) {
                    // Gemini returned the same HTML — treat as failure so user knows
                    echo json_encode([
                        'success' => false,
                        'error' => 'Gemini returned the same HTML — no changes were applied. Try rephrasing your request more specifically (e.g. "Add a pricing section with 3 plans").',
                        'html' => $currentHTML,
                        'source' => 'gemini_no_change'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                echo json_encode([
                    'success' => true,
                    'html' => $aiText,
                    'source' => 'gemini',
                    'response_msg' => "✨ Gemini applied your refinement: \"{$instruction}\"."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $geminiError = 'Gemini returned malformed HTML (missing closing tags or invalid structure).';
            error_log('[generate.php refine] Malformed HTML from Gemini. First 200 chars: ' . substr($aiText, 0, 200));
        }
    } else {
        $geminiError = 'No Gemini API key configured.';
    }

    // ── 2. Smart engine fallback WITH change tracking ──
    $modified = $currentHTML;
    $lower = strtolower($instruction);
    $changed = false;
    $responseMsg = '';

    // 1. Phone
    if (str_contains($lower, 'phone') || str_contains($lower, 'call') || str_contains($lower, 'number') || str_contains($lower, 'tel')) {
        if (preg_match('/(\+?[\d\s\-\(\)]{8,22})/', $instruction, $m) && !empty($m[1])) {
            $newPhone = trim($m[1]);
            $after = preg_replace('/(\+?[\d\s\-\(\)]{9,22})/', $newPhone, $modified, 4);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Updated phone number to {$newPhone}."; }
        }
    }

    // 2. Email
    if (str_contains($lower, 'email') || str_contains($lower, 'mail')) {
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $instruction, $m) && !empty($m[1])) {
            $newEmail = trim($m[1]);
            $after = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $newEmail, $modified, 4);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Updated contact email to {$newEmail}."; }
        }
    }

    // 3. Address
    if (str_contains($lower, 'address') || str_contains($lower, 'location') || str_contains($lower, 'headquarters') || str_contains($lower, 'city')) {
        if (preg_match('/(?:to|in|at)\s+([A-Za-z0-9\s,.-]{4,40})/i', $instruction, $m) && !empty($m[1])) {
            $newAddr = trim($m[1]);
            $after = preg_replace('/(<strong>Headquarters:<\/strong>\s*|<span>Office<\/span>\s*<strong>|<strong>📍 Address:<\/strong>\s*)[^<]+/i', '$1' . $newAddr, $modified, 2);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Updated address to \"{$newAddr}\"."; }
        }
    }

    // 4. Headline / tagline
    if (str_contains($lower, 'headline') || str_contains($lower, 'tagline') || str_contains($lower, 'title') || str_contains($lower, 'heading')) {
        $newTitle = '';
        if (preg_match('/["“\']([^"”\']+)["”\']/u', $instruction, $m)) $newTitle = trim($m[1]);
        elseif (preg_match('/(?:to|as)\s+(.+)$/i', $instruction, $m)) $newTitle = trim($m[1]);
        if (!empty($newTitle)) {
            $after = preg_replace('/(<header[^>]*>[\s\S]*?<h1[^>]*>)([\s\S]*?)(<\/h1>)/i', '$1' . htmlspecialchars($newTitle) . '$3', $modified, 1);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Updated hero headline to: \"{$newTitle}\"."; }
        }
    }

    // 5. CTA button text
    if (str_contains($lower, 'button') || str_contains($lower, 'cta')) {
        $newBtn = '';
        if (preg_match('/["“\']([^"”\']+)["”\']/u', $instruction, $m)) $newBtn = trim($m[1]);
        elseif (preg_match('/(?:to|as|saying)\s+(.+)$/i', $instruction, $m)) $newBtn = trim($m[1]);
        if (!empty($newBtn)) {
            $cleanBtn = htmlspecialchars($newBtn);
            $after = preg_replace('/(<a[^>]*class=["\'][^"\']*(?:btn-primary|btn-bold|btn-gold)[^"\']*["\'][^>]*>)([\s\S]*?)(<\/a>)/i', '$1' . $cleanBtn . ' &rarr;$3', $modified, 1);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Updated primary button text to: \"{$cleanBtn}\"."; }
        }
    }

    // 6. Testimonials / reviews
    if (str_contains($lower, 'testimonial') || str_contains($lower, 'review') || str_contains($lower, 'feedback') || str_contains($lower, 'social proof') || str_contains($lower, 'rating')) {
        if (!str_contains($modified, 'id="testimonials"')) {
            $testiHtml = '
            <section class="section" id="testimonials" style="padding:5rem 1.5rem;background:rgba(99,102,241,0.03);border-top:1px solid rgba(226,232,240,0.4);border-bottom:1px solid rgba(226,232,240,0.4)">
              <div style="text-align:center;max-width:700px;margin:0 auto 3rem">
                <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;color:var(--primary);letter-spacing:0.1em;margin-bottom:0.4rem">Client Feedback</div>
                <h2 style="font-size:2.4rem;font-weight:800;letter-spacing:-0.02em">Trusted by Visionary Leaders</h2>
                <p style="color:#64748b;margin-top:0.5rem">See how our solutions deliver measurable excellence and outstanding growth for our partners.</p>
              </div>
              <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;max-width:1150px;margin:0 auto">
                <div style="background:rgba(255,255,255,0.85);backdrop-filter:blur(10px);border:1.5px solid rgba(226,232,240,0.8);border-radius:18px;padding:2rem;box-shadow:0 10px 30px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between">
                  <div><div style="color:#f59e0b;font-size:1.1rem;margin-bottom:1rem">★★★★★</div>
                  <p style="color:#334155;line-height:1.6;font-size:0.95rem;font-style:italic">"The velocity and design quality exceeded our highest expectations. Our conversions jumped 48% within the first month of launching."</p></div>
                  <div style="display:flex;align-items:center;gap:0.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid #e2e8f0">
                    <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem">SJ</div>
                    <div><div style="font-weight:700;color:#0f172a;font-size:0.92rem">Sarah Jenkins</div><div style="color:#64748b;font-size:0.8rem">VP of Marketing, NexaCorp</div></div>
                  </div>
                </div>
                <div style="background:rgba(255,255,255,0.85);backdrop-filter:blur(10px);border:1.5px solid var(--primary);border-radius:18px;padding:2rem;box-shadow:0 15px 35px rgba(99,102,241,0.12);display:flex;flex-direction:column;justify-content:space-between;position:relative">
                  <span style="position:absolute;top:-12px;right:20px;background:var(--primary);color:#fff;font-size:0.72rem;font-weight:700;padding:0.25rem 0.75rem;border-radius:999px">FEATURED REVIEW</span>
                  <div><div style="color:#f59e0b;font-size:1.1rem;margin-bottom:1rem">★★★★★</div>
                  <p style="color:#334155;line-height:1.6;font-size:0.95rem;font-style:italic">"Working together was seamless. They understood our brand immediately and engineered a platform that sets us years ahead of competitors."</p></div>
                  <div style="display:flex;align-items:center;gap:0.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid #e2e8f0">
                    <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#059669,#10b981);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem">DC</div>
                    <div><div style="font-weight:700;color:#0f172a;font-size:0.92rem">David Chen</div><div style="color:#64748b;font-size:0.8rem">Co-Founder, Strata Global</div></div>
                  </div>
                </div>
                <div style="background:rgba(255,255,255,0.85);backdrop-filter:blur(10px);border:1.5px solid rgba(226,232,240,0.8);border-radius:18px;padding:2rem;box-shadow:0 10px 30px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between">
                  <div><div style="color:#f59e0b;font-size:1.1rem;margin-bottom:1rem">★★★★★</div>
                  <p style="color:#334155;line-height:1.6;font-size:0.95rem;font-style:italic">"The attention to mobile responsiveness and speed optimization is unmatched. Truly world-class digital craftsmanship."</p></div>
                  <div style="display:flex;align-items:center;gap:0.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid #e2e8f0">
                    <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#06b6d4);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem">ER</div>
                    <div><div style="font-weight:700;color:#0f172a;font-size:0.92rem">Elena Rostova</div><div style="color:#64748b;font-size:0.8rem">Managing Director, Lumina Labs</div></div>
                  </div>
                </div>
              </div>
            </section>';
            $after = str_replace('<section class="section" id="contact">', $testiHtml . "\n" . '<section class="section" id="contact">', $modified);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Added a stunning 5-star Customer Testimonials & Reviews section!"; }
        }
    }

    // 7. Team / leadership
    if (str_contains($lower, 'team') || str_contains($lower, 'leadership') || str_contains($lower, 'founder') || str_contains($lower, 'staff')) {
        if (!str_contains($modified, 'id="team"')) {
            $teamHtml = '
            <section class="section" id="team" style="padding:5rem 1.5rem;text-align:center">
              <div style="max-width:700px;margin:0 auto 3rem">
                <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;color:var(--primary);letter-spacing:0.1em;margin-bottom:0.4rem">The Experts</div>
                <h2 style="font-size:2.4rem;font-weight:800;letter-spacing:-0.02em">Meet Our Leadership Team</h2>
                <p style="color:#64748b;margin-top:0.5rem">Passionate industry specialists committed to delivering exceptional digital results.</p>
              </div>
              <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;max-width:1050px;margin:0 auto">
                <div style="background:rgba(255,255,255,0.7);border:1px solid #e2e8f0;border-radius:18px;padding:2rem 1.5rem;text-align:center">
                  <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;font-size:1.8rem;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-weight:800">AT</div>
                  <h3 style="font-size:1.15rem;font-weight:700;color:#0f172a">Alexander Thorne</h3>
                  <div style="color:var(--primary);font-size:0.85rem;font-weight:600;margin-top:0.2rem">CEO &amp; Head of Strategy</div>
                  <p style="font-size:0.85rem;color:#64748b;margin-top:0.75rem;line-height:1.5">12+ years directing high-impact initiatives for global enterprise clients.</p>
                </div>
                <div style="background:rgba(255,255,255,0.7);border:1px solid #e2e8f0;border-radius:18px;padding:2rem 1.5rem;text-align:center">
                  <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#059669,#10b981);color:#fff;font-size:1.8rem;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-weight:800">MV</div>
                  <h3 style="font-size:1.15rem;font-weight:700;color:#0f172a">Maya Vance</h3>
                  <div style="color:var(--primary);font-size:0.85rem;font-weight:600;margin-top:0.2rem">Creative Director</div>
                  <p style="font-size:0.85rem;color:#64748b;margin-top:0.75rem;line-height:1.5">Award-winning designer crafting intuitive, human-centered experiences.</p>
                </div>
                <div style="background:rgba(255,255,255,0.7);border:1px solid #e2e8f0;border-radius:18px;padding:2rem 1.5rem;text-align:center">
                  <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#06b6d4);color:#fff;font-size:1.8rem;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-weight:800">LK</div>
                  <h3 style="font-size:1.15rem;font-weight:700;color:#0f172a">Liam Kendrick</h3>
                  <div style="color:var(--primary);font-size:0.85rem;font-weight:600;margin-top:0.2rem">Lead Solutions Architect</div>
                  <p style="font-size:0.85rem;color:#64748b;margin-top:0.75rem;line-height:1.5">Full-stack systems specialist ensuring military-grade stability and speed.</p>
                </div>
              </div>
            </section>';
            $after = str_replace('<section class="section" id="contact">', $teamHtml . "\n" . '<section class="section" id="contact">', $modified);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Added Meet Our Leadership Team section!"; }
        }
    }

    // 8. WhatsApp
    if (str_contains($lower, 'whatsapp') || str_contains($lower, 'chat button')) {
        if (!str_contains($modified, 'whatsapp-floating-btn')) {
            $waHtml = '<a href="https://wa.me/15551234567" target="_blank" id="whatsapp-floating-btn" style="position:fixed;bottom:2rem;left:2rem;z-index:99;background:#25D366;color:#fff;padding:0.75rem 1.25rem;border-radius:999px;font-weight:700;font-size:0.9rem;box-shadow:0 10px 25px rgba(37,211,102,0.4);display:flex;align-items:center;gap:0.5rem;text-decoration:none"><span style="font-size:1.2rem">💬</span> Chat on WhatsApp</a>';
            $after = str_replace('</body>', $waHtml . "\n</body>", $modified);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Added floating WhatsApp chat button!"; }
        }
    }

    // 9. FAQ
    if (str_contains($lower, 'faq') || str_contains($lower, 'questions') || str_contains($lower, 'accordion')) {
        if (!str_contains($modified, 'id="faq"')) {
            $faqHtml = '
            <section class="section" id="faq" style="padding:5.5rem 1.5rem;max-width:950px;margin:0 auto">
              <div style="text-align:center;margin-bottom:3rem">
                <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;color:var(--primary);letter-spacing:0.1em;margin-bottom:0.4rem">Common Inquiries</div>
                <h2 style="font-size:2.4rem;font-weight:800;letter-spacing:-0.02em">Frequently Asked Questions</h2>
              </div>
              <div style="display:flex;flex-direction:column;gap:1rem">
                <div style="background:rgba(255,255,255,0.04);border:1.5px solid #cbd5e1;border-radius:14px;padding:1.25rem 1.75rem;cursor:pointer" onclick="toggleFaq(this)">
                  <div style="display:flex;justify-content:space-between;align-items:center;font-weight:700;font-size:1.05rem"><span>What is the typical turnaround timeline for a website project?</span><span class="faq-icon" style="font-size:1.4rem;color:var(--primary)">+</span></div>
                  <div style="display:none;margin-top:0.85rem;color:#64748b;font-size:0.95rem;line-height:1.6">Our average turnaround is between 2 to 4 weeks, with expedited 7-day launch schedules available for fast-track initiatives.</div>
                </div>
                <div style="background:rgba(255,255,255,0.04);border:1.5px solid #cbd5e1;border-radius:14px;padding:1.25rem 1.75rem;cursor:pointer" onclick="toggleFaq(this)">
                  <div style="display:flex;justify-content:space-between;align-items:center;font-weight:700;font-size:1.05rem"><span>Is full responsive mobile optimization included?</span><span class="faq-icon" style="font-size:1.4rem;color:var(--primary)">+</span></div>
                  <div style="display:none;margin-top:0.85rem;color:#64748b;font-size:0.95rem;line-height:1.6">Yes, 100% of our builds are mobile-first, ensuring high speeds, crisp accessibility, and seamless journeys on phones, tablets, and desktops.</div>
                </div>
                <div style="background:rgba(255,255,255,0.04);border:1.5px solid #cbd5e1;border-radius:14px;padding:1.25rem 1.75rem;cursor:pointer" onclick="toggleFaq(this)">
                  <div style="display:flex;justify-content:space-between;align-items:center;font-weight:700;font-size:1.05rem"><span>How does payment and milestone scheduling work?</span><span class="faq-icon" style="font-size:1.4rem;color:var(--primary)">+</span></div>
                  <div style="display:none;margin-top:0.85rem;color:#64748b;font-size:0.95rem;line-height:1.6">We provide flexible terms, including 50% down / 50% on completion, or monthly subscription plans tailored to your budget.</div>
                </div>
              </div>
            </section>';
            $after = str_replace('<section class="section" id="contact">', $faqHtml . "\n" . '<section class="section" id="contact">', $modified);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Added interactive FAQ accordion!"; }
        }
    }

    // 10. Client logos
    if (str_contains($lower, 'logo') || str_contains($lower, 'partner') || str_contains($lower, 'trusted by')) {
        if (!str_contains($modified, 'id="partners-strip"')) {
            $partnersHtml = '<div id="partners-strip" style="padding:2.5rem 1.5rem;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;text-align:center"><div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:0.1em;margin-bottom:1.25rem">Trusted by industry leaders worldwide</div><div style="display:flex;justify-content:center;gap:3rem;flex-wrap:wrap;align-items:center;opacity:0.75;font-weight:800;font-size:1.15rem;color:#475569"><span>✦ ACME CORP</span><span>⚡ VELOCITY AI</span><span>◈ NEXUS LABS</span><span>❖ ORBITAL MEDIA</span><span>★ STRATA GLOBAL</span></div></div>';
            $after = str_replace('</header>', '</header>' . "\n" . $partnersHtml, $modified);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Added client brand logos strip!"; }
        }
    }

    // 11. Pricing
    if (str_contains($lower, 'pricing') || str_contains($lower, 'plans') || str_contains($lower, 'tier') || str_contains($lower, 'packages')) {
        if (!str_contains(strtolower($modified), 'id="pricing"')) {
            $pricingHtml = '
            <section class="section" id="pricing" style="padding:5.5rem 1.5rem;text-align:center">
              <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;color:var(--primary);letter-spacing:0.1em;margin-bottom:0.4rem">Transparent Investment</div>
              <h2 style="font-size:2.4rem;font-weight:800;margin-bottom:0.6rem;letter-spacing:-0.02em">Flexible Pricing Plans</h2>
              <p style="color:#64748b;margin-bottom:3.5rem;max-width:550px;margin-left:auto;margin-right:auto">Choose the tier that fits your growth ambitions.</p>
              <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;max-width:1050px;margin:0 auto">
                <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:18px;padding:2.5rem 2rem;color:#0f172a">
                  <h3 style="font-size:1.2rem;font-weight:700">Starter</h3>
                  <div style="font-size:2.6rem;font-weight:800;margin:1rem 0;color:var(--dark)">$499</div>
                  <p style="color:#64748b;font-size:0.9rem;margin-bottom:1.5rem">Essential digital presence</p>
                  <a href="#contact" class="btn-secondary" style="display:block;text-align:center;padding:0.75rem;border-radius:999px">Choose Starter</a>
                </div>
                <div style="background:#fff;border:2.5px solid var(--primary);border-radius:18px;padding:2.5rem 2rem;box-shadow:0 15px 40px rgba(99,102,241,0.18);color:#0f172a;position:relative">
                  <span style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--primary);color:#fff;font-size:0.75rem;font-weight:700;padding:0.25rem 0.85rem;border-radius:999px">MOST POPULAR</span>
                  <h3 style="font-size:1.2rem;font-weight:700">Growth Scale</h3>
                  <div style="font-size:2.6rem;font-weight:800;margin:1rem 0;color:var(--primary)">$999</div>
                  <p style="color:#64748b;font-size:0.9rem;margin-bottom:1.5rem">Complete custom experience</p>
                  <a href="#contact" class="btn-primary" style="display:block;text-align:center;padding:0.75rem;border-radius:999px">Choose Growth &rarr;</a>
                </div>
                <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:18px;padding:2.5rem 2rem;color:#0f172a">
                  <h3 style="font-size:1.2rem;font-weight:700">Enterprise</h3>
                  <div style="font-size:2.6rem;font-weight:800;margin:1rem 0;color:var(--dark)">$1,899</div>
                  <p style="color:#64748b;font-size:0.9rem;margin-bottom:1.5rem">Full custom engineering</p>
                  <a href="#contact" class="btn-secondary" style="display:block;text-align:center;padding:0.75rem;border-radius:999px">Choose Enterprise</a>
                </div>
              </div>
            </section>';
            $after = str_replace('<section class="section" id="contact">', $pricingHtml . "\n" . '<section class="section" id="contact">', $modified);
            if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Added 3-tier pricing table!"; }
        }
    }

    // 12. Dark / light mode
    if (str_contains($lower, 'dark') || str_contains($lower, 'night') || str_contains($lower, 'black')) {
        $after = str_replace(['#ffffff', '#fafbfe', '#f8fafc', 'background: #ffffff;', 'background: #fff;'], ['#090d16', '#090d16', '#0f172a', 'background: #090d16;', 'background: #090d16;'], $modified);
        $after = str_replace(['color: #1e293b;', 'color: #0f172a;'], 'color: #f1f5f9;', $after);
        if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Switched entire website to executive dark mode!"; }
    } elseif (str_contains($lower, 'light') || str_contains($lower, 'clean mode') || str_contains($lower, 'white')) {
        $after = str_replace(['#090d16', '#0f172a', '#111622'], ['#ffffff', '#f8fafc', '#ffffff'], $modified);
        $after = str_replace(['color: #f1f5f9;', 'color: #f8fafc;'], 'color: #0f172a;', $after);
        if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Switched theme to clean light mode!"; }
    }

    // 13. Color palette changes
    if (str_contains($lower, 'blue') || str_contains($lower, 'cyan') || str_contains($lower, 'ocean')) {
        $after = str_replace(['#6366f1', '#a855f7'], ['#2563eb', '#06b6d4'], $modified);
        $after = preg_replace('/--primary:\s*[^;]+;/', '--primary: #2563eb;', $after);
        if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Updated color palette to Ocean Blue!"; }
    } elseif (str_contains($lower, 'green') || str_contains($lower, 'emerald')) {
        $after = str_replace(['#6366f1', '#a855f7', '#2563eb'], ['#059669', '#10b981', '#10b981'], $modified);
        $after = preg_replace('/--primary:\s*[^;]+;/', '--primary: #059669;', $after);
        if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Updated color palette to Emerald Green!"; }
    } elseif (str_contains($lower, 'red') || str_contains($lower, 'crimson') || str_contains($lower, 'orange')) {
        $after = str_replace(['#6366f1', '#a855f7'], ['#dc2626', '#f97316'], $modified);
        $after = preg_replace('/--primary:\s*[^;]+;/', '--primary: #dc2626;', $after);
        if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Updated color palette to Crimson Red!"; }
    } elseif (str_contains($lower, 'gold') || str_contains($lower, 'amber') || str_contains($lower, 'luxury')) {
        $after = str_replace(['#6366f1', '#a855f7'], ['#d97706', '#f59e0b'], $modified);
        $after = preg_replace('/--primary:\s*[^;]+;/', '--primary: #d97706;', $after);
        if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Updated color palette to Luxury Gold!"; }
    } elseif (str_contains($lower, 'purple') || str_contains($lower, 'violet') || str_contains($lower, 'indigo')) {
        $after = str_replace(['#2563eb', '#06b6d4', '#059669'], ['#6366f1', '#a855f7', '#6366f1'], $modified);
        $after = preg_replace('/--primary:\s*[^;]+;/', '--primary: #6366f1;', $after);
        if ($after !== $modified) { $modified = $after; $changed = true; $responseMsg = "Updated color palette to Royal Indigo!"; }
    }

    // ═══ CRITICAL FIX: If nothing changed, tell the truth ═══
    if (!$changed) {
        $hint = '';
        if ($geminiError) {
            $hint = "Gemini failed: {$geminiError}. ";
        }
        echo json_encode([
            'success' => false,
            'error' => $hint . "I couldn't figure out how to apply \"{$instruction}\" to this website. Try a specific instruction like:\n" .
                "• \"Add a pricing table with 3 tiers\"\n" .
                "• \"Add 5-star customer reviews\"\n" .
                "• \"Add an FAQ section\"\n" .
                "• \"Change phone to +1 555 123 4567\"\n" .
                "• \"Switch to dark mode\" or \"Change color to blue\"",
            'html' => $currentHTML,
            'source' => 'no_change'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'html' => $modified,
        'source' => 'smart_engine',
        'response_msg' => $responseMsg
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);