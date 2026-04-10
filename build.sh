#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

echo "Installing production dependencies..."
composer install --no-dev --optimize-autoloader

echo "Removing previous build..."
rm -rf build

echo "Running PHP-Scoper..."
php-scoper add-prefix --output-dir=build --force

echo "Regenerating autoloader for scoped build..."
composer dump-autoload --working-dir=build --classmap-authoritative --no-dev

echo "Done. Scoped vendor files are in build/"
