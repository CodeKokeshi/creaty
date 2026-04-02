<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: admin/notifications/');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$routeBase = $routeBase ?? 'admin/';
$assetBase = $assetBase ?? '';

if (!isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])) {
    header('Location: ' . $routeBase);
    exit;
}

$accountLabel = 'Admin';
$adminHomePath = $routeBase . 'dashboard/';
$logoutPath = $routeBase . 'logout.php';
$notificationsPath = $routeBase . 'notifications/';

require_once __DIR__ . '/config/message_notifications_repository.php';

$notifications = load_message_notifications_repository();
$markReadResult = mark_all_message_notifications_as_read($notifications);

if ($markReadResult['changed']) {
    save_message_notifications_repository($markReadResult['notifications']);
}

$notifications = $markReadResult['notifications'];
$adminNotificationCount = count_unread_message_notifications($notifications);

function format_message_notification_datetime(string $value): string
{
    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return 'Unknown time';
    }

    return date('M d, Y g:i A', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20260402-1">
</head>
<body class="events-page admin-notifications-page">
    <header class="site-header">
        <div class="topbar topbar-admin">
            <a class="brand-badge" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </a>

            <form class="topbar-search landing-search" action="#" method="get">
                <input type="search" name="q" placeholder="Search notifications">
            </form>

            <div class="topbar-admin-actions">
                <a
                    class="topbar-notification-button"
                    href="<?php echo htmlspecialchars($notificationsPath, ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="Notifications"
                    title="Notifications"
                    data-admin-notification-trigger
                    data-notification-count="<?php echo htmlspecialchars((string) $adminNotificationCount, ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <span class="topbar-notification-text">Notifications</span>
                    <span class="topbar-notification-icon-wrap" aria-hidden="true">
                        <img class="topbar-notification-icon" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/notifications.svg" alt="">
                        <span class="cart-count topbar-notification-count" aria-hidden="true"><?php echo htmlspecialchars((string) $adminNotificationCount, ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                </a>

                <div class="dropdown topbar-account-menu">
                    <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">Admin Home</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($assetBase . 'archive/', ENT_QUOTES, 'UTF-8'); ?>">Archived</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <nav class="section-nav section-nav-disabled" aria-label="Notifications view">
            <span class="section-nav-filter is-disabled" aria-disabled="true">INBOX</span>
            <span class="section-nav-section is-disabled" aria-disabled="true">MESSAGE US</span>
            <span class="section-nav-filter is-disabled" aria-disabled="true">ALL READ</span>
        </nav>
    </header>

    <main class="admin-notifications-shell">
        <section class="admin-notifications-panel reveal" aria-labelledby="admin-notifications-title">
            <div class="admin-notifications-head">
                <h1 id="admin-notifications-title">Message Notifications</h1>
                <p>Customer messages submitted through Message Us appear here.</p>
            </div>

            <?php if (!$notifications): ?>
                <p class="admin-notifications-empty">No notifications yet.</p>
            <?php else: ?>
                <div class="admin-notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                            $senderName = trim((string) ($notification['sender_name'] ?? 'Unknown customer'));
                            $senderEmail = trim((string) ($notification['sender_email'] ?? 'No email provided'));
                            $subject = trim((string) ($notification['subject'] ?? 'Untitled message'));
                            $messageBody = trim((string) ($notification['message'] ?? ''));
                            $attachments = is_array($notification['attachments'] ?? null) ? $notification['attachments'] : [];
                            $createdAt = format_message_notification_datetime((string) ($notification['created_at'] ?? ''));
                        ?>
                        <article class="admin-notification-item<?php echo !empty($notification['is_read']) ? ' is-read' : ''; ?>">
                            <div class="admin-notification-meta">
                                <span class="admin-notification-sender"><?php echo htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="admin-notification-email"><?php echo htmlspecialchars($senderEmail, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="admin-notification-time"><?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>

                            <h2><?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p class="admin-notification-message"><?php echo nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8')); ?></p>

                            <?php if ($attachments): ?>
                                <div class="admin-notification-attachments" role="list" aria-label="Attached images">
                                    <?php foreach ($attachments as $attachmentPath): ?>
                                        <?php
                                            $relativeAttachmentPath = ltrim((string) $attachmentPath, '/');
                                            $attachmentUrl = $assetBase . $relativeAttachmentPath;
                                        ?>
                                        <a
                                            class="admin-notification-attachment"
                                            href="<?php echo htmlspecialchars($attachmentUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            role="listitem"
                                        >
                                            <img src="<?php echo htmlspecialchars($attachmentUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Message attachment">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260402-1"></script>
</body>
</html>
