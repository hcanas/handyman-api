<?php

namespace Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected string $url;

    protected array $valid_input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('register');

        $this->valid_input = [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ];
    }

    public function test_can_register_with_valid_input(): void
    {
        $response = $this->postJson($this->url, $this->valid_input);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'name' => $this->valid_input['name'],
            'email' => $this->valid_input['email'],
        ]);
    }

    #[DataProvider('invalidNameProvider')]
    public function test_invalid_name_fails_validation(?string $name = null): void
    {
        $invalid_input = [
            ...$this->valid_input,
            'name' => $name,
        ];

        $response = $this->postJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    #[DataProvider('invalidEmailProvider')]
    public function test_invalid_email_fails_validation(?string $email = null): void
    {
        if ($email === 'duplicate') {
            User::factory()->create(['email' => $this->valid_input['email']]);
            $email = $this->valid_input['email'];
        }

        $invalid_input = [
            ...$this->valid_input,
            'email' => $email,
        ];

        $response = $this->postJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    #[DataProvider('invalidPasswordProvider')]
    public function test_invalid_password_fails_validation(array $input): void
    {
        $invalid_input = [
            ...$this->valid_input,
            'password' => $input['password'] ?? null,
            'password_confirmation' => $input['password_confirmation'] ?? null,
        ];

        $response = $this->postJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    public static function invalidNameProvider(): array
    {
        return [
            'undefined' => [],
            'empty' => [''],
            'characters_over_limit' => [str_repeat('a', 256)],
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
            'undefined' => [[]],
            'empty' => [['password' => '', 'password_confirmation' => '']],
            'too_short' => [['password' => '123', 'password_confirmation' => '123']],
            'mismatch' => [['password' => '12345678', 'password_confirmation' => '87654321']],
        ];
    }
}
