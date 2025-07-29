<?php

namespace Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected string $url;

    protected User $user;

    protected string $token;

    protected array $valid_input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('update-password');

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('web')->plainTextToken;

        $this->valid_input = [
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ];
    }

    public function test_can_update_password(): void
    {
        $response = $this->withToken($this->token)->patchJson($this->url, $this->valid_input);

        $this->user->refresh();

        $response->assertOk();
        $this->assertTrue(Hash::check($this->valid_input['password'], $this->user->password));
    }

    public function test_guests_cannot_update_password(): void
    {
        $response = $this->patchJson($this->url, $this->valid_input);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidPasswordProvider')]
    public function test_invalid_password_fails_validation(array $input): void
    {
        $invalid_input = [
            'password' => $input['password'] ?? null,
            'password_confirmation' => $input['password_confirmation'] ?? null,
        ];

        $response = $this->withToken($this->token)->patchJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
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
