#!/usr/bin/env bash
#
# Prepare WordPress core + phpunit lib under .srv/wp-tests and ensure the
# wordpress_test database exists (via PDO, no mysql CLI required).
#
# DB password/port default to DB_ROOT_PASSWORD and DB_PORT from .env
# (Docker MariaDB). Explicit WP_TESTS_DB_* variables still win (CI).
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

# Read one KEY=value from .env without sourcing the file.
read_dotenv() {
	local key="$1"
	local file="$ROOT/.env"
	local line value

	[ -f "$file" ] || return 0

	line="$(grep -E "^${key}=" "$file" | tail -n 1 | tr -d '\r' || true)"
	[ -n "$line" ] || return 0

	value="${line#*=}"
	value="${value#"${value%%[![:space:]]*}"}"
	value="${value%"${value##*[![:space:]]}"}"
	value="${value#\"}"
	value="${value%\"}"
	value="${value#\'}"
	value="${value%\'}"
	printf '%s' "$value"
}

export WP_TESTS_DB_NAME="${WP_TESTS_DB_NAME:-wordpress_test}"
export WP_TESTS_DB_USER="${WP_TESTS_DB_USER:-root}"

if [ -z "${WP_TESTS_DB_PASS+x}" ]; then
	WP_TESTS_DB_PASS="$(read_dotenv DB_ROOT_PASSWORD)"
	WP_TESTS_DB_PASS="${WP_TESTS_DB_PASS:-root}"
fi
export WP_TESTS_DB_PASS

if [ -z "${WP_TESTS_DB_HOST+x}" ]; then
	db_port="$(read_dotenv DB_PORT)"
	db_port="${db_port:-3306}"
	# Tests run on the host. Compose service name "mysql" is only reachable inside Docker.
	WP_TESTS_DB_HOST="127.0.0.1:${db_port}"
fi
export WP_TESTS_DB_HOST

export WP_TESTS_VERSION="${WP_TESTS_VERSION:-7.0.2}"

php -r '
$db_name = getenv("WP_TESTS_DB_NAME") ?: "wordpress_test";
$db_user = getenv("WP_TESTS_DB_USER") ?: "root";
$db_pass = getenv("WP_TESTS_DB_PASS");
$db_pass = is_string($db_pass) ? $db_pass : "root";
$db_host = getenv("WP_TESTS_DB_HOST") ?: "127.0.0.1:3306";

$host = $db_host;
$port = null;
if (str_contains($db_host, ":")) {
    [$host, $port] = explode(":", $db_host, 2);
}

$dsn = "mysql:host={$host}" . ($port ? ";port={$port}" : "");
$attempts = 30;
$pdo = null;
for ($i = 1; $i <= $attempts; $i++) {
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        break;
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $denied = str_contains($message, "1045") || str_contains($message, "Access denied");
        if ($denied || $i === $attempts) {
            fwrite(STDERR, "Could not connect to MySQL at {$db_host}: {$message}" . PHP_EOL);
            exit(1);
        }
        sleep(2);
    }
}
$pdo->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace("`", "``", $db_name) . "`");
echo "Database {$db_name} is ready.\n";
'

# Download / configure WP + tests lib; skip CLI DB creation (PDO already did it).
bash "$ROOT/bin/install-wp-tests.sh" "$WP_TESTS_DB_NAME" "$WP_TESTS_DB_USER" "$WP_TESTS_DB_PASS" "$WP_TESTS_DB_HOST" "$WP_TESTS_VERSION" true

# Keep an already written wp-tests-config.php in sync with the current credentials.
php <<'PHP'
<?php
$root = getenv('PWD') ?: getcwd();
$file = $root . '/.srv/wp-tests/wordpress-tests-lib/wp-tests-config.php';
if (! is_file($file)) {
    exit(0);
}
$map = array(
    'DB_NAME'     => getenv('WP_TESTS_DB_NAME') ?: 'wordpress_test',
    'DB_USER'     => getenv('WP_TESTS_DB_USER') ?: 'root',
    'DB_PASSWORD' => (string) getenv('WP_TESTS_DB_PASS'),
    'DB_HOST'     => getenv('WP_TESTS_DB_HOST') ?: '127.0.0.1:3306',
);
$src = file_get_contents($file);
if (! is_string($src)) {
    fwrite(STDERR, "Could not read {$file}\n");
    exit(1);
}
foreach ($map as $const => $value) {
    $literal = var_export((string) $value, true);
    $pattern = "/define\\(\\s*'{$const}'\\s*,\\s*(?:'[^']*'|\"[^\"]*\")\\s*\\);/";
    $src = preg_replace($pattern, "define( '{$const}', {$literal} );", $src, 1);
}
file_put_contents($file, $src);
PHP
