<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_assets_use_https_when_request_is_forwarded_by_a_tls_terminating_proxy(): void
    {
        $response = $this->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/');
        $assetUrl = asset('css/global.css');

        $response->assertOk()
            ->assertSee('href="'.$assetUrl, false)
            ->assertDontSee('href="'.preg_replace('/^https:/', 'http:', $assetUrl), false);

        $this->assertStringStartsWith('https://', $assetUrl);
    }
}
