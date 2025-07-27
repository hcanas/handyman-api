<?php

namespace Tests\Feature\Department;

use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateDepartmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected Department $target_department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->token = $this->auth_user->createToken('web')->plainTextToken;

        $this->target_department = Department::factory()->create();

        $this->url = route('departments.update', ['department' => $this->target_department->id]);
    }

    public function test_admins_can_update_department(): void
    {
        $name = fake()->name;

        $response = $this->withToken($this->token)->patchJson($this->url, ['name' => $name]);

        $response->assertOk();
        $this->assertDatabaseHas('departments', [
            'id' => $this->target_department->id,
            'name' => $name,
        ]);
    }

    #[DataProvider('nonAdminUsersProvider')]
    public function test_non_admins_cannot_update_department(UserRole $role): void
    {
        $this->auth_user->update(['role' => $role->value]);

        $response = $this->withToken($this->token)->patchJson($this->url, ['name' => fake()->name]);

        $response->assertForbidden();
    }

    public function test_undesignated_users_cannot_update_department(): void
    {
        $this->auth_user->update(['role' => null]);

        $response = $this->withToken($this->token)->patchJson($this->url, ['name' => fake()->name]);

        $response->assertForbidden();
    }

    public function test_guests_cannot_update_department(): void
    {
        $response = $this->patchJson($this->url, ['name' => fake()->name]);

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

        $response = $this->withToken($this->token)->patchJson($this->url, $payload);

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
