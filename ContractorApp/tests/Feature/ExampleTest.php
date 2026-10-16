<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The home page requires signing in.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk();
    }
}
