<?php

namespace Tests\Integration\Access;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class UsersAccessTest extends TestCase
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

    public function test_should_access_the_signup_route_if_not_authenticated(): void
    {
        $response = $this->client->get('/signup');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Cadastrar', (string) $response->getBody());
    }
}
