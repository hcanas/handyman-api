<?php

namespace Tests\Feature\Department;

use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateDepartmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected Department $target_department;

    protected self $request;

    protected array $valid_input;

    protected array $fields;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create([
            'role' => UserRole::Admin->value,
        ]);

        $this->token = $this->auth_user
            ->createToken('web')
            ->plainTextToken;

        Sanctum::actingAs($this->auth_user);

        $this->target_department = Department::factory()->create();

        $this->url = route('departments.update', [
            'department' => $this->target_department->id,
        ]);

        $this->request = $this->withCookie('token', $this->token);

        $this->valid_input = [
            'name' => $this->faker->unique()->name,
        ];

        $this->fields = [
            'name',
        ];
    }

    public function test_can_update_department_if_authenticated_user_is_an_admin(): void
    {
        $this->assertTrue($this->auth_user->isAdmin());
    }

    public function test_cannot_update_department_if_authenticated_user_is_not_an_admin(): void
    {
        $this->auth_user->update(['role' => UserRole::Staff->value]);

        $this->assertFalse($this->auth_user->isAdmin());
    }

    public function test_succeeds_if_input_is_valid(): void
    {
        $response = $this->request->patchJson($this->url, $this->valid_input);

        $response->assertOk();
    }

    public function test_fails_if_input_is_empty(): void
    {
        $response = $this->request->patchJson($this->url, []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors($this->fields);
    }

    public function test_fails_if_name_is_duplicate(): void
    {
        Department::factory()->create([
            'name' => $this->valid_input['name'],
        ]);

        $response = $this->request->patchJson($this->url, $this->valid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }
}
