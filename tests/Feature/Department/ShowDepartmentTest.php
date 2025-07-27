<?php

namespace Tests\Feature\Department;

use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowDepartmentTest extends TestCase
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

        $this->url = route('departments.show', ['department' => $this->target_department->id]);
    }

    public function test_admins_can_view_department(): void
    {
        $response = $this->withToken($this->token)->getJson($this->url);

        $response->assertOk();
    }

    #[DataProvider('nonAdminUsersProvider')]
    public function test_non_admins_cannot_view_department(UserRole $role): void
    {
        $this->auth_user->update(['role' => $role->value]);

        $response = $this->withToken($this->token)->getJson($this->url);

        $response->assertForbidden();
    }

    public function test_undesignated_user_cannot_view_department(): void
    {
        $this->auth_user->update(['role' => null]);

        $response = $this->withToken($this->token)->getJson($this->url);

        $response->assertForbidden();
    }

    public function test_guests_cannot_view_department(): void
    {
        $response = $this->getJson($this->url);

        $response->assertUnauthorized();
    }

    public function test_returns_error_if_department_does_not_exist(): void
    {
        $response = $this->withToken($this->token)->getJson(route('departments.show', ['department' => 999]));

        $response->assertNotFound();
    }

    public static function nonAdminUsersProvider(): array
    {
        return [
            'staff' => [UserRole::Staff],
            'technician' => [UserRole::Technician],
        ];
    }
}
