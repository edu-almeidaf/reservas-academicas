<?php

namespace Tests\Integration\Access;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Tests\TestCase;

class AreasAccessTest extends TestCase
{
    private const AREAS = [
        User::PROFILE_STUDENT => '/student',
        User::PROFILE_TEACHER => '/teacher',
        User::PROFILE_ADMIN => '/admin',
    ];

    private Client $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = new Client([
            'allow_redirects' => false,
            'base_uri' => 'http://web:8080'
        ]);

        foreach (array_keys(self::AREAS) as $profile) {
            $user = new User([
                'profile' => $profile,
                'name' => 'User ' . $profile,
                'email' => $profile . '@example.com',
                'password' => '123456',
                'password_confirmation' => '123456'
            ]);
            $user->save();
        }
    }

    public function test_should_not_access_the_areas_if_not_authenticated(): void
    {
        foreach (self::AREAS as $area) {
            $response = $this->client->get($area);

            $this->assertEquals(302, $response->getStatusCode());
            $this->assertEquals('/login', $response->getHeaderLine('Location'));
        }
    }

    public function test_should_access_only_the_own_area(): void
    {
        foreach (self::AREAS as $profile => $ownArea) {
            $cookieJar = $this->login($profile);

            foreach (self::AREAS as $area) {
                $response = $this->client->get($area, ['cookies' => $cookieJar]);

                if ($area === $ownArea) {
                    $this->assertEquals(200, $response->getStatusCode());
                } else {
                    $this->assertEquals(302, $response->getStatusCode());
                    $this->assertEquals($ownArea, $response->getHeaderLine('Location'));
                }
            }
        }
    }

    public function test_should_redirect_root_to_the_own_area(): void
    {
        foreach (self::AREAS as $profile => $ownArea) {
            $response = $this->client->get('/', ['cookies' => $this->login($profile)]);

            $this->assertEquals(302, $response->getStatusCode());
            $this->assertEquals($ownArea, $response->getHeaderLine('Location'));
        }
    }

    private function login(string $profile): CookieJar
    {
        $cookieJar = new CookieJar();

        $this->client->post('/login', [
            'form_params' => [
                'user[email]' => $profile . '@example.com',
                'user[password]' => '123456'
            ],
            'cookies' => $cookieJar
        ]);

        return $cookieJar;
    }
}
