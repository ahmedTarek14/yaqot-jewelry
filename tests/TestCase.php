<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Storage;
use Mcamara\LaravelLocalization\LaravelLocalization;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Storage::fake() cleans one shared folder; test runs started at the same time (e.g. by
        // parallel agents) would wipe each other's files. A per-process token gives each run its
        // own folder (storage/framework/testing/disks/public_test_{pid}). Outside `artisan test
        // --parallel` the token is used by Storage::fake() only.
        ParallelTesting::resolveTokenUsing(fn () => (string) getmypid());

        // Safety net: no test may ever write to the real storage/app/public.
        Storage::fake('public');
    }

    /**
     * LaravelLocalization picks the route prefix while routes are registered,
     * i.e. when the application boots — before a test sends its request.
     * Reboot the application with the given locale so /en routes exist.
     *
     * $this->setUp() (not self::) so the test class's own setUp runs again on
     * the new application, e.g. its Storage::fake().
     */
    protected function refreshApplicationWithLocale(string $locale): void
    {
        $this->tearDown();
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'='.$locale);
        $this->setUp();
    }

    protected function tearDown(): void
    {
        // Remove this process's fake disk folder (see setUp).
        (new Filesystem)->deleteDirectory(storage_path('framework/testing/disks/public_test_'.getmypid()));

        putenv(LaravelLocalization::ENV_ROUTE_KEY);
        parent::tearDown();
    }
}
