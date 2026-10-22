<?php

namespace Tests\Integration\Access;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReservationsAccessTest extends TestCase
{
    private const PROFILES = [User::PROFILE_STUDENT, User::PROFILE_TEACHER, User::PROFILE_ADMIN];

    private Client $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = new Client([
            'allow_redirects' => false,
            'http_errors' => false,
            'base_uri' => 'http://web:8080'
        ]);

        foreach (self::PROFILES as $profile) {
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

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function authenticatedRoutesProvider(): array
    {
        return [
            'index' => ['GET', '/reservations'],
            'paginate' => ['GET', '/reservations/page/2'],
        ];
    }

    #[DataProvider('authenticatedRoutesProvider')]
    public function test_should_not_access_the_route_if_not_authenticated(string $method, string $uri): void
    {
        $response = $this->client->request($method, $uri);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function test_every_profile_should_access_the_list(): void
    {
        foreach (self::PROFILES as $profile) {
            $response = $this->client->get('/reservations', ['cookies' => $this->login($profile)]);

            $this->assertEquals(200, $response->getStatusCode(), $profile);
            $this->assertStringContainsString('Minhas Reservas', (string) $response->getBody());
        }
    }

    public function test_invalid_page_should_be_not_found(): void
    {
        $response = $this->client->get('/reservations/page/0', ['cookies' => $this->login(User::PROFILE_STUDENT)]);

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
