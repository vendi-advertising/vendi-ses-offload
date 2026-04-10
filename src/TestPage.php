<?php

declare(strict_types=1);

namespace Vendi\SesOffload;

final class TestPage
{
    public static function renderContent(): void
    {
        $result = null;
        if (isset($_GET['vendi-ses-result'])) {
            $result = $_GET['vendi-ses-result'] === 'success';
        }
        $error = get_transient('vendi_ses_last_error');
        if ($error) {
            delete_transient('vendi_ses_last_error');
        }

        ?>
        <?php if ($result === true): ?>
            <div class="notice notice-success"><p>Test email sent successfully.</p></div>
        <?php elseif ($result === false): ?>
            <div class="notice notice-error"><p>Failed to send test email.<?php if ($error): ?> Error: <?php echo esc_html($error); ?><?php endif; ?></p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('vendi_ses_test_nonce', '_vendi_ses_nonce'); ?>
            <input type="hidden" name="action" value="vendi_ses_send_test">

            <table class="form-table">
                <tr>
                    <th><label for="vendi_ses_to">To</label></th>
                    <td><input type="email" id="vendi_ses_to" name="to" value="chris@vendiadvertising.com" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="vendi_ses_subject">Subject</label></th>
                    <td><input type="text" id="vendi_ses_subject" name="subject" value="Vendi SES Test Email" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="vendi_ses_message">Message</label></th>
                    <td><textarea id="vendi_ses_message" name="message" rows="5" class="large-text">This is a test email sent via the Vendi SES Offload plugin.</textarea></td>
                </tr>
            </table>

            <?php submit_button('Send Test Email'); ?>
        </form>
        <?php
    }

    public static function handleTestSend(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('vendi_ses_test_nonce', '_vendi_ses_nonce');

        $to = sanitize_email($_POST['to'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (!$to || !$subject || !$message) {
            wp_safe_redirect(AdminPage::tabUrl('test', ['vendi-ses-result' => 'fail']));
            exit;
        }

        $sent = wp_mail($to, $subject, $message);

        wp_safe_redirect(AdminPage::tabUrl('test', [
            'vendi-ses-result' => $sent ? 'success' : 'fail',
        ]));
        exit;
    }
}
