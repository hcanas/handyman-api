<?php

namespace Tests\Feature\Department;

use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateDepartmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected self $request;

    protected array $valid_input;

    protected array $fields;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('departments.store');

        $this->auth_user = User::factory()->create([
            'role' => UserRole::Admin->value,
        ]);

        $this->token = $this->auth_user
            ->createToken('web')
            ->plainTextToken;

        Sanctum::actingAs($this->auth_user);

        $this->request = $this->withCookie('token', $this->token);

        $this->valid_input = [
            'name' => $this->faker->unique()->name,
        ];

        $this->fields = [
            'name',
        ];
    }

    public function test_can_create_department_if_authenticated_user_is_an_admin(): void
    {
        $this->assertTrue($this->auth_user->isAdmin());
    }

    public function test_cannot_create_department_if_authenticated_user_is_not_an_admin(): void
    {
        $this->auth_user->update(['role' => UserRole::Staff->value]);

        $this->assertFalse($this->auth_user->isAdmin());
    }

    public function test_succeeds_if_input_is_valid(): void
    {
        $response = $this->request->postJson($this->url, $this->valid_input);

        $response->assertCreated();
        $this->assertDatabaseHas('departments', $this->valid_input);
    }

    public function test_fails_if_input_is_empty(): void
    {
        $response = $this->request->postJson($this->url, []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors($this->fields);
    }

    public function test_fails_if_name_is_duplicate(): void
    {
        Department::factory()->create([
            'name' => $this->valid_input['name'],
        ]);

        $response = $this->request->postJson($this->url, $this->valid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }
}
