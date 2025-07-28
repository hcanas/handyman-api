<?php

namespace Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $user;

    protected array $credentials;

    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('login');

        $this->credentials = [
            'email' => fake()->safeEmail(),
            'password' => fake()->password(8),
        ];

        $this->user = User::factory()->create($this->credentials);

        $this->headers = ['X-Client-Platform' => 'web'];
    }

    public function test_can_login_via_web_client(): void
    {
        $response = $this->postJson($this->url, $this->credentials, $this->headers);

        $response->assertOk();
        $response->assertCookie('token');
    }

    public function test_can_login_via_mobile_client(): void
    {
        $this->headers['X-Client-Platform'] = 'mobile';

        $response = $this->postJson($this->url, $this->credentials, $this->headers);

        $response->assertOk();
        $response->assertJsonStructure(['token']);
    }

    #[DataProvider('invalidClientPlatformProvider')]
    public function test_returns_400_if_client_platform_is_invalid(array $data): void
    {
        $headers = [];

        if (isset($data['platform'])) {
            $headers['X-Client-Platform'] = $data['platform'];
        }

        $response = $this->postJson($this->url, $this->credentials, $headers);

        $response->assertBadRequest();
    }

    #[Dataprovider('invalidEmailProvider')]
    public function test_invalid_email_fails_validation(?string $email = null): void
    {
        $credentials = ['password' => $this->credentials['password']];

        if ($email !== null) {
            $credentials['email'] = $email;
        }

        $response = $this->postJson($this->url, $credentials, $this->headers);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['auth']);
    }

    #[DataProvider('invalidPasswordProvider')]
    public function test_invalid_password_fails_validation(?string $password = null): void
    {
        $credentials = ['email' => $this->credentials['email']];

        if ($password !== null) {
            $credentials['password'] = $password;
        }

        $response = $this->postJson($this->url, $credentials, $this->headers);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['auth']);
    }

    #[DataProvider('incorrectCredentialsProvider')]
    public function test_returns_401_if_incorrect_credentials(array $input): void
    {
        $credentials = [
            'email' => $input['email'] ?? $input['registered_email'],
            'password' => $input['password'],
        ];

        if (array_key_exists('registered_email', $input)) {
            User::factory()->create(['email' => $input['registered_email']]);
        }

        $response = $this->postJson($this->url, $credentials, $this->headers);

        $response->assertUnauthorized();
        $response->assertJsonStructure(['message']);
    }

    public static function invalidClientPlatformProvider(): array
    {
        return [
            'undefined' => [[]],
            'empty' => [['platform' => '']],
            'invalid' => [['platform' => 'invalid']],
        ];
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'undefined' => [],
            'empty' => ['email' => ''],
            'missing_at_symbol' => ['email' => 'abc.def.com'],
            'missing_username' => ['email' => '@domain.com'],
            'missing_domain' => ['email' => 'abc@'],
            'double_at_symbols' => ['email' => 'abc@@domain.com'],
            'space_in_email' => ['email' => 'abc def@domain.com'],
            'starting_dot' => ['email' => '.abc@domain.com'],
            'ending_dot' => ['email' => 'abc.@domain.com'],
            'double_dot' => ['email' => 'abc..def@domain.com'],
            'special_chars' => ['email' => 'abc@domain!.com'],
            'no_domain' => ['email' => 'abc@.com'],
            'just_at' => ['email' => '@'],
            'characters_over_limit' => ['email' => str_repeat('a', 256)],
        ];
    }

    public static function invalidPasswordProvider(): array
    {
        return [
            'undefined' => [],
            'empty' => ['password' => ''],
            'short' => ['password' => str_repeat('a', 6)],
            'character_over_limit' => ['password' => str_repeat('a', 256)],
        ];
    }

    public static function incorrectCredentialsProvider(): array
    {
        return [
            'unregistered_email' => [['email' => 'unregistered@email.com', 'password' => str_repeat('a', 8)]],
            'incorrect_password' => [['registered_email' => fake()->safeEmail(), 'password' => str_repeat('a', 8)]],
        ];
    }
}
