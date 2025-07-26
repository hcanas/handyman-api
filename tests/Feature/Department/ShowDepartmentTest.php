<?php

namespace Tests\Feature\Department;

use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowDepartmentTest extends TestCase
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
        $this->url = route('departments.show', ['department' => $this->target_department->id]);
    }

    public function test_can_view_department_if_auth_user_is_an_admin(): void
    {
        $this->assertTrue($this->auth_user->isAdmin());
    }

    public function test_cannot_view_department_if_auth_user_is_not_an_admin(): void
    {
        $this->auth_user->update(['role' => UserRole::Staff->value]);

        $this->assertFalse($this->auth_user->isAdmin());
    }

    public function test_returns_department_if_exists(): void
    {
        $response = $this->request->getJson($this->url);

        $response->assertOk();
    }

    public function test_fails_if_department_does_not_exist(): void
    {
        $department_id = 999;

        $response = $this->request->getJson(route('departments.show', ['department' => $department_id]));

        $response->assertNotFound();
        $this->assertDatabaseMissing('departments', ['id' => $department_id]);
    }
}
