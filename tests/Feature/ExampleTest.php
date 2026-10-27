<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root has no content of its own: it redirects to the default locale.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertRedirect();

        $this->refreshApplicationWithLocale('en');

        $this->get('/en')->assertOk();
    }
}
