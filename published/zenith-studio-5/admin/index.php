<?php
require_once __DIR__ . '/layout.php';
renderAdminHeader('dashboard', 'Dashboard');

$contentData = file_exists(__DIR__ . '/content.json') ? json_decode(file_get_contents(__DIR__ . '/content.json'), true) : [];
$authData = getAuthData();
?>
<div class="top-actions">
  <div>
    <h1 class="page-title">Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?> 👋</h1>
    <p style="color:#64748b;font-size:.88rem;margin-top:.3rem">Manage your website content, customer records, and settings here.</p>
  </div>
  <a href="../index.html" target="_blank" class="btn btn-primary">
    <span>🌐</span> View Live Website &rarr;
  </a>
</div>

<!-- Stat Cards -->
<div class="grid4">
  <div class="stat-box">
    <div class="stat-lbl">Website Status</div>
    <div class="stat-val" style="color:#10b981">Live 🟢</div>
    <div style="font-size:.75rem;color:#64748b">Hosted &amp; Published</div>
  </div>
  <div class="stat-box">
    <div class="stat-lbl">SSL Certificate</div>
    <div class="stat-val" style="color:#a5b4fc">Active 🔒</div>
    <div style="font-size:.75rem;color:#64748b">HTTPS Secured</div>
  </div>
  <div class="stat-box">
    <div class="stat-lbl">Content Sections</div>
    <div class="stat-val">6</div>
    <div style="font-size:.75rem;color:#64748b">Hero, Services, Contact &amp; More</div>
  </div>
  <div class="stat-box">
    <div class="stat-lbl">Account</div>
    <div class="stat-val" style="font-size:1.25rem"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin') ?></div>
    <div style="font-size:.75rem;color:#64748b"><a href="settings.php" style="color:#818cf8;text-decoration:none">Change Password &rarr;</a></div>
  </div>
</div>

<!-- Quick Actions -->
<h2 style="font-size:1.15rem;font-weight:800;color:#fff;margin-bottom:1rem">⚡ Quick Actions</h2>
<div class="grid2" style="margin-bottom:2.5rem">
  <div class="card" style="margin-bottom:0">
    <div style="font-size:1.8rem;margin-bottom:.75rem">📝</div>
    <h3 style="font-size:1.1rem;font-weight:800;color:#fff;margin-bottom:.35rem">Edit Website Content</h3>
    <p style="color:#94a3b8;font-size:.85rem;line-height:1.55;margin-bottom:1.25rem">
      Update your business name, phone number, email, address, hero banner, and about text directly on your live website.
    </p>
    <a href="content.php" class="btn btn-primary">Open Content Editor &rarr;</a>
  </div>

  <div class="card" style="margin-bottom:0">
    <div style="font-size:1.8rem;margin-bottom:.75rem">📋</div>
    <h3 style="font-size:1.1rem;font-weight:800;color:#fff;margin-bottom:.35rem">Manage Business Data</h3>
    <p style="color:#94a3b8;font-size:.85rem;line-height:1.55;margin-bottom:1.25rem">
      Add, edit, or delete items such as services, products, customer inquiries, and appointments with simple forms.
    </p>
    <a href="manage.php" class="btn btn-ghost">Manage Records &rarr;</a>
  </div>
</div>

<!-- Live Info Card -->
<div class="card">
  <h3 style="font-size:1.05rem;font-weight:800;color:#fff;margin-bottom:1rem">ℹ️ Your Website Details</h3>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;font-size:.85rem">
    <div><strong style="color:#64748b">Business Name:</strong> <span style="color:#fff"><?= htmlspecialchars($contentData['business_name'] ?? 'Zenith Studio') ?></span></div>
    <div><strong style="color:#64748b">Recovery Email:</strong> <span style="color:#fff"><?= htmlspecialchars($authData['email'] ?? 'Not set') ?></span></div>
    <div><strong style="color:#64748b">Primary Phone:</strong> <span style="color:#fff"><?= htmlspecialchars($contentData['phone'] ?? 'Not set') ?></span></div>
    <div><strong style="color:#64748b">Admin URL:</strong> <code style="color:#34d399;font-size:.8rem"><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?></code></div>
  </div>
</div>

<?php renderAdminFooter(); ?>