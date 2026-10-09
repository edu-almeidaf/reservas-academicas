<?php

namespace Tests\Integration\Access;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class AuthenticationAccessTest extends TestCase
{
    private Client $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = new Client([
            'allow_redirects' => false,
            'base_uri' => 'http://web:8080'
        ]);
    }

    public function test_should_access_the_login_route_if_not_authenticated(): void
    {
        $response = $this->client->get('/login');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Entrar', (string) $response->getBody());
    }

    public function test_should_not_access_the_logout_route_if_not_authenticated(): void
    {
        $response = $this->client->post('/logout');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }
}
