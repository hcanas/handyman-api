<?php

namespace Tests\Feature\Department;

use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateDepartmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('departments.store');

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->token = $this->auth_user->createToken('web')->plainTextToken;
    }

    public function test_admins_can_create_department(): void
    {
        $name = fake()->word();

        $response = $this->withToken($this->token)->postJson($this->url, ['name' => $name]);

        $response->assertCreated();
        $this->assertDatabaseHas('departments', ['name' => $name]);
    }

    #[DataProvider('nonAdminUsersProvider')]
    public function test_non_admins_cannot_create_department(UserRole $role): void
    {
        $this->auth_user->update(['role' => $role->value]);

        $response = $this->withToken($this->token)->postJson($this->url);

        $response->assertForbidden();
    }

    public function test_undesignated_users_cannot_create_department(): void
    {
        $this->auth_user->update(['role' => null]);

        $response = $this->withToken($this->token)->postJson($this->url);

        $response->assertForbidden();
    }

    public function test_guests_cannot_create_department(): void
    {
        $response = $this->postJson($this->url, ['name' => fake()->word()]);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidInputProvider')]
    public function test_fails_all_validation_rules(array $input): void
    {
        if (isset($input['duplicate'])) {
            Department::factory()->create(['name' => $input['duplicate']]);
            $payload = ['name' => $input['duplicate']];
        } elseif (isset($input['name'])) {
            $payload = ['name' => $input['name']];
        } else {
            $payload = [];
        }

        $response = $this->withToken($this->token)->postJson($this->url, $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    public static function nonAdminUsersProvider(): array
    {
        return [
            'staff' => [UserRole::Staff],
            'technician' => [UserRole::Technician],
        ];
    }

    public static function invalidInputProvider(): array
    {
        return [
            'empty_payload' => [[]],
            'empty_fields' => [['name' => '']],
            'characters_over_limit' => [['name' => str_repeat('a', 256)]],
            'duplicate' => [['duplicate' => fake()->name]],
        ];
    }
}
