<?php

namespace Tests\Feature;

use Tests\TestCase;

class TunnelUrlsTest extends TestCase
{
    public function test_local_proxy_generates_https_assets_and_form_actions(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['Host' => 'preview-8000.asse.devtunnels.ms', 'X-Forwarded-Proto' => 'https'])
            ->get('http://preview-8000.asse.devtunnels.ms/login')->assertOk()
            ->assertSee('https://preview-8000.asse.devtunnels.ms/css/login.css', false)
            ->assertSee('https://preview-8000.asse.devtunnels.ms/login', false);
    }

    public function test_direct_local_http_still_works(): void
    {
        $this->withHeaders(['Host' => 'localhost:8000'])->get('http://localhost:8000/login')->assertOk()
            ->assertSee('http://localhost:8000/css/login.css', false);
    }

    public function test_remote_client_cannot_spoof_proxy_headers(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders(['Host' => 'localhost', 'X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'untrusted.test'])
            ->get('/login')->assertOk()->assertSee('http://localhost/css/login.css', false)
            ->assertDontSee('untrusted.test');
    }
}
