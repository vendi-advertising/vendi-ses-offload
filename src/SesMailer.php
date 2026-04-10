<?php

declare(strict_types=1);

namespace Vendi\SesOffload;

use Vendi\SesOffload\Vendor\Aws\SesV2\SesV2Client;
use Vendi\SesOffload\Vendor\Aws\Exception\AwsException;

final class SesMailer
{
    private SesV2Client $client;
    private string $defaultFrom;

    public function __construct(string $accessKeyId, string $secretAccessKey, string $region, string $defaultFrom)
    {
        $this->defaultFrom = $defaultFrom;
        $this->client = new SesV2Client([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
        ]);
    }

    /**
     * Send an email via SES. Compatible with wp_mail() signature.
     *
     * @param string|string[] $to
     * @param string $subject
     * @param string $message
     * @param string|string[] $headers
     * @param string|string[] $attachments
     * @return bool
     */
    public function send(
        array|string $to,
        string $subject,
        string $message,
        array|string $headers = '',
        array|string $attachments = [],
    ): bool {
        $to = is_array($to) ? $to : explode(',', $to);
        $to = array_map('trim', $to);

        $headers = is_array($headers) ? $headers : explode("\n", str_replace("\r\n", "\n", $headers));

        $from = $this->defaultFrom;
        $contentType = 'text/plain';
        $cc = [];
        $bcc = [];
        $replyTo = [];

        foreach ($headers as $header) {
            $header = trim($header);
            if ($header === '') {
                continue;
            }

            [$name, $value] = array_pad(explode(':', $header, 2), 2, '');
            $name = strtolower(trim($name));
            $value = trim($value);

            match ($name) {
                'from' => $from = $value,
                'content-type' => $contentType = strtolower(explode(';', $value)[0]),
                'cc' => $cc = array_merge($cc, array_map('trim', explode(',', $value))),
                'bcc' => $bcc = array_merge($bcc, array_map('trim', explode(',', $value))),
                'reply-to' => $replyTo = array_merge($replyTo, array_map('trim', explode(',', $value))),
                default => null,
            };
        }

        $isHtml = str_contains($contentType, 'html');

        $attachments = is_array($attachments) ? $attachments : [$attachments];
        $attachments = array_filter($attachments);

        // Always send as raw MIME — pass only Content.Raw.Data, no FromEmailAddress
        // or Destination. This matches how WP Offload SES sends and avoids IAM
        // authorization issues with explicit identity parameters.
        $rawMessage = $this->buildRawMime($from, $to, $cc, $bcc, $replyTo, $subject, $message, $isHtml, $attachments);

        try {
            $this->client->sendEmail([
                'Content' => [
                    'Raw' => [
                        'Data' => $rawMessage,
                    ],
                ],
            ]);
            delete_transient('vendi_ses_last_error');
            return true;
        } catch (AwsException $e) {
            $errorMessage = $e->getAwsErrorCode() . ': ' . $e->getAwsErrorMessage();
            set_transient('vendi_ses_last_error', $errorMessage, 120);
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('[Vendi SES Offload] Failed to send email: ' . $errorMessage);
            return false;
        } catch (\Throwable $e) {
            $errorMessage = get_class($e) . ': ' . $e->getMessage();
            set_transient('vendi_ses_last_error', $errorMessage, 120);
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('[Vendi SES Offload] Failed to send email: ' . $errorMessage);
            return false;
        }
    }

    private function buildRawMime(
        string $from,
        array $to,
        array $cc,
        array $bcc,
        array $replyTo,
        string $subject,
        string $body,
        bool $isHtml,
        array $attachments,
    ): string {
        $mime = '';

        $mime .= 'From: ' . $from . "\r\n";
        $mime .= 'To: ' . implode(', ', $to) . "\r\n";
        if ($cc) {
            $mime .= 'Cc: ' . implode(', ', $cc) . "\r\n";
        }
        if ($bcc) {
            $mime .= 'Bcc: ' . implode(', ', $bcc) . "\r\n";
        }
        if ($replyTo) {
            $mime .= 'Reply-To: ' . implode(', ', $replyTo) . "\r\n";
        }
        $mime .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
        $mime .= 'MIME-Version: 1.0' . "\r\n";

        // Simple message with no attachments.
        if (!$attachments) {
            $mime .= 'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . "; charset=UTF-8\r\n";
            $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $mime .= chunk_split(base64_encode($body));
            return $mime;
        }

        // Multipart message with attachments.
        $boundary = bin2hex(random_bytes(16));
        $mime .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n\r\n";

        // Body part
        $mime .= '--' . $boundary . "\r\n";
        $mime .= 'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8' . "\r\n";
        $mime .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
        $mime .= chunk_split(base64_encode($body)) . "\r\n";

        // Attachment parts
        foreach ($attachments as $filePath) {
            if (!is_file($filePath) || !is_readable($filePath)) {
                continue;
            }
            $filename = basename($filePath);
            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
            $fileData = file_get_contents($filePath);

            $mime .= '--' . $boundary . "\r\n";
            $mime .= 'Content-Type: ' . $mimeType . '; name="' . $filename . '"' . "\r\n";
            $mime .= 'Content-Disposition: attachment; filename="' . $filename . '"' . "\r\n";
            $mime .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
            $mime .= chunk_split(base64_encode($fileData)) . "\r\n";
        }

        $mime .= '--' . $boundary . '--' . "\r\n";

        return $mime;
    }
}
