<?php
require_once __DIR__ . '/layout.php';

$authFile = __DIR__ . '/.auth.json';
$authData = file_exists($authFile) ? json_decode(file_get_contents($authFile), true) : [];

$successMsg = '';
$errorMsg = '';

// Handle Password Change POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $currPass = trim($_POST['current_password'] ?? '');
        $newPass  = trim($_POST['new_password'] ?? '');
        $confPass = trim($_POST['confirm_password'] ?? '');

        // Verify current password
        $storedHash  = $authData['hash'] ?? '';
        $storedPlain = $authData['password_plain'] ?? '';

        $currValid = false;
        if ($storedHash && password_verify($currPass, $storedHash)) {
            $currValid = true;
        } elseif ($storedPlain && $currPass === $storedPlain) {
            $currValid = true;
        }

        if (!$currValid) {
            $errorMsg = 'Current password is incorrect. Please check and try again.';
        } elseif (strlen($newPass) < 6) {
            $errorMsg = 'New password must be at least 6 characters long.';
        } elseif ($newPass !== $confPass) {
            $errorMsg = 'New passwords do not match. Please re-enter.';
        } else {
            // Update .auth.json
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $authData['hash'] = $newHash;
            $authData['password_plain'] = $newPass;
            $authData['updated_at'] = date('Y-m-d H:i:s');
            file_put_contents($authFile, json_encode($authData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Also synchronize with storage/orders if order_id is present
            $orderId = $authData['order_id'] ?? '';
            if ($orderId) {
                $orderDir = dirname(__DIR__, 2) . '/storage/orders';
                $orderFile = $orderDir . '/' . $orderId . '.json';
                if (file_exists($orderFile)) {
                    $order = json_decode(file_get_contents($orderFile), true);
                    if ($order) {
                        $order['admin_password_hash'] = $newHash;
                        $order['admin_password_plain'] = $newPass;
                        file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    }
                }
            }

            $successMsg = 'Your password has been changed successfully!';
        }
    }

    if ($action === 'update_profile') {
        $newEmail = trim($_POST['email'] ?? '');
        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $authData['email'] = $newEmail;
            file_put_contents($authFile, json_encode($authData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $successMsg = 'Profile email updated successfully!';
        } else {
            $errorMsg = 'Please enter a valid email address.';
        }
    }
}

renderAdminHeader('settings', 'Settings & Password');
?>
<div class="top-actions">
  <div>
    <h1 class="page-title">⚙️ Settings &amp; Security</h1>
    <p style="color:#64748b;font-size:.88rem;margin-top:.3rem">Change your password and manage login credentials for this website.</p>
  </div>
</div>

<?php if ($successMsg): ?>
  <div style="background:rgba(16,185,129,.15);border:1px solid #10b981;color:#34d399;padding:1rem 1.25rem;border-radius:12px;font-weight:700;margin-bottom:1.5rem">
    ✅ <?= htmlspecialchars($successMsg) ?>
  </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
  <div style="background:rgba(239,68,68,.15);border:1px solid #ef4444;color:#fca5a5;padding:1rem 1.25rem;border-radius:12px;font-weight:700;margin-bottom:1.5rem">
    ⚠️ <?= htmlspecialchars($errorMsg) ?>
  </div>
<?php endif; ?>

<div class="grid2">
  <!-- CHANGE PASSWORD CARD -->
  <div class="card">
    <h2 style="font-size:1.15rem;font-weight:800;color:#fff;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem">
      <span>🔑</span> Change Password
    </h2>
    <form method="POST">
      <input type="hidden" name="action" value="change_password">

      <div class="field">
        <label class="lbl" for="current_password">Current Password</label>
        <input type="password" name="current_password" id="current_password" class="inp" required placeholder="Enter current password">
      </div>

      <div class="field">
        <label class="lbl" for="new_password">New Password</label>
        <input type="password" name="new_password" id="new_password" class="inp" required minlength="6" placeholder="At least 6 characters">
      </div>

      <div class="field">
        <label class="lbl" for="confirm_password">Confirm New Password</label>
        <input type="password" name="confirm_password" id="confirm_password" class="inp" required minlength="6" placeholder="Re-enter new password">
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.5rem">
        Update Password Now &rarr;
      </button>
    </form>
  </div>

  <!-- PROFILE / ACCOUNT CARD -->
  <div class="card">
    <h2 style="font-size:1.15rem;font-weight:800;color:#fff;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem">
      <span>👤</span> Account Details
    </h2>
    <form method="POST">
      <input type="hidden" name="action" value="update_profile">

      <div class="field">
        <label class="lbl">Admin Username</label>
        <input type="text" class="inp" value="<?= htmlspecialchars($authData['username'] ?? 'admin') ?>" disabled style="opacity:.6;cursor:not-allowed">
        <div style="font-size:.75rem;color:#64748b;margin-top:.3rem">Username is fixed to your site installation.</div>
      </div>

      <div class="field">
        <label class="lbl" for="email">Recovery Email</label>
        <input type="email" name="email" id="email" class="inp" value="<?= htmlspecialchars($authData['email'] ?? '') ?>" required placeholder="you@example.com">
        <div style="font-size:.75rem;color:#64748b;margin-top:.3rem">Used for password recovery and notifications.</div>
      </div>

      <button type="submit" class="btn btn-ghost" style="width:100%;margin-top:.5rem">
        Save Profile Email
      </button>
    </form>
  </div>
</div>

<?php renderAdminFooter(); ?>