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

class DeleteDepartmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected Department $target_department;

    protected self $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->token = $this->auth_user->createToken('web')->plainTextToken;
        Sanctum::actingAs($this->auth_user);

        $this->request = $this->withCookie('token', $this->token);

        $this->target_department = Department::factory()->create();
        $this->url = route('departments.destroy', ['department' => $this->target_department->id]);
    }

    public function test_admin_can_delete_department(): void
    {
        $response = $this->request->deleteJson($this->url);

        $response->assertOk();
        $this->assertDatabaseMissing('departments', ['id' => $this->target_department->id]);
    }

    #[DataProvider('nonAdminUserProvider')]
    public function test_non_admin_cannot_delete_department(UserRole $role): void
    {
        $auth_user = User::factory()->create(['role' => $role->value]);

        $response = $this->request
            ->actingAs($auth_user, 'sanctum')
            ->deleteJson($this->url);

        $response->assertForbidden();
    }

    public function test_cannot_delete_department_with_staff(): void
    {
        User::factory()->for($this->target_department, 'department')->create();

        $response = $this->request->deleteJson($this->url);

        $response->assertConflict();
    }

    public static function nonAdminUserProvider(): array
    {
        return [
            'staff' => [UserRole::Staff],
            'technician' => [UserRole::Technician],
        ];
    }
}
