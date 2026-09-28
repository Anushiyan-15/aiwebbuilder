<?php
// ═══════════════════════════════════════════════════════════════
//  includes/db.php — Universal Database Helper (Supabase & MySQL)
// ═══════════════════════════════════════════════════════════════

require_once dirname(__DIR__) . '/config/database.php';

function getDb(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = defined('DB_DRIVER') ? DB_DRIVER : 'pgsql';

    // 1. Primary Supabase PostgreSQL Connection
    if ($driver === 'pgsql') {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 6,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            error_log('Supabase PostgreSQL Connection Error: ' . $e->getMessage());
        }
    }

    // 2. Fallback to Local MySQL if configured
    if (defined('LOCAL_MYSQL_HOST')) {
        try {
            $dsn = "mysql:host=" . LOCAL_MYSQL_HOST . ";dbname=" . LOCAL_MYSQL_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, LOCAL_MYSQL_USER, LOCAL_MYSQL_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 3,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            error_log('Local MySQL Connection Error: ' . $e->getMessage());
        }
    }

    return null;
}

/**
 * Synchronize an order array to Supabase / MySQL
 */
function syncOrderToDatabase(array $order): bool {
    $db = getDb();
    if (!$db) return false;

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $oid    = $order['order_id'] ?? '';
    if (!$oid) return false;

    $slug        = $order['slug'] ?? '';
    $siteName    = $order['site_name'] ?? 'Website';
    $package     = $order['package'] ?? 'Pro';
    $amount      = (float)($order['amount'] ?? 19.00);
    $status      = $order['status'] ?? 'published';
    $siteActive  = !empty($order['site_active']) ? 1 : 0;
    $liveUrl     = $order['live_url'] ?? '';
    $adminUrl    = $order['admin_url'] ?? '';
    $adminUser   = $order['admin_username'] ?? '';
    $adminEmail  = $order['admin_email'] ?: ($order['client_email'] ?? '');
    $nextPayment = $order['next_payment_due'] ?? date('Y-m-d', strtotime('+30 days'));
    $publishedAt = $order['published_at'] ?? date('Y-m-d H:i:s');
    $genMode     = $order['gen_mode'] ?? 'static';
    $payCapture  = $order['paypal_capture_id'] ?? null;
    $payMethod   = $order['payment_method'] ?? 'paypal';

    try {
        if ($driver === 'pgsql') {
            // Supabase PostgreSQL syntax
            $sql = "
                INSERT INTO orders (
                    order_id, paypal_capture_id, site_name, slug, gen_mode,
                    package, amount, currency, payment_method, payment_status,
                    status, site_active, live_url, admin_url, published_slug,
                    admin_username, admin_email, next_payment_due, published_at, updated_at
                ) VALUES (
                    :order_id, :paypal_capture_id, :site_name, :slug, :gen_mode,
                    :package, :amount, 'USD', :payment_method, 'completed',
                    :status, :site_active, :live_url, :admin_url, :slug,
                    :admin_username, :admin_email, :next_payment_due, :published_at, NOW()
                )
                ON CONFLICT (order_id) DO UPDATE SET
                    site_name        = EXCLUDED.site_name,
                    slug             = EXCLUDED.slug,
                    package          = EXCLUDED.package,
                    amount           = EXCLUDED.amount,
                    status           = EXCLUDED.status,
                    site_active      = EXCLUDED.site_active,
                    live_url         = EXCLUDED.live_url,
                    admin_url        = EXCLUDED.admin_url,
                    admin_email      = EXCLUDED.admin_email,
                    next_payment_due = EXCLUDED.next_payment_due,
                    published_at     = EXCLUDED.published_at,
                    updated_at       = NOW()
            ";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':order_id'          => $oid,
                ':paypal_capture_id' => $payCapture,
                ':site_name'         => $siteName,
                ':slug'              => $slug,
                ':gen_mode'          => $genMode,
                ':package'           => $package,
                ':amount'            => $amount,
                ':payment_method'    => $payMethod,
                ':status'            => $status,
                ':site_active'       => $siteActive ? 'true' : 'false',
                ':live_url'          => $liveUrl,
                ':admin_url'         => $adminUrl,
                ':admin_username'    => $adminUser,
                ':admin_email'       => $adminEmail,
                ':next_payment_due'  => $nextPayment,
                ':published_at'      => $publishedAt,
            ]);
        } else {
            // MySQL syntax
            $sql = "
                INSERT INTO orders (
                    order_id, paypal_capture_id, site_name, slug, gen_mode,
                    package, amount, currency, payment_method, payment_status,
                    status, site_active, live_url, admin_url, published_slug,
                    admin_username, admin_email, next_payment_due, published_at, created_at, updated_at
                ) VALUES (
                    :order_id, :paypal_capture_id, :site_name, :slug, :gen_mode,
                    :package, :amount, 'USD', :payment_method, 'completed',
                    :status, :site_active, :live_url, :admin_url, :slug,
                    :admin_username, :admin_email, :next_payment_due, :published_at, NOW(), NOW()
                )
                ON DUPLICATE KEY UPDATE
                    site_name        = VALUES(site_name),
                    slug             = VALUES(slug),
                    package          = VALUES(package),
                    amount           = VALUES(amount),
                    status           = VALUES(status),
                    site_active      = VALUES(site_active),
                    live_url         = VALUES(live_url),
                    admin_url        = VALUES(admin_url),
                    admin_email      = VALUES(admin_email),
                    next_payment_due = VALUES(next_payment_due),
                    published_at     = VALUES(published_at),
                    updated_at       = NOW()
            ";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':order_id'          => $oid,
                ':paypal_capture_id' => $payCapture,
                ':site_name'         => $siteName,
                ':slug'              => $slug,
                ':gen_mode'          => $genMode,
                ':package'           => $package,
                ':amount'            => $amount,
                ':payment_method'    => $payMethod,
                ':status'            => $status,
                ':site_active'       => $siteActive,
                ':live_url'          => $liveUrl,
                ':admin_url'         => $adminUrl,
                ':admin_username'    => $adminUser,
                ':admin_email'       => $adminEmail,
                ':next_payment_due'  => $nextPayment,
                ':published_at'      => $publishedAt,
            ]);
        }
    } catch (Throwable $e) {
        error_log('syncOrderToDatabase Error: ' . $e->getMessage());
        return false;
    }
}
