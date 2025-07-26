<?php

namespace Tests\Feature\Department;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListDepartmentsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected self $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('departments.index');

        $this->auth_user = User::factory()->create([
            'role' => UserRole::Admin->value,
        ]);

        $this->token = $this->auth_user
            ->createToken('web')
            ->plainTextToken;

        Sanctum::actingAs($this->auth_user);

        $this->request = $this->withCookie('token', $this->token);
    }

    public function test_can_list_departments_if_authenticated_user_is_an_admin(): void
    {
        $this->assertTrue($this->auth_user->isAdmin());
    }

    public function test_cannot_list_departments_if_authenticated_user_is_not_an_admin(): void
    {
        $this->auth_user->update(['role' => UserRole::Staff->value]);

        $this->assertFalse($this->auth_user->isAdmin());
    }

    public function test_returns_list_of_departments(): void
    {
        $response = $this->request->getJson($this->url);

        $response->assertOk();
    }
}
