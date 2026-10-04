<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_public(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertViewIs('home');
    }
}
