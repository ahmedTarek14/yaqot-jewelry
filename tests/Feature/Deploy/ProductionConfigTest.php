<?php

namespace Tests\Feature\Deploy;

use Tests\TestCase;

/**
 * The files that configure a production deployment (docs/DEPLOY.md): safe values, no secrets,
 * and a host without symlinks keeps working.
 */
class ProductionConfigTest extends TestCase
{
    /** @return array<string, string> */
    private function productionEnv(): array
    {
        $values = [];

        foreach (file(base_path('.env.production.example'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (! str_starts_with(trim($line), '#') && str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $values[trim($key)] = trim($value);
            }
        }

        return $values;
    }

    public function test_the_production_example_is_safe_and_holds_no_secret(): void
    {
        $env = $this->productionEnv();

        $this->assertSame('production', $env['APP_ENV']);
        $this->assertSame('false', $env['APP_DEBUG']);
        $this->assertStringStartsWith('https://', $env['APP_URL']);
        $this->assertSame('warning', $env['LOG_LEVEL']);
        $this->assertSame('mysql', $env['DB_CONNECTION']);

        // Sessions and cache in the database (no file per visitor), cookies only over https.
        $this->assertSame('database', $env['SESSION_DRIVER']);
        $this->assertSame('database', $env['CACHE_STORE']);
        $this->assertSame('true', $env['SESSION_SECURE_COOKIE']);
        $this->assertSame('true', $env['SESSION_HTTP_ONLY']);
        $this->assertSame('lax', $env['SESSION_SAME_SITE']);
        $this->assertSame('sync', $env['QUEUE_CONNECTION']);

        // Uploads without a symlink.
        $this->assertSame('uploads', $env['PUBLIC_DISK_ROOT']);

        // Every secret is left for the owner to fill in on the server.
        foreach (['APP_KEY', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $secret) {
            $this->assertSame('', $env[$secret], "$secret must be empty in the example");
        }

        $this->assertStringNotContainsString('base64:', (string) file_get_contents(base_path('.env.production.example')));
    }

    public function test_every_setting_the_example_names_is_one_the_app_reads(): void
    {
        // A typo in the example would be silently ignored on the server.
        $known = ['APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL', 'APP_LOCALE', 'APP_FALLBACK_LOCALE', 'APP_MAINTENANCE_DRIVER',
            'BCRYPT_ROUNDS', 'LOG_CHANNEL', 'LOG_STACK', 'LOG_DAILY_DAYS', 'LOG_LEVEL', 'LOG_DEPRECATIONS_CHANNEL', 'DB_CONNECTION', 'DB_HOST',
            'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'SESSION_DRIVER', 'SESSION_LIFETIME', 'SESSION_ENCRYPT', 'SESSION_PATH',
            'SESSION_DOMAIN', 'SESSION_SECURE_COOKIE', 'SESSION_HTTP_ONLY', 'SESSION_SAME_SITE', 'CACHE_STORE', 'QUEUE_CONNECTION',
            'FILESYSTEM_DISK', 'PUBLIC_DISK_ROOT', 'MAIL_MAILER'];

        // The project's config files and the framework's own defaults (e.g. BCRYPT_ROUNDS).
        $code = '';
        foreach ([...glob(config_path('*.php')), ...glob(base_path('vendor/laravel/framework/config/*.php'))] as $file) {
            $code .= file_get_contents($file);
        }

        foreach (array_keys($this->productionEnv()) as $key) {
            $this->assertContains($key, $known, "$key is not a setting the app reads");
            $this->assertStringContainsString("'$key'", $code, "$key is not read anywhere in config/");
        }
    }

    public function test_the_public_disk_follows_public_disk_root_without_a_symlink(): void
    {
        $local = require config_path('filesystems.php');
        $this->assertSame(storage_path('app/public'), $local['disks']['public']['root']);
        $this->assertStringEndsWith('/storage', $local['disks']['public']['url']);

        putenv('PUBLIC_DISK_ROOT=uploads');
        $_ENV['PUBLIC_DISK_ROOT'] = $_SERVER['PUBLIC_DISK_ROOT'] = 'uploads';

        try {
            $host = require config_path('filesystems.php');
        } finally {
            putenv('PUBLIC_DISK_ROOT');
            unset($_ENV['PUBLIC_DISK_ROOT'], $_SERVER['PUBLIC_DISK_ROOT']);
        }

        $this->assertSame(public_path('uploads'), $host['disks']['public']['root']);
        $this->assertStringEndsWith('/uploads', $host['disks']['public']['url']);
    }

    public function test_the_htaccess_files_fit_the_hosts_10_kb_limit_and_protect_what_they_should(): void
    {
        foreach (['public/.htaccess', 'tools/deploy/templates/htdocs.htaccess', 'tools/deploy/templates/deny.htaccess', 'tools/deploy/templates/public-uploads.htaccess'] as $file) {
            $this->assertLessThan(10 * 1024, filesize(base_path($file)), "$file is over the host's 10 kB .htaccess limit");
        }

        $root = (string) file_get_contents(base_path('tools/deploy/templates/htdocs.htaccess'));
        $this->assertStringContainsString('RewriteRule ^(.*)$ public/$1 [L]', $root);
        $this->assertStringContainsString('!^/public(/|$)', $root);
        $this->assertStringContainsString('\.env', $root);

        $public = (string) file_get_contents(base_path('public/.htaccess'));
        $this->assertStringContainsString('(^|/)\.(?!well-known/)', $public);
        $this->assertStringContainsString('immutable', $public);
        $this->assertStringContainsString('DEFLATE', $public);
    }
}
