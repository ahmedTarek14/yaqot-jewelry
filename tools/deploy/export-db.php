<?php

/**
 * Exports a LOCAL MySQL database as one .sql file to import with InfinityFree's phpMyAdmin
 * (see docs/DEPLOY.md, "Database"). Run it on the clean deploy database, after migrating it and
 * creating the first admin, never on the development database (it holds demo data):
 *
 *   php tools/deploy/export-db.php --database=yaqot_deploy --out=D:\yaqot-release\yaqot_deploy.sql
 *   [--mysqldump=D:\xampp\mysql\bin\mysqldump.exe]
 *
 * The host, user and password come from the project's .env (DB_HOST, DB_PORT, DB_USERNAME,
 * DB_PASSWORD); the password is passed to mysqldump through the environment, not the command line.
 * The file has no CREATE DATABASE / USE line (the database on the host has another name), no
 * triggers or routines, no tablespace statements (the host's user may not have the privilege) and
 * every table is dropped and recreated, so importing it twice gives the same result.
 */
$options = getopt('', ['database:', 'out:', 'mysqldump::', 'force']);

if (empty($options['database']) || empty($options['out'])) {
    fwrite(STDERR, "Usage: php tools/deploy/export-db.php --database=<local database> --out=<file.sql> [--mysqldump=<path>]\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
$env = [];

foreach (is_file("$root/.env") ? file("$root/.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $line) {
    if (! str_starts_with(trim($line), '#') && str_contains($line, '=')) {
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\"'");
    }
}

$database = (string) $options['database'];

if (! isset($options['force']) && $database === ($env['DB_DATABASE'] ?? null)) {
    fwrite(STDERR, "Refusing to export the development database ($database): it holds demo data. Use the clean deploy database.\n");
    exit(1);
}

$mysqldump = (string) ($options['mysqldump'] ?? (is_file('D:\\xampp\\mysql\\bin\\mysqldump.exe') ? 'D:\\xampp\\mysql\\bin\\mysqldump.exe' : 'mysqldump'));
$out = (string) $options['out'];

if (! is_dir(dirname($out))) {
    mkdir(dirname($out), 0777, true);
}

$command = implode(' ', [
    escapeshellarg($mysqldump),
    '--host='.escapeshellarg($env['DB_HOST'] ?? '127.0.0.1'),
    '--port='.escapeshellarg($env['DB_PORT'] ?? '3306'),
    '--user='.escapeshellarg($env['DB_USERNAME'] ?? 'root'),
    '--default-character-set=utf8mb4',
    '--single-transaction',
    '--no-tablespaces',
    '--skip-triggers',
    '--skip-add-locks',
    '--skip-comments',
    '--no-create-db',
    '--add-drop-table',
    '--result-file='.escapeshellarg($out),
    escapeshellarg($database),
]);

$process = proc_open($command, [1 => STDOUT, 2 => STDERR], $pipes, null, array_merge(getenv(), ['MYSQL_PWD' => $env['DB_PASSWORD'] ?? '']));

if (! is_resource($process) || proc_close($process) !== 0 || ! is_file($out)) {
    fwrite(STDERR, "mysqldump failed.\n");
    exit(1);
}

$sql = (string) file_get_contents($out);
$tables = preg_match_all('/^CREATE TABLE `([^`]+)`/m', $sql, $matches);
$admins = preg_match('/INSERT INTO `admins`/', $sql) === 1;

printf("Wrote %s (%.1f KB): %d tables, %s.\n", $out, filesize($out) / 1024, $tables, $admins ? 'with an admin account' : 'NO admin account (run yaqot:create-admin on this database first)');
printf("Tables: %s\n", implode(', ', $matches[1]));
