<?php

namespace Tests\Integration\Access;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResourcesAccessTest extends TestCase
{
    private const AREAS = [
        User::PROFILE_STUDENT => '/student',
        User::PROFILE_TEACHER => '/teacher',
    ];

    private Client $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = new Client([
            'allow_redirects' => false,
            'http_errors' => false,
            'base_uri' => 'http://web:8080'
        ]);

        foreach ([User::PROFILE_STUDENT, User::PROFILE_TEACHER, User::PROFILE_ADMIN] as $profile) {
            $user = new User([
                'profile' => $profile,
                'name' => 'User ' . $profile,
                'email' => $profile . '@example.com',
                'password' => '123456',
                'password_confirmation' => '123456'
            ]);
            $user->save();
        }

        $category = new ResourceCategory(['name' => 'Laboratório']);
        $category->save();
        $resource = new Resource(['resource_category_id' => $category->id, 'name' => 'Lab 1', 'active' => 1]);
        $resource->save();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function adminRoutesProvider(): array
    {
        return [
            'index' => ['GET', '/resources'],
            'show' => ['GET', '/resources/1'],
        ];
    }

    #[DataProvider('adminRoutesProvider')]
    public function test_should_not_access_the_route_if_not_authenticated(string $method, string $uri): void
    {
        $response = $this->client->request($method, $uri);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    #[DataProvider('adminRoutesProvider')]
    public function test_should_not_access_the_route_if_not_admin(string $method, string $uri): void
    {
        foreach (self::AREAS as $profile => $ownArea) {
            $response = $this->client->request($method, $uri, ['cookies' => $this->login($profile)]);

            $this->assertEquals(302, $response->getStatusCode(), $profile);
            $this->assertEquals($ownArea, $response->getHeaderLine('Location'), $profile);
        }
    }

    #[DataProvider('adminRoutesProvider')]
    public function test_admin_should_access_the_route(string $method, string $uri): void
    {
        $response = $this->client->request($method, $uri, ['cookies' => $this->login(User::PROFILE_ADMIN)]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_nonexistent_resource_should_be_not_found(): void
    {
        $response = $this->client->get('/resources/999', ['cookies' => $this->login(User::PROFILE_ADMIN)]);

        $this->assertEquals(404, $response->getStatusCode());
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
