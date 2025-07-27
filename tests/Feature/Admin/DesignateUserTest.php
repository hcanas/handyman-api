<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DesignateUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected User $target_user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->token = $this->auth_user->createToken('web')->plainTextToken;

        $this->target_user = User::factory()->create();

        $this->url = route('user.designate', ['user' => $this->target_user->id]);
    }

    public function test_admins_can_designate_active_user(): void
    {
        $designation = [
            'role' => UserRole::Staff->value,
            'department_id' => Department::factory()->create()->id,
        ];

        $response = $this->withToken($this->token)->patchJson($this->url, $designation);

        $response->assertOk();
    }

    public function test_admins_cannot_designate_banned_user(): void
    {
        $this->target_user->update(['banned_at' => now()]);

        $designation = [
            'role' => UserRole::Staff->value,
            'department_id' => Department::factory()->create()->id,
        ];

        $response = $this->withToken($this->token)->patchJson($this->url, $designation);

        $response->assertConflict();
    }

    #[DataProvider('nonAdminUsersProvider')]
    public function test_non_admins_cannot_designate_user(UserRole $role): void
    {
        $this->auth_user->update(['role' => $role->value]);

        $response = $this->withToken($this->token)->patchJson($this->url);

        $response->assertForbidden();
    }

    public function test_undesignated_users_cannot_designate_user(): void
    {
        $this->auth_user->update(['role' => null]);

        $response = $this->withToken($this->token)->patchJson($this->url);

        $response->assertForbidden();
    }

    public function test_guests_cannot_designate_user(): void
    {
        $response = $this->patchJson($this->url);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidRoleProvider')]
    public function test_invalid_role_fails_validation(array $input): void
    {
        $designation = ['department_id' => Department::factory()->create()->id];

        if (isset($input['role'])) {
            $designation['role'] = $input['role'];
        }

        $response = $this->withToken($this->token)->patchJson($this->url, $designation);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['role']);
    }

    #[DataProvider('invalidDepartmentIdProvider')]
    public function test_invalid_department_fails_validation(array $input): void
    {
        $designation = ['role' => UserRole::Staff->value];

        if (isset($input['department_id'])) {
            $designation['department_id'] = $input['department_id'];
        }

        $response = $this->withToken($this->token)->patchJson($this->url, $designation);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['department_id']);
    }

    public static function nonAdminUsersProvider(): array
    {
        return [
            'staff' => [UserRole::Staff],
            'technician' => [UserRole::Technician],
        ];
    }

    public static function invalidRoleProvider(): array
    {
        return [
            'undefined' => [[]],
            'empty' => [['role' => '']],
            'not_in_list' => [['role' => 'fake role']],
        ];
    }

    public static function invalidDepartmentIdProvider(): array
    {
        return [
            'undefined' => [[]],
            'empty' => [['department_id' => '']],
            'does_not_exist' => [['department_id' => 999]],
        ];
    }
}
