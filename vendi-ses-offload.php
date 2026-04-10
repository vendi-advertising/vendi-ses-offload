<?php
/**
 * Plugin Name: Vendi SES Offload
 * Description: Routes all WordPress email through Amazon SES using the SESv2 API.
 * Version: 1.0.1
 * Author: Vendi Advertising
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Load scoped vendor autoloader.
$autoloader = __DIR__ . '/build/vendor/autoload.php';
if (!file_exists($autoloader)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p><strong>Vendi SES Offload:</strong> Scoped vendor build not found. Run the build script in the plugin directory.</p></div>';
    });
    return;
}
require_once $autoloader;

// Register our own PSR-4 autoloader for Vendi\SesOffload\ classes.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Vendi\\SesOffload\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// ── Database table ─────────────────────────────────────────────────────────

register_activation_hook(__FILE__, [\Vendi\SesOffload\Database::class, 'install']);
add_action('admin_init', [\Vendi\SesOffload\Database::class, 'maybeUpgrade']);

// ── Configuration ──────────────────────────────────────────────────────────
// All four constants must be defined in wp-config.php. The plugin will not
// activate without them.

$vendiSesRequired = [
    'VENDI_SES_ACCESS_KEY_ID',
    'VENDI_SES_SECRET_ACCESS_KEY',
    'VENDI_SES_REGION',
    'VENDI_SES_FROM',
];

$vendiSesMissing = array_filter($vendiSesRequired, static fn (string $name): bool => !defined($name));

if ($vendiSesMissing) {
    add_action('admin_notices', static function () use ($vendiSesMissing): void {
        $constants = implode('</code>, <code>', $vendiSesMissing);
        echo '<div class="notice notice-error"><p><strong>Vendi SES Offload:</strong> '
            . 'The following constants must be defined in <code>wp-config.php</code> before this plugin can send email: '
            . '<code>' . $constants . '</code></p></div>';
    });
    return;
}

// ── Mailer instance ────────────────────────────────────────────────────────

$vendiSesMailer = new \Vendi\SesOffload\SesMailer(
    VENDI_SES_ACCESS_KEY_ID,
    VENDI_SES_SECRET_ACCESS_KEY,
    VENDI_SES_REGION,
    VENDI_SES_FROM,
);

// ── Hook into wp_mail via pre_wp_mail (WP 5.9+) ───────────────────────────
// Returning a non-null value short-circuits the default wp_mail() entirely.

add_filter('pre_wp_mail', static function ($null, array $atts) use ($vendiSesMailer) {
    $to = $atts['to'];
    $subject = $atts['subject'];
    $message = $atts['message'];
    $headers = $atts['headers'];
    $attachments = $atts['attachments'];

    // Normalize for storage.
    $toStr = is_array($to) ? implode(', ', $to) : $to;
    $headersStr = is_array($headers) ? implode("\n", $headers) : $headers;
    $attachmentsStr = is_array($attachments) ? implode("\n", $attachments) : $attachments;

    $sent = $vendiSesMailer->send($to, $subject, $message, $headers, $attachments);

    $error = $sent ? null : (string) get_transient('vendi_ses_last_error');

    \Vendi\SesOffload\Database::logEmail(
        $toStr,
        $subject,
        $message,
        $headersStr,
        $attachmentsStr,
        $sent ? 'sent' : 'failed',
        $error,
    );

    return $sent;
}, 10, 2);

// ── Admin test page ────────────────────────────────────────────────────────

if (is_admin()) {
    \Vendi\SesOffload\AdminPage::register();
}
