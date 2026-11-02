<?php

namespace Tests\Integration\Access;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubdivisionsAccessTest extends TestCase
{
    private const JSON_HEADERS = ['Accept' => 'application/json'];

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
        $subdivision = $resource->subdivisions()->new(['name' => 'Bancada 1']);
        $subdivision->save();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function authenticatedRoutesProvider(): array
    {
        return [
            'index' => ['GET', '/resources/1/subdivisions'],
        ];
    }

    #[DataProvider('authenticatedRoutesProvider')]
    public function test_should_not_access_the_route_if_not_authenticated(string $method, string $uri): void
    {
        $response = $this->client->request($method, $uri, ['headers' => self::JSON_HEADERS]);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function test_every_profile_should_get_the_subdivisions_as_json(): void
    {
        foreach ([User::PROFILE_STUDENT, User::PROFILE_TEACHER, User::PROFILE_ADMIN] as $profile) {
            $response = $this->client->get('/resources/1/subdivisions', [
                'headers' => self::JSON_HEADERS,
                'cookies' => $this->login($profile),
            ]);

            $this->assertEquals(200, $response->getStatusCode(), $profile);
            $this->assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'), $profile);

            $json = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame([['id' => 1, 'name' => 'Bancada 1']], $json['subdivisions'], $profile);
        }
    }

    public function test_nonexistent_resource_should_be_not_found(): void
    {
        $response = $this->client->get('/resources/999/subdivisions', [
            'headers' => self::JSON_HEADERS,
            'cookies' => $this->login(User::PROFILE_STUDENT),
        ]);

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
