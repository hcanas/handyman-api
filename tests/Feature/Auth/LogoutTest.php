<?php

namespace Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('logout');

        $this->auth_user = User::factory()->create();
        $this->token = $this->auth_user->createToken('web')->plainTextToken;

        $this->headers = ['X-Client-Platform' => 'web'];
    }

    public function test_can_logout_if_user_is_authenticated(): void
    {
        $this->headers['Authorization'] = 'Bearer ' . $this->token;

        $response = $this->postJson($this->url, [], $this->headers);

        $response->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => explode('|', $this->token)[0],
            'tokenable_id' => $this->auth_user->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_guests_cannot_logout(): void
    {
        $response = $this->postJson($this->url, [], $this->headers);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidTokenProvider')]
    public function test_returns_401_if_token_is_invalid(?string $token = null): void
    {
        if ($token === 'expired') {
            $token_id = explode('|', $this->token)[0];
            PersonalAccessToken::find($token_id)->update(['expires_at' => now()->subMinute()]);
        }

        $this->headers['Authorization'] = 'Bearer ' . $token;

        $response = $this->postJson($this->url, [], $this->headers);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidClientPlatformProvider')]
    public function test_returns_400_if_client_platform_is_invalid(?string $platform = null): void
    {
        $this->headers['X-Client-Platform'] = $platform;
        $this->headers['Authorization'] = 'Bearer ' . $this->token;

        $response = $this->postJson($this->url, [], $this->headers);

        $response->assertBadRequest();
    }

    public static function invalidTokenProvider(): array
    {
        return [
            'undefined' => [],
            'token_does_not_exist' => [fake()->sha256()],
            'expired_token' => ['expired'],
        ];
    }

    public static function invalidClientPlatformProvider(): array
    {
        return [
            'undefined' => [],
            'empty' => [''],
            'invalid' => ['invalid'],
        ];
    }
}
