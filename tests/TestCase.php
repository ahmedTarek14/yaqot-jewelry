<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mcamara\LaravelLocalization\LaravelLocalization;

abstract class TestCase extends BaseTestCase
{
    /**
     * LaravelLocalization picks the route prefix while routes are registered,
     * i.e. when the application boots — before a test sends its request.
     * Reboot the application with the given locale so /en routes exist.
     */
    protected function refreshApplicationWithLocale(string $locale): void
    {
        self::tearDown();
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'='.$locale);
        self::setUp();
    }

    protected function tearDown(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY);
        parent::tearDown();
    }
}
