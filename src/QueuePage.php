<?php

declare(strict_types=1);

namespace Vendi\SesOffload;

final class QueuePage
{
    public static function renderContent(): void
    {
        global $wpdb;

        $result = isset($_GET['vendi-ses-result']) ? sanitize_text_field($_GET['vendi-ses-result']) : null;
        $resultId = isset($_GET['vendi-ses-id']) ? (int) $_GET['vendi-ses-id'] : null;
        $resultCount = isset($_GET['vendi-ses-count']) ? (int) $_GET['vendi-ses-count'] : null;
        $wasOverride = isset($_GET['vendi-ses-override']) && sanitize_text_field($_GET['vendi-ses-override']) === '1';
        $error = get_transient('vendi_ses_last_error');
        if ($error) {
            delete_transient('vendi_ses_last_error');
        }

        // Pagination
        $perPage = 20;
        $currentPage = max(1, (int) ($_GET['paged'] ?? 1));
        $offset = ($currentPage - 1) * $perPage;

        // Filter by status
        $statusFilter = sanitize_text_field($_GET['status'] ?? 'failed');
        $validStatuses = ['sent', 'failed', 'all'];
        if (!in_array($statusFilter, $validStatuses, true)) {
            $statusFilter = 'failed';
        }

        $table = Database::getTableName();

        $where = $statusFilter === 'all' ? '1=1' : $wpdb->prepare('status = %s', $statusFilter);
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where}");
        $totalPages = (int) ceil($total / $perPage);

        $emails = $wpdb->get_results(
            "SELECT id, email_to, email_subject, status, error, attempts, created_at, sent_at
             FROM {$table}
             WHERE {$where}
             ORDER BY created_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        // Count by status for sub-filters
        $counts = [];
        foreach (['sent', 'failed'] as $s) {
            $counts[$s] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE status = %s",
                $s,
            ));
        }
        $counts['all'] = array_sum($counts);

        $showImport = Database::wpOsesTableExists();

        ?>
        <?php if ($result === 'success'): ?>
            <div class="notice notice-success"><p>
                Email #<?php echo esc_html((string) $resultId); ?> sent successfully.
                <?php if ($wasOverride): ?>
                    <strong>(Override address used — not marked as sent.)</strong>
                <?php else: ?>
                    Marked as sent.
                <?php endif; ?>
            </p></div>
        <?php elseif ($result === 'fail'): ?>
            <div class="notice notice-error"><p>
                Failed to send email #<?php echo esc_html((string) $resultId); ?>.
                <?php if ($error): ?> Error: <?php echo esc_html($error); ?><?php endif; ?>
            </p></div>
        <?php elseif ($result === 'imported'): ?>
            <div class="notice notice-success"><p>
                Imported <?php echo esc_html((string) $resultCount); ?> email(s) from WP Offload SES.
            </p></div>
        <?php elseif ($result === 'import-none'): ?>
            <div class="notice notice-warning"><p>No new emails to import (all records already exist).</p></div>
        <?php endif; ?>

        <ul class="subsubsub">
            <?php $i = 0; foreach (['all', 'failed', 'sent'] as $s): ?>
                <?php
                $isCurrent = $statusFilter === $s;
                $url = AdminPage::tabUrl('log', ['status' => $s]);
                $label = ucfirst($s);
                $count = $counts[$s];
                ?>
                <li>
                    <?php if ($isCurrent): ?>
                        <strong><?php echo esc_html($label); ?> <span class="count">(<?php echo esc_html((string) $count); ?>)</span></strong>
                    <?php else: ?>
                        <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?> <span class="count">(<?php echo esc_html((string) $count); ?>)</span></a>
                    <?php endif; ?>
                    <?php if (++$i < 3): ?> |<?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="clear:both;">
            <?php wp_nonce_field('vendi_ses_queue_nonce', '_vendi_ses_queue_nonce'); ?>
            <input type="hidden" name="action" value="vendi_ses_queue_send">

            <table class="form-table" style="max-width: 400px;">
                <tr>
                    <th><label for="vendi_ses_override">Override To</label></th>
                    <td>
                        <input type="email" id="vendi_ses_override" name="override_to" value="" class="regular-text" placeholder="Leave blank to send to original recipient">
                        <p class="description">If set, email goes here instead. The log entry will <strong>not</strong> be marked as sent.</p>
                    </td>
                </tr>
            </table>

            <?php if ($emails): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:3em;"></th>
                        <th style="width:4em;">ID</th>
                        <th>To</th>
                        <th>Subject</th>
                        <th style="width:5em;">Status</th>
                        <th>Error</th>
                        <th style="width:4em;">#</th>
                        <th style="width:11em;">Created</th>
                        <th style="width:11em;">Sent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emails as $email): ?>
                    <tr>
                        <td><input type="checkbox" name="email_ids[]" value="<?php echo esc_attr((string) $email->id); ?>"></td>
                        <td><?php echo esc_html((string) $email->id); ?></td>
                        <td><?php echo esc_html($email->email_to); ?></td>
                        <td><?php echo esc_html($email->email_subject); ?></td>
                        <td><?php echo esc_html($email->status); ?></td>
                        <td style="font-size:12px; color:#a00;"><?php echo esc_html($email->error ?: ''); ?></td>
                        <td><?php echo esc_html((string) $email->attempts); ?></td>
                        <td><?php echo esc_html($email->created_at); ?></td>
                        <td><?php echo esc_html($email->sent_at ?: '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="tablenav bottom">
                <div class="alignleft actions">
                    <?php submit_button('Send Selected', 'primary', 'submit', false); ?>
                </div>
                <?php if ($totalPages > 1): ?>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo esc_html(number_format_i18n($total)); ?> items</span>
                    <?php
                    echo paginate_links([
                        'base' => AdminPage::tabUrl('log', ['status' => $statusFilter, 'paged' => '%#%']),
                        'format' => '',
                        'current' => $currentPage,
                        'total' => $totalPages,
                        'prev_text' => '&lsaquo;',
                        'next_text' => '&rsaquo;',
                    ]);
                    ?>
                </div>
                <?php endif; ?>
            </div>

            <?php else: ?>
                <p>No emails found with status <strong><?php echo esc_html($statusFilter); ?></strong>.</p>
            <?php endif; ?>
        </form>

        <?php if ($showImport): ?>
        <hr>
        <h2>Import from WP Offload SES</h2>
        <p>The <code>wp_oses_emails</code> table was detected. You can import its records into the Vendi SES log. Duplicates are skipped automatically.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('vendi_ses_import_nonce', '_vendi_ses_import_nonce'); ?>
            <input type="hidden" name="action" value="vendi_ses_import_wposes">
            <?php submit_button('Import Emails', 'secondary'); ?>
        </form>
        <?php endif; ?>
        <?php
    }

    public static function handleSend(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('vendi_ses_queue_nonce', '_vendi_ses_queue_nonce');

        $emailIds = array_map('intval', $_POST['email_ids'] ?? []);
        $overrideTo = sanitize_email($_POST['override_to'] ?? '');

        if (!$emailIds) {
            wp_safe_redirect(AdminPage::tabUrl('log', [
                'vendi-ses-result' => 'fail',
                'vendi-ses-id' => 0,
            ]));
            exit;
        }

        $lastId = 0;
        $lastResult = false;
        $isOverride = $overrideTo !== '';

        $mailer = new SesMailer(
            VENDI_SES_ACCESS_KEY_ID,
            VENDI_SES_SECRET_ACCESS_KEY,
            VENDI_SES_REGION,
            VENDI_SES_FROM,
        );

        foreach ($emailIds as $emailId) {
            $email = Database::getEmail($emailId);

            if (!$email) {
                continue;
            }

            $to = $isOverride ? $overrideTo : $email->email_to;

            $sent = $mailer->send(
                $to,
                $email->email_subject,
                $email->email_message,
                $email->email_headers,
                $email->email_attachments,
            );

            $lastId = $emailId;
            $lastResult = $sent;

            if ($sent && !$isOverride) {
                Database::markSent($emailId);
            } elseif (!$sent) {
                $error = (string) get_transient('vendi_ses_last_error');
                Database::markFailed($emailId, $error);
            }
        }

        $args = [
            'vendi-ses-result' => $lastResult ? 'success' : 'fail',
            'vendi-ses-id' => $lastId,
        ];
        if ($isOverride) {
            $args['vendi-ses-override'] = '1';
        }

        wp_safe_redirect(AdminPage::tabUrl('log', $args));
        exit;
    }

    public static function handleImport(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('vendi_ses_import_nonce', '_vendi_ses_import_nonce');

        $count = Database::importFromWpOses();

        $args = ['status' => 'all'];

        if ($count > 0) {
            $args['vendi-ses-result'] = 'imported';
            $args['vendi-ses-count'] = $count;
        } else {
            $args['vendi-ses-result'] = 'import-none';
        }

        wp_safe_redirect(AdminPage::tabUrl('log', $args));
        exit;
    }
}
