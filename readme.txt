=== Vendi SES Offload ===
Contributors: vendiadvertising
Tags: email, ses, aws, smtp
Requires at least: 5.9
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.5
License: GPLv2 or later

Routes all WordPress email through Amazon SES using the SESv2 API.

== Description ==

Vendi SES Offload replaces WordPress's default email delivery with Amazon Simple Email Service (SES). All calls to `wp_mail()` are intercepted and sent via the SES v2 API as raw MIME messages.

Features:

* Sends all WordPress email through Amazon SES
* Logs every email attempt with status (sent/failed) and error details
* Admin interface to view the email log, retry failed emails, and send test messages
* Optional import of email history from WP Offload SES Lite
* Scoped vendor dependencies to avoid conflicts with other plugins
* Configuration via `wp-config.php` constants — no database settings to manage

== Installation ==

1. Upload the `vendi-ses-offload` directory to `wp-content/plugins/`.
2. Add the following constants to `wp-config.php`:

    define( 'VENDI_SES_ACCESS_KEY_ID',     'your-aws-access-key-id' );
    define( 'VENDI_SES_SECRET_ACCESS_KEY', 'your-aws-secret-access-key' );
    define( 'VENDI_SES_REGION',            'us-east-2' );
    define( 'VENDI_SES_FROM',              'noreply@example.com' );

3. Activate the plugin through the WordPress admin.
4. Go to Tools > Vendi SES > Send Test to verify delivery.

== Configuration ==

All configuration is done via PHP constants in `wp-config.php`:

* `VENDI_SES_ACCESS_KEY_ID` — Your AWS IAM access key ID.
* `VENDI_SES_SECRET_ACCESS_KEY` — Your AWS IAM secret access key.
* `VENDI_SES_REGION` — The AWS region where SES is configured (e.g. `us-east-2`).
* `VENDI_SES_FROM` — The verified sender email address.

All four constants are required. The plugin will display an admin notice if any are missing.

== Frequently Asked Questions ==

= What AWS permissions does the IAM user need? =

The IAM user needs `ses:SendEmail` and `ses:SendRawEmail` permissions on the identity ARN used for sending.

= Can I use this alongside WP Offload SES Lite? =

You should deactivate WP Offload SES Lite before activating this plugin to avoid conflicts. You can import your existing email log from WP Offload SES via the Email Log tab.

= How do I rebuild the scoped vendor dependencies? =

Install php-scoper globally (`composer global require humbug/php-scoper`) and run `./build.sh` from the plugin directory.

== Changelog ==

= 1.0.5 =
* Initial release.
* SES v2 API integration with raw MIME sending.
* Email logging with sent/failed status tracking.
* Admin UI with Email Log, Send Test, and Configuration tabs.
* WP Offload SES email history import.
* PHP-Scoper namespaced vendor dependencies.
