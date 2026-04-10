<?php

declare(strict_types=1);

namespace Vendi\SesOffload;

final class AdminPage
{
    private const SLUG = 'vendi-ses';

    private const TABS = [
        'log' => 'Email Log',
        'test' => 'Send Test',
        'config' => 'Configuration',
    ];

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'addMenuPage']);

        // POST handlers from the sub-pages.
        add_action('admin_post_vendi_ses_send_test', [TestPage::class, 'handleTestSend']);
        add_action('admin_post_vendi_ses_queue_send', [QueuePage::class, 'handleSend']);
        add_action('admin_post_vendi_ses_import_wposes', [QueuePage::class, 'handleImport']);
    }

    public static function addMenuPage(): void
    {
        add_management_page(
            'Vendi SES',
            'Vendi SES',
            'manage_options',
            self::SLUG,
            [self::class, 'renderPage'],
        );
    }

    public static function getSlug(): string
    {
        return self::SLUG;
    }

    public static function tabUrl(string $tab, array $extra = []): string
    {
        return add_query_arg(
            array_merge(['page' => self::SLUG, 'tab' => $tab], $extra),
            admin_url('tools.php'),
        );
    }

    public static function renderPage(): void
    {
        $currentTab = sanitize_text_field($_GET['tab'] ?? 'log');
        if (!array_key_exists($currentTab, self::TABS)) {
            $currentTab = 'log';
        }

        ?>
        <div class="wrap">
            <h1>Vendi SES</h1>

            <nav class="nav-tab-wrapper">
                <?php foreach (self::TABS as $slug => $label): ?>
                    <a href="<?php echo esc_url(self::tabUrl($slug)); ?>"
                       class="nav-tab <?php echo $currentTab === $slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div style="margin-top: 1em;">
                <?php
                match ($currentTab) {
                    'log' => QueuePage::renderContent(),
                    'test' => TestPage::renderContent(),
                    'config' => self::renderConfig(),
                };
                ?>
            </div>
        </div>
        <?php
    }

    private static function renderConfig(): void
    {
        ?>
        <table class="form-table">
            <tr>
                <th>From Address</th>
                <td><code><?php echo esc_html(VENDI_SES_FROM); ?></code></td>
            </tr>
            <tr>
                <th>Region</th>
                <td><code><?php echo esc_html(VENDI_SES_REGION); ?></code></td>
            </tr>
            <tr>
                <th>AWS Key ID</th>
                <td><code><?php echo esc_html(VENDI_SES_ACCESS_KEY_ID); ?></code></td>
            </tr>
        </table>
        <p class="description">These values are set via constants in <code>wp-config.php</code>.</p>
        <?php
    }
}
