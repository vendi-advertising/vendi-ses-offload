<?php

declare(strict_types=1);

namespace Vendi\SesOffload;

final class Database
{
    public const TABLE_NAME = 'vendi_ses_log';
    public const DB_VERSION = '1.0';
    public const DB_VERSION_OPTION = 'vendi_ses_log_db_version';

    public static function getTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    public static function install(): void
    {
        global $wpdb;

        $table = self::getTableName();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint NOT NULL AUTO_INCREMENT,
            email_to text NOT NULL,
            email_subject varchar(255) NOT NULL,
            email_message longtext NOT NULL,
            email_headers longtext NOT NULL,
            email_attachments longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'failed',
            error text DEFAULT NULL,
            attempts int NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            sent_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
    }

    public static function maybeUpgrade(): void
    {
        if (get_option(self::DB_VERSION_OPTION) !== self::DB_VERSION) {
            self::install();
        }
    }

    /**
     * Insert an email log record.
     *
     * @return int|false The inserted row ID, or false on failure.
     */
    public static function logEmail(
        string $to,
        string $subject,
        string $message,
        string $headers,
        string $attachments,
        string $status,
        ?string $error = null,
    ): int|false {
        global $wpdb;

        $data = [
            'email_to' => $to,
            'email_subject' => $subject,
            'email_message' => $message,
            'email_headers' => $headers,
            'email_attachments' => $attachments,
            'status' => $status,
            'error' => $error,
            'attempts' => 1,
            'created_at' => current_time('mysql'),
            'sent_at' => $status === 'sent' ? current_time('mysql') : null,
        ];

        $result = $wpdb->insert(self::getTableName(), $data);

        return $result ? (int) $wpdb->insert_id : false;
    }

    /**
     * Mark an existing record as sent.
     */
    public static function markSent(int $id): void
    {
        global $wpdb;

        $wpdb->update(self::getTableName(), [
            'status' => 'sent',
            'error' => null,
            'sent_at' => current_time('mysql'),
        ], ['id' => $id]);
    }

    /**
     * Mark an existing record as failed and increment attempts.
     */
    public static function markFailed(int $id, string $error): void
    {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            "UPDATE %i SET status = 'failed', error = %s, attempts = attempts + 1 WHERE id = %d",
            self::getTableName(),
            $error,
            $id,
        ));
    }

    /**
     * Get a single email record by ID.
     */
    public static function getEmail(int $id): ?object
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE id = %d",
            self::getTableName(),
            $id,
        ));
    }

    /**
     * Check if the WP Offload SES emails table exists.
     */
    public static function wpOsesTableExists(): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'oses_emails';
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    /**
     * Import emails from WP Offload SES into our log table.
     *
     * @return int Number of rows imported.
     */
    public static function importFromWpOses(): int
    {
        global $wpdb;

        $source = $wpdb->prefix . 'oses_emails';
        $dest = self::getTableName();

        // Only import rows that haven't already been imported.
        // We use a NOT EXISTS check against matching subject + to + created_at to avoid duplicates.
        $imported = (int) $wpdb->query(
            "INSERT INTO {$dest} (email_to, email_subject, email_message, email_headers, email_attachments, status, error, attempts, created_at, sent_at)
             SELECT
                 s.email_to,
                 s.email_subject,
                 s.email_message,
                 s.email_headers,
                 s.email_attachments,
                 CASE s.email_status
                     WHEN 'queued' THEN 'failed'
                     WHEN 'cancelled' THEN 'failed'
                     ELSE s.email_status
                 END,
                 NULL,
                 GREATEST(1, s.auto_retries + s.manual_retries),
                 s.email_created,
                 s.email_sent
             FROM {$source} s
             WHERE NOT EXISTS (
                 SELECT 1 FROM {$dest} d
                 WHERE d.email_to = s.email_to
                   AND d.email_subject = s.email_subject
                   AND d.created_at = s.email_created
             )"
        );

        return $imported;
    }
}
