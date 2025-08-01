<?php

namespace Tests\Feature\Ticket;

use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use App\TicketAction;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListTicketsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('tickets.index');

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->token = $this->auth_user->createToken('token')->plainTextToken;

        Ticket::factory()->count(20)->create();
    }

    public function test_admins_can_list_all_tickets(): void
    {
        $expected_ids = Ticket::paginate()->pluck('id')->values()->all();

        $response = $this->withToken($this->token)->getJson($this->url);

        $returned_ids = collect($response->json('data'))->pluck('id')->values()->all();

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'priority_level',
                        'reported_by',
                        'assigned_to',
                        'department_name',
                        'resolved_at',
                        'status',
                    ],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals($expected_ids, $returned_ids);
    }

    public function test_staff_can_list_own_tickets(): void
    {
        $tickets = Ticket::factory()->count(15)->create(['reported_by_id' => $this->auth_user->id]);
        $expected_ids = $tickets->pluck('id')->values()->all();

        $this->auth_user->update(['role' => UserRole::Staff->value]);

        $response = $this->withToken($this->token)->getJson($this->url);

        $returned_ids = collect($response->json('data'))->pluck('id')->values()->all();

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'priority_level',
                        'reported_by',
                        'assigned_to',
                        'department_name',
                        'resolved_at',
                        'status',
                    ],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals($expected_ids, $returned_ids);
    }

    public function test_technicians_can_list_assigned_tickets(): void
    {
        $tickets = Ticket::factory()->count(15)->create(['assigned_to_id' => $this->auth_user->id]);
        $expected_ids = $tickets->pluck('id')->values()->all();

        $this->auth_user->update(['role' => UserRole::Technician->value]);

        $response = $this->withToken($this->token)->getJson($this->url);

        $returned_ids = collect($response->json('data'))->pluck('id')->values()->all();

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'priority_level',
                        'reported_by',
                        'assigned_to',
                        'department_name',
                        'resolved_at',
                        'status',
                    ],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals($expected_ids, $returned_ids);
    }

    public function test_technicians_can_list_previously_assigned_tickets(): void
    {
        $assigned_to_id = $this->auth_user->id;
        $reassigned_to_id = User::factory()->create()->id;

        $tickets = Ticket::factory()
            ->has(TicketLog::factory()
                ->count(2)
                ->state(function (array $attr, Ticket $ticket) use ($assigned_to_id, $reassigned_to_id) {
                    static $assigned = false;

                    $user_id = $assigned ? $reassigned_to_id : $assigned_to_id;

                    $assigned = !$assigned;

                    return [
                        'user_id' => $user_id,
                        'action' => TicketAction::ReceivedAssignment,
                    ];
                }), 'logs')
            ->count(15)
            ->create(['assigned_to_id' => $reassigned_to_id]);

        $expected_ids = $tickets->pluck('id')->values()->all();

        $this->auth_user->update(['role' => UserRole::Technician->value]);

        $response = $this->withToken($this->token)->getJson($this->url);

        $returned_ids = collect($response->json('data'))->pluck('id')->values()->all();

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'priority_level',
                        'reported_by',
                        'assigned_to',
                        'department_name',
                        'resolved_at',
                        'status',
                    ],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals($expected_ids, $returned_ids);
    }

    #[DataProvider('queryParamsProvider')]
    public function test_can_list_tickets_with_query_params(array $params): void
    {
        $url = $this->url . '?' . http_build_query($params);

        $response = $this->withToken($this->token)->getJson($url);

        $response->assertOk();

        if (isset($params['page'])) {
            $this->assertEquals($params['page'], $response->json('meta.current_page'));
        }

        if (isset($params['per_page'])) {
            $this->assertEquals($params['per_page'], $response->json('meta.per_page'));
        }
    }

    public function test_undesignated_users_cannot_list_tickets(): void
    {
        $this->auth_user->update(['role' => null]);

        $response = $this->withToken($this->token)->getJson($this->url);

        $response->assertForbidden();
    }

    public function test_guests_cannot_list_tickets(): void
    {
        $response = $this->getJson($this->url);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidQueryParamsProvider')]
    public function test_invalid_query_params_fail_validation(array $params): void
    {
        $url = $this->url . '?' . http_build_query($params);

        $response = $this->withToken($this->token)->getJson($url);

        $response->assertUnprocessable();
    }

    public static function queryParamsProvider(): array
    {
        return [
            'undefined' => [[]],
            'full' => [[
                'page' => 1,
                'per_page' => 10,
                'order_by' => 'updated_at',
                'order_dir' => 'desc',
            ]],
            'page only' => [['page' => 1]],
            'per_page only' => [['per_page' => 10]],
            'order_by only' => [['order_by' => 'updated_at']],
            'order_dir only' => [['order_dir' => 'desc']],
        ];
    }

    public static function invalidQueryParamsProvider(): array
    {
        return [
            'negative page' => [['page' => -1]],
            'zero page' => [['page' => 0]],
            'zero per_page' => [['per_page' => 0]],
            'non-numeric page' => [['page' => 'abc']],
            'invalid order_by' => [['order_by' => 'bad_field']],
            'invalid order_dir' => [['order_by' => 'created_at', 'order_dir' => 'sideways']],
        ];
    }
}
