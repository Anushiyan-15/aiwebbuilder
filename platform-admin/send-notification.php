<?php
/**
 * Platform Admin - Send Notification Handler (send-notification.php)
 * Handles POST requests to send/save notifications.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/platform-admin.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: customers.php');
    exit;
}

$order_id = trim($_POST['order_id'] ?? '');
$redirect = 'customer-view.php?order_id=' . urlencode($order_id);

if (!$order_id) {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_msg']  = 'Invalid order ID.';
    header('Location: customers.php');
    exit;
}

$order = load_order($order_id);
if (!$order) {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_msg']  = 'Order not found.';
    header('Location: customers.php');
    exit;
}

// Validate inputs
$allowed_types = ['payment_reminder', 'warning', 'info'];
$notif_type    = in_array($_POST['notif_type'] ?? '', $allowed_types)
                 ? $_POST['notif_type'] : 'info';
$notif_message = trim($_POST['notif_message'] ?? '');

if ($notif_message === '') {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_msg']  = 'Notification message cannot be empty.';
    header('Location: ' . $redirect);
    exit;
}

// Build the notification record
$notif = [
    'type'    => $notif_type,
    'message' => $notif_message,
    'sent_by' => $_SESSION['platform_admin_user'] ?? 'admin',
    'sent_at' => date('Y-m-d H:i:s'),
];

// Save to storage/notifications/{order_id}.json
$saved = save_notification($order_id, $notif);

// Attempt email delivery
$customer_email = filter_var($order['admin_email'] ?? '', FILTER_VALIDATE_EMAIL);
$email_sent     = false;

if ($customer_email) {
    $subject_map = [
        'payment_reminder' => 'Payment Reminder — WebCraft AI Builder',
        'warning'          => 'Important Notice — WebCraft AI Builder',
        'info'             => 'Notification — WebCraft AI Builder',
    ];
    $subject = $subject_map[$notif_type] ?? 'Notification — WebCraft AI Builder';

    $site_name = $order['site_name'] ?? 'your website';
    $body      = "Hello,\n\n" . $notif_message . "\n\n";
    $body     .= "---\nSite: " . $site_name . "\n";
    $body     .= "Order: " . $order_id . "\n";
    $body     .= "Live URL: " . ($order['live_url'] ?? 'N/A') . "\n\n";
    $body     .= "If you have any questions, reply to this email or contact support@webcraft.ai\n\n";
    $body     .= "— WebCraft AI Builder Team";

    $headers  = implode("\r\n", [
        'From: WebCraft AI Builder <noreply@webcraft.ai>',
        'Reply-To: support@webcraft.ai',
        'X-Mailer: WebCraft-Platform-Admin/1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'MIME-Version: 1.0',
    ]);

    $email_sent = @mail($customer_email, $subject, $body, $headers);
}

// Set flash and redirect
if ($saved) {
    $_SESSION['flash_type'] = 'success';
    $_SESSION['flash_msg']  = 'Notification saved' . ($email_sent ? ' and email sent' : ' (email delivery depends on server config)') . '.';
} else {
    $_SESSION['flash_type'] = 'warning';
    $_SESSION['flash_msg']  = 'Could not save notification to disk. Check storage/notifications/ write permissions.';
}

header('Location: ' . $redirect);
exit;
