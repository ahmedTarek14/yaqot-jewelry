<?php

/**
 * Builds the upload package for InfinityFree (see docs/DEPLOY.md). Nothing is uploaded.
 *
 *   php tools/deploy/build.php --out=D:\yaqot-release [--no-route-cache] [--zip-mb=9]
 *
 * Produces, OUTSIDE the project:
 *   <out>/htdocs/        the tree to upload into the host's htdocs folder (FTP)
 *   <out>/zips/          the same tree split into zips under 10 MB (for the control panel's file manager)
 *   <out>/MANIFEST.txt   every file with its size (to compare with the host after the upload)
 *   <out>/REPORT.txt     the numbers below
 *
 * What it does: copies the application (no tests, docs, .git, .claude, node_modules, demo seeders
 * or factories, design sources or local uploads), runs `composer install --no-dev` with the
 * NON-optimized autoloader, prunes the packages' own tests/docs, builds the route cache and the
 * package manifest, adds the .htaccess protection files, and checks the host's limits: file count,
 * PHP/HTML/JS files over 1 MB (silently deleted by the host), other files over 10 MB, .htaccess
 * files over 10 kB. It exits with an error when a limit is broken or a forbidden file is present.
 *
 * Never shipped: .env, caches that bake in this machine's paths (config, views), storage contents.
 */
const MB = 1048576;

$root = str_replace('\\', '/', dirname(__DIR__, 2));
$options = getopt('', ['out:', 'no-route-cache', 'zip-mb::']);

if (empty($options['out'])) {
    fail('Usage: php tools/deploy/build.php --out=<folder outside the project> [--no-route-cache] [--zip-mb=9]');
}

$out = str_replace('\\', '/', rtrim((string) $options['out'], '/\\'));
$zipBudget = (float) ($options['zip-mb'] ?? 9) * MB;
$routeCache = ! array_key_exists('no-route-cache', $options);

if ($out === '' || str_starts_with(strtolower($out.'/'), strtolower($root.'/'))) { // Windows paths ignore case
    fail("The output folder must be outside the project ($root).");
}

$stage = "$out/htdocs";
$marker = "$out/.yaqot-deploy-build";

if (is_dir($out) && count(array_diff(scandir($out), ['.', '..'])) > 0 && ! is_file($marker)) {
    fail("$out exists, is not empty and was not made by this script: refusing to delete anything in it.");
}

step('Preparing '.$out);
foreach (['htdocs', 'zips'] as $folder) {
    removeTree("$out/$folder");
}
@unlink("$out/MANIFEST.txt");
@unlink("$out/REPORT.txt");
mkdirs($stage);
mkdirs("$out/zips");
file_put_contents($marker, "Made by tools/deploy/build.php; safe to delete and rebuild.\n");

// ---------------------------------------------------------------------------------------------
step('Copying the application');

$include = ['app', 'bootstrap', 'config', 'database/migrations', 'lang', 'Modules', 'public', 'resources', 'routes'];
$files = ['composer.json', 'composer.lock', 'modules_statuses.json', '.env.production.example', 'artisan'];

$skip = [
    '#(^|/)\.(git|gitignore|gitattributes|gitkeep|editorconfig|DS_Store)$#',
    '#(^|/)(node_modules|\.idea|\.vscode)(/|$)#',
    '#^bootstrap/cache/#',                         // rebuilt below
    '#^public/(storage|hot|build)(/|$)#',          // local uploads / dev leftovers
    '#^Modules/[^/]+/(tests|database/(factories|seeders))(/|$)#',
    '#^public/assets/images/hero/[^/]+\.png$#',    // design sources are never served
];

foreach ($include as $path) {
    copyTree("$root/$path", "$stage/$path", $skip, $path);
}

foreach ($files as $file) {
    copy("$root/$file", "$stage/$file");
}

// Composer here must not build the optimized autoloader (its two big files approach the host's
// 1 MB limit for PHP files); this edits only the copy in the package.
$composer = json_decode(file_get_contents("$stage/composer.json"), true, 512, JSON_THROW_ON_ERROR);
$composer['config']['optimize-autoloader'] = false;
unset($composer['config']['classmap-authoritative']);
file_put_contents("$stage/composer.json", json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

// ---------------------------------------------------------------------------------------------
step('composer install --no-dev (non-optimized autoloader)');
run('composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist', $stage);

$php = PHP_BINARY;
$artisanEnv = ['APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_KEY' => 'base64:'.base64_encode(str_repeat('0', 32))];

// artisan boots the app, which may write a log line or a compiled view: give it the folders, then empty them.
foreach (['storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $folder) {
    mkdirs("$stage/$folder");
}

step('Package manifest'.($routeCache ? ' and route cache (route:trans:cache)' : ''));
run(escapeshellarg($php).' artisan package:discover --ansi', $stage, $artisanEnv);

if ($routeCache) {
    // Per-language route files (never plain route:cache: it would make every /ar and /en URL a 404).
    // They hold class names and URLs only, no paths of this machine, so they are safe to ship.
    run(escapeshellarg($php).' artisan route:trans:cache --ansi', $stage, $artisanEnv);
}

// Caches that DO bake in this machine's paths or .env values are never shipped.
foreach (glob("$stage/bootstrap/cache/*") as $cached) {
    $name = basename($cached);

    if (! in_array($name, ['packages.php'], true) && ! ($routeCache && preg_match('/^routes-v7(_[a-z]{2})?\.php$/', $name))) {
        unlink($cached);
    }
}

unlink("$stage/artisan");

removeTree("$stage/storage");

// ---------------------------------------------------------------------------------------------
step('Pruning the packages (their tests, docs and bin proxies)');
$pruned = pruneVendor("$stage/vendor");
echo "  removed $pruned entries\n";

// ---------------------------------------------------------------------------------------------
step('Protection files and the storage skeleton');

$templates = __DIR__.'/templates';
copy("$templates/htdocs.htaccess", "$stage/.htaccess");

foreach (['app', 'bootstrap', 'config', 'database', 'lang', 'Modules', 'resources', 'routes', 'vendor', 'storage'] as $folder) {
    mkdirs("$stage/$folder");
    copy("$templates/deny.htaccess", "$stage/$folder/.htaccess");
}

// Folders Laravel writes into. The deny file keeps each one alive in a zip or an FTP upload.
foreach (['storage/app/public', 'storage/app/private', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $folder) {
    mkdirs("$stage/$folder");
    copy("$templates/deny.htaccess", "$stage/$folder/.htaccess");
}

// Uploads go straight into public/uploads (PUBLIC_DISK_ROOT=uploads): a real folder, no symlink.
mkdirs("$stage/public/uploads");
copy("$templates/public-uploads.htaccess", "$stage/public/uploads/.htaccess");

// ---------------------------------------------------------------------------------------------
step('Checking the package');

$all = listFiles($stage);
$dirCount = countDirs($stage);
$problems = [];
$bigPhp = [];
$bigText = [];
$bigOther = [];
$bigHtaccess = [];
$bytes = 0;

foreach ($all as $relative => $size) {
    $bytes += $size;
    $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

    if ($extension === 'php' && $size > MB) {
        $bigPhp[$relative] = $size;
    } elseif (in_array($extension, ['html', 'htm', 'js'], true) && $size > MB) {
        $bigText[$relative] = $size;
    } elseif (basename($relative) === '.htaccess' && $size > 10 * 1024) {
        $bigHtaccess[$relative] = $size;
    } elseif ($size > 10 * MB) {
        $bigOther[$relative] = $size;
    }

    // Anything that must never be on the server.
    if (preg_match('#(^|/)(\.env|\.git|\.claude|\.idea|node_modules|tests?|docs|design|DemoCatalogSeeder\.php|DatabaseSeeder\.php|[^/]*Factory\.php|phpunit\.xml(\.dist)?|CLAUDE\.md|README\.md)(/|$)#i', $relative)
        && $relative !== '.env.production.example'
        && ! str_starts_with($relative, 'vendor/')) {
        $problems[] = "forbidden file in the package: $relative";
    }
}

if (is_file("$stage/.env")) {
    $problems[] = '.env is in the package';
}

foreach ($bigPhp as $file => $size) {
    $problems[] = sprintf('PHP file over 1 MB (the host deletes it silently): %s (%.2f MB)', $file, $size / MB);
}

foreach ($bigText as $file => $size) {
    $problems[] = sprintf('HTML/JS file over 1 MB (the host deletes it): %s (%.2f MB)', $file, $size / MB);
}

foreach ($bigOther as $file => $size) {
    $problems[] = sprintf('file over 10 MB: %s (%.2f MB)', $file, $size / MB);
}

foreach ($bigHtaccess as $file => $size) {
    $problems[] = sprintf('.htaccess over 10 kB (the host deletes it): %s', $file);
}

$entries = count($all) + $dirCount;

if ($entries > 30000) {
    $problems[] = "$entries files and folders: over the host's 30,000";
}

$largestPhp = [];
foreach ($all as $relative => $size) {
    if (str_ends_with($relative, '.php')) {
        $largestPhp[$relative] = $size;
    }
}
arsort($largestPhp);

$topLevel = [];
foreach ($all as $relative => $size) {
    $top = str_contains($relative, '/') ? explode('/', $relative)[0] : '(root files)';
    $topLevel[$top]['files'] = ($topLevel[$top]['files'] ?? 0) + 1;
    $topLevel[$top]['bytes'] = ($topLevel[$top]['bytes'] ?? 0) + $size;
}
ksort($topLevel);

$report = [];
$report[] = 'Yaqot upload package, built '.date('Y-m-d H:i:s');
$report[] = sprintf('Files: %d   Folders: %d   Entries (the host counts both): %d of 30,000   Size: %.1f MB', count($all), $dirCount, $entries, $bytes / MB);
$report[] = sprintf('PHP files over 1 MB: %d   HTML/JS files over 1 MB: %d   other files over 10 MB: %d   .htaccess over 10 kB: %d', count($bigPhp), count($bigText), count($bigOther), count($bigHtaccess));
$report[] = 'Largest PHP files:';
foreach (array_slice($largestPhp, 0, 5, true) as $file => $size) {
    $report[] = sprintf('  %7.1f KB  %s', $size / 1024, $file);
}
$report[] = 'By folder:';
foreach ($topLevel as $name => $info) {
    $report[] = sprintf('  %-16s %6d files  %7.1f MB', $name, $info['files'], $info['bytes'] / MB);
}
$report[] = 'Autoloader: '.(filesize("$stage/vendor/composer/autoload_static.php") < 200000 ? 'non-optimized' : 'OPTIMIZED (unexpected)')
    .sprintf(' (autoload_static.php %.0f KB, autoload_classmap.php %.0f KB)', filesize("$stage/vendor/composer/autoload_static.php") / 1024, filesize("$stage/vendor/composer/autoload_classmap.php") / 1024);
$report[] = 'Route cache: '.(glob("$stage/bootstrap/cache/routes-v7*.php") ? implode(', ', array_map('basename', glob("$stage/bootstrap/cache/routes-v7*.php"))) : 'none');

foreach ($report as $line) {
    echo "  $line\n";
}

// ---------------------------------------------------------------------------------------------
step('Manifest');
$manifest = [];
foreach ($all as $relative => $size) {
    $manifest[] = sprintf('%10d  %s', $size, $relative);
}
file_put_contents("$out/MANIFEST.txt", implode("\n", $manifest)."\n");

// ---------------------------------------------------------------------------------------------
step('Zips under 10 MB');
$zips = makeZips($stage, "$out/zips", $all, $zipBudget);
foreach ($zips as $zip => $size) {
    $line = sprintf('  %-34s %5.2f MB', $zip, $size / MB);
    echo $line."\n";
    $report[] = trim($line);
}

$report[] = $problems === [] ? 'RESULT: OK' : 'RESULT: FAILED';
foreach ($problems as $problem) {
    $report[] = '  PROBLEM: '.$problem;
}
file_put_contents("$out/REPORT.txt", implode("\n", $report)."\n");

if ($problems !== []) {
    foreach ($problems as $problem) {
        fwrite(STDERR, "PROBLEM: $problem\n");
    }

    exit(1);
}

echo "\nDone. Upload $stage (FTP) or the zips in $out/zips. Nothing was uploaded.\n";

// =============================================================================================

function step(string $message): void
{
    echo "\n== $message\n";
}

function fail(string $message): never
{
    fwrite(STDERR, $message."\n");
    exit(1);
}

function mkdirs(string $path): void
{
    if (! is_dir($path) && ! mkdir($path, 0777, true) && ! is_dir($path)) {
        fail("Cannot create $path");
    }
}

function removeTree(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item) {
        $item->isDir() && ! $item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }

    @rmdir($path);
}

/**
 * @param  list<string>  $skip  regexes matched against the path relative to the project
 */
function copyTree(string $from, string $to, array $skip, string $relative): void
{
    if (! is_dir($from)) {
        fail("Missing $from");
    }

    mkdirs($to);

    foreach (scandir($from) as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }

        $childRelative = "$relative/$name";

        foreach ($skip as $pattern) {
            if (preg_match($pattern, $childRelative)) {
                continue 2;
            }
        }

        is_dir("$from/$name") ? copyTree("$from/$name", "$to/$name", $skip, $childRelative) : copy("$from/$name", "$to/$name");
    }
}

/**
 * @param  array<string, string>  $env
 */
function run(string $command, string $cwd, array $env = []): void
{
    $process = proc_open($command, [1 => STDOUT, 2 => STDERR], $pipes, $cwd, $env === [] ? null : array_merge(getenv(), $env));

    if (! is_resource($process) || proc_close($process) !== 0) {
        fail("Command failed: $command");
    }
}

/**
 * Removes what a running site never reads from vendor/. Returns the number of entries removed.
 */
function pruneVendor(string $vendor): int
{
    removeTree("$vendor/bin");
    $removed = 0;
    $directories = '#^(tests?|docs?|examples?|\.github|\.git|\.idea|benchmarks?|\.phpstorm\.meta\.php|build)$#i';
    $filePattern = '#^(phpunit\.xml(\.dist)?|\.?php[-_]cs[-_]fixer.*|phpstan.*|psalm.*|\.gitattributes|\.gitignore|\.editorconfig|\.styleci\.yml|\.travis\.yml|appveyor\.yml|codecov\.yml|\.scrutinizer\.yml|Makefile|CHANGELOG.*|CHANGES.*|UPGRADE.*|CONTRIBUTING.*|SECURITY\.md|README.*|composer\.lock|\.php_cs.*|infection\.json.*|rector\.php|ecs\.php|\.codecov\.yml)$#i';

    $walk = function (string $directory) use (&$walk, &$removed, $directories, $filePattern, $vendor): void {
        foreach (scandir($directory) as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $path = "$directory/$name";

            if (is_dir($path)) {
                // A package's top folder is never pruned by name (vendor/name/package).
                $depth = substr_count(substr($path, strlen($vendor)), '/');

                if ($depth > 2 && preg_match($directories, $name)) {
                    $removed += countEntries($path);
                    removeTree($path);
                } else {
                    $walk($path);
                }
            } elseif (preg_match($filePattern, $name) && substr_count(substr($path, strlen($vendor)), '/') > 2) {
                unlink($path);
                $removed++;
            }
        }
    };

    $walk($vendor);

    return $removed;
}

function countEntries(string $path): int
{
    $count = 1;

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $_) {
        $count++;
    }

    return $count;
}

/**
 * @return array<string, int> relative path => size, for every file
 */
function listFiles(string $base): array
{
    $files = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile()) {
            $files[ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($base))), '/')] = $file->getSize();
        }
    }

    ksort($files);

    return $files;
}

function countDirs(string $base): int
{
    $count = 0;

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
        if ($item->isDir()) {
            $count++;
        }
    }

    return $count;
}

/**
 * Splits the tree into zips, each under 10 MB. Weight is a guess (text compresses, images do not);
 * the real size is checked after writing and a zip that is too big is split again.
 *
 * @param  array<string, int>  $files
 * @return array<string, int> zip name => size
 */
function makeZips(string $stage, string $zipDir, array $files, float $budget): array
{
    $compressible = ['php', 'js', 'css', 'json', 'md', 'txt', 'svg', 'xml', 'html', 'htaccess', 'lock', 'stub'];
    $groups = [];
    $current = [];
    $weight = 0.0;

    foreach ($files as $relative => $size) {
        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        $cost = $size * (in_array($extension, $compressible, true) || basename($relative) === '.htaccess' ? 0.3 : 1.0);

        if ($weight + $cost > $budget && $current !== []) {
            $groups[] = $current;
            $current = [];
            $weight = 0.0;
        }

        $current[] = $relative;
        $weight += $cost;
    }

    $groups[] = $current;

    // The root .htaccess goes in the last zip, so a half-uploaded site is not "live" on a half rule set.
    $final = [];
    foreach ($groups as $group) {
        $final[] = array_values(array_filter($group, fn ($f) => $f !== '.htaccess'));
    }
    $final[array_key_last($final)][] = '.htaccess';

    $written = [];
    $queue = $final;
    $index = 0;
    $total = 0;

    // Write each group; a group whose zip is over the limit is halved and queued again.
    $tmp = [];
    while ($queue !== []) {
        $group = array_shift($queue);
        $name = sprintf('%s/part-%03d.zip', $zipDir, ++$index);
        $zip = new ZipArchive;

        if ($zip->open($name, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            fail("Cannot write $name");
        }

        foreach ($group as $relative) {
            $zip->addFile("$stage/$relative", $relative);
            $zip->setCompressionName($relative, ZipArchive::CM_DEFLATE);
        }

        $zip->close();

        if (filesize($name) > 10 * MB - 4096 && count($group) > 1) {
            unlink($name);
            $index--;
            $half = intdiv(count($group), 2);
            array_unshift($queue, array_slice($group, $half));
            array_unshift($queue, array_slice($group, 0, $half));

            continue;
        }

        $tmp[$name] = filesize($name);
    }

    // Rename to part-NN-of-MM.zip now that the count is known.
    $total = count($tmp);
    $number = 0;

    foreach ($tmp as $name => $size) {
        $final = sprintf('%s/yaqot-%02d-of-%02d.zip', $zipDir, ++$number, $total);
        rename($name, $final);
        $written[basename($final)] = $size;
    }

    return $written;
}
