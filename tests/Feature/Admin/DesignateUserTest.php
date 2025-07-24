<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DesignateUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected User $target_user;

    protected Department $department;

    protected array $valid_input;

    protected array $fields;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->token = $this->auth_user->createToken('web')->plainTextToken;

        Sanctum::actingAs($this->auth_user);

        $this->target_user = User::factory()->create();
        $this->department = Department::factory()->create();

        $this->url = route('user.designate', ['user' => $this->target_user->id]);

        $this->valid_input = [
            'role' => UserRole::Staff->value,
            'department_id' => $this->department->id,
        ];

        $this->fields = [
            'role',
            'department_id',
        ];
    }

    public function test_can_designate_user_if_authenticated_user_is_an_admin(): void
    {
        $this->assertTrue($this->auth_user->isAdmin());

        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson($this->url);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors($this->fields);
    }

    public function test_cannot_designate_user_if_authenticated_user_is_not_an_admin(): void
    {
        $this->auth_user->update(['role' => UserRole::Staff->value]);

        $this->assertFalse($this->auth_user->isAdmin());

        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson($this->url);

        $response->assertForbidden();
    }

    public function test_succeeds_if_input_is_valid(): void
    {
        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $this->target_user->id,
            'role' => $this->valid_input['role'],
            'department_id' => $this->valid_input['department_id'],
        ]);
    }

    public function test_fails_if_input_is_empty(): void
    {
        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson($this->url);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors($this->fields);
    }

    public function test_fails_if_role_is_invalid(): void
    {
        $role = 'invalid';

        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson($this->url, [
                ...$this->valid_input,
                'role' => $role,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['role']);
    }

    public function test_fails_if_department_does_not_exist(): void
    {
        $department_id = 999;

        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson($this->url, [
                ...$this->valid_input,
                'department_id' => $department_id,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['department_id']);
    }
}
