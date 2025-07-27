<?php

namespace Tests\Feature\Department;

use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeleteDepartmentTest extends TestCase
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

        $this->url = route('departments.destroy', ['department' => $this->target_department->id]);
    }

    public function test_admins_can_delete_department(): void
    {
        $response = $this->withToken($this->token)->deleteJson($this->url);

        $response->assertOk();
        $this->assertDatabaseMissing('departments', ['id' => $this->target_department->id]);
    }

    #[DataProvider('nonAdminUsersProvider')]
    public function test_non_admins_cannot_delete_department(UserRole $role): void
    {
        $this->auth_user->update(['role' => $role->value]);

        $response = $this->withToken($this->token)->deleteJson($this->url);

        $response->assertForbidden();
    }

    public function test_undesignated_users_cannot_delete_department(): void
    {
        $this->auth_user->update(['role' => null]);

        $response = $this->withToken($this->token)->deleteJson($this->url);

        $response->assertForbidden();
    }

    public function test_guests_cannot_delete_department(): void
    {
        $response = $this->deleteJson($this->url);

        $response->assertUnauthorized();
    }

    public function test_cannot_delete_department_with_staff(): void
    {
        User::factory()->for($this->target_department, 'department')->create();

        $response = $this->withToken($this->token)->deleteJson($this->url);

        $response->assertConflict();
    }

    public static function nonAdminUsersProvider(): array
    {
        return [
            'staff' => [UserRole::Staff],
            'technician' => [UserRole::Technician],
        ];
    }
}
