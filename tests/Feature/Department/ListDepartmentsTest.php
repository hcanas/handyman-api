<?php

namespace Tests\Feature\Department;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListDepartmentsTest extends TestCase
{
    use RefreshDatabase;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('departments.index');

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->token = $this->auth_user->createToken('token')->plainTextToken;
    }

    public function test_admins_can_view_department_list(): void
    {
        $response = $this->withToken($this->token)->getJson($this->url);

        $response->assertOk();
    }

    #[DataProvider('nonAdminUsersProvider')]
    public function test_non_admins_cannot_view_department_list(UserRole $role): void
    {
        $this->auth_user->update(['role' => $role->value]);

        $response = $this->withToken($this->token)->getJson($this->url);

        $response->assertForbidden();
    }

    public function test_undesignated_users_cannot_view_department_list(): void
    {
        $this->auth_user->update(['role' => null]);

        $response = $this->withToken($this->token)->getJson($this->url);

        $response->assertForbidden();
    }

    public function test_guests_cannot_view_department_list(): void
    {
        $response = $this->getJson($this->url);

        $response->assertUnauthorized();
    }

    public static function nonAdminUsersProvider(): array
    {
        return [
            'staff' => [UserRole::Staff],
            'technician' => [UserRole::Technician],
        ];
    }
}
