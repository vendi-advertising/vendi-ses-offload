<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// AWS SDK service directories and data directories that are actually used.
// Derived by tracing get_included_files() during SesV2Client instantiation.
$awsSdkCoreDirs = [
    'Api', 'Auth', 'ClientSideMonitoring', 'Configuration', 'Credentials',
    'DefaultsMode', 'Endpoint', 'EndpointDiscovery', 'EndpointV2', 'Exception',
    'Handler', 'Identity', 'Retry', 'SesV2', 'Signature', 'Token',
];

// Build an exclude callback for unused AWS service and data directories.
$awsSrcPath = 'vendor/aws/aws-sdk-php/src';
$unusedServiceFilter = static function (\SplFileInfo $file) use ($awsSrcPath, $awsSdkCoreDirs): bool {
    $path = str_replace('\\', '/', $file->getPathname());

    // Only filter files under the AWS SDK src directory.
    if (!str_contains($path, $awsSrcPath)) {
        return true;
    }

    $relative = substr($path, strpos($path, $awsSrcPath) + strlen($awsSrcPath) + 1);

    // Root-level files (AwsClient.php, Middleware.php, etc.) — always include.
    if (!str_contains($relative, '/')) {
        return true;
    }

    $topDir = explode('/', $relative)[0];

    // The data/ directory: only include sesv2, partitions, endpoints, and aliases.
    if ($topDir === 'data') {
        $parts = explode('/', $relative);
        if (count($parts) >= 2) {
            $dataSubdir = $parts[1];
            return in_array($dataSubdir, ['sesv2', 'partitions.json.php', 'endpoints.json.php', 'endpoints_prefix_history.json.php', 'aliases.json.php', 'manifest.json.php'], true)
                || !str_contains($dataSubdir, '/') && str_ends_with($dataSubdir, '.php');
        }
        return true;
    }

    // Core directories — include.
    return in_array($topDir, $awsSdkCoreDirs, true);
};

return [
    'prefix' => 'Vendi\\SesOffload\\Vendor',

    'finders' => [
        // All non-AWS vendor packages (guzzle, psr, symfony, etc.)
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->exclude('aws')
            ->in('vendor'),

        // AWS SDK — filtered to only include core + SES v2.
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->filter($unusedServiceFilter)
            ->in('vendor/aws'),

        // Include composer files so dump-autoload works in the build dir.
        Finder::create()
            ->files()
            ->name(['composer.json', 'composer.lock'])
            ->depth(0)
            ->in('.'),
    ],

    'exclude-namespaces' => [
        'Vendi\SesOffload',
    ],

    'exclude-files' => [],

    'patchers' => [
        // AwsClient::parseClass() dynamically builds exception class names as
        // "Aws\\{$service}\\Exception\\{$service}Exception" — a string concat
        // that PHP-Scoper can't rewrite. Patch it to use the scoped prefix.
        static function (string $filePath, string $prefix, string $contents): string {
            if (!str_ends_with($filePath, 'AwsClient.php')) {
                return $contents;
            }
            return str_replace(
                '"Aws\\\\{$service}\\\\Exception\\\\{$service}Exception"',
                '"Vendi\\\\SesOffload\\\\Vendor\\\\Aws\\\\{$service}\\\\Exception\\\\{$service}Exception"',
                $contents,
            );
        },
        // PHP-Scoper incorrectly prefixes string constants that look like
        // namespace paths. Date format strings with backslashes (e.g.
        // 'Ymd\THis\Z') get mangled. Different scoper versions produce
        // single or double backslashes, so we handle both.
        static function (string $filePath, string $prefix, string $contents): string {
            // Single-backslash variant (php-scoper 0.18.18+)
            $mangled = [
                "Vendi\\SesOffload\\Vendor\\Ymd\\THis\\Z"                 => "Ymd\\THis\\Z",
                "Vendi\\SesOffload\\Vendor\\Y-m-d\\TH:i:s\\Z"            => "Y-m-d\\TH:i:s\\Z",
                "Vendi\\SesOffload\\Vendor\\D, d M Y H:i:s \\G\\M\\T"    => "D, d M Y H:i:s \\G\\M\\T",
            ];
            $contents = str_replace(array_keys($mangled), array_values($mangled), $contents);

            // Double-backslash variant (older php-scoper versions)
            $mangledDouble = [
                "Vendi\\\\SesOffload\\\\Vendor\\\\Ymd\\\\THis\\\\Z"              => "Ymd\\\\THis\\\\Z",
                "Vendi\\\\SesOffload\\\\Vendor\\\\Y-m-d\\\\TH:i:s\\\\Z"         => "Y-m-d\\\\TH:i:s\\\\Z",
                "Vendi\\\\SesOffload\\\\Vendor\\\\D, d M Y H:i:s \\\\G\\\\M\\\\T" => "D, d M Y H:i:s \\\\G\\\\M\\\\T",
            ];
            return str_replace(array_keys($mangledDouble), array_values($mangledDouble), $contents);
        },
    ],
];
