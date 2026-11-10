<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ScheduleItem;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->client()->create();
        Sanctum::actingAs($this->client);
    }

    /** Абонемент клиента с заданным лимитом посещений (null — без лимита). */
    private function membershipFor(User $user, ?int $limit = null): Membership
    {
        $type = MembershipType::factory()->create(['visits_limit' => $limit, 'duration_days' => 30]);

        return Membership::factory()->forType($type)->create(['user_id' => $user->id]);
    }

    private function item(array $attrs = []): ScheduleItem
    {
        return ScheduleItem::factory()->create(array_merge([
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'ends_at' => now()->addDays(2)->setTime(11, 0),
            'capacity' => 10,
        ], $attrs));
    }

    public function test_client_can_book_a_session(): void
    {
        $membership = $this->membershipFor($this->client, 8);
        $item = $this->item();

        $this->postJson("/api/v1/schedule-items/{$item->id}/bookings")
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'booked')
            ->assertJsonPath('data.schedule_item.id', $item->id);

        $this->assertDatabaseHas('workout_bookings', [
            'user_id' => $this->client->id,
            'schedule_item_id' => $item->id,
            'membership_id' => $membership->id,
            'status' => 'booked',
        ]);
    }

    public function test_trainer_cannot_book(): void
    {
        Sanctum::actingAs(User::factory()->trainer()->create());
        $item = $this->item();

        $this->postJson("/api/v1/schedule-items/{$item->id}/bookings")->assertStatus(403);
    }

    public function test_booking_without_membership_is_rejected(): void
    {
        $item = $this->item();

        $this->postJson("/api/v1/schedule-items/{$item->id}/bookings")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Нет действующего абонемента на дату занятия');

        $this->assertDatabaseCount('workout_bookings', 0);
    }

    public function test_booking_is_rejected_when_visit_limit_is_used_up(): void
    {
        $this->membershipFor($this->client, 1);
        $first = $this->item();
        $second = $this->item(['starts_at' => now()->addDays(3)->setTime(10, 0), 'ends_at' => now()->addDays(3)->setTime(11, 0)]);

        $this->postJson("/api/v1/schedule-items/{$first->id}/bookings")->assertStatus(201);
        $this->postJson("/api/v1/schedule-items/{$second->id}/bookings")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Лимит посещений по абонементу исчерпан');
    }

    public function test_duplicate_booking_is_rejected(): void
    {
        $this->membershipFor($this->client);
        $item = $this->item();

        $this->postJson("/api/v1/schedule-items/{$item->id}/bookings")->assertStatus(201);
        $this->postJson("/api/v1/schedule-items/{$item->id}/bookings")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Вы уже записаны на это занятие');

        $this->assertDatabaseCount('workout_bookings', 1);
    }

    public function test_booking_is_rejected_when_session_is_full(): void
    {
        $this->membershipFor($this->client);
        $item = $this->item(['capacity' => 1]);
        $other = User::factory()->client()->create();
        $otherMembership = $this->membershipFor($other);
        WorkoutBooking::factory()->create([
            'user_id' => $other->id,
            'schedule_item_id' => $item->id,
            'membership_id' => $otherMembership->id,
        ]);

        $this->postJson("/api/v1/schedule-items/{$item->id}/bookings")
            ->assertStatus(409)
            ->assertJsonPath('message', 'На занятие нет свободных мест');
    }

    public function test_booking_is_rejected_for_past_and_cancelled_sessions(): void
    {
        $this->membershipFor($this->client);
        $past = $this->item(['starts_at' => now()->subHours(3), 'ends_at' => now()->subHours(2)]);
        $cancelled = ScheduleItem::factory()->cancelled()->create();

        $this->postJson("/api/v1/schedule-items/{$past->id}/bookings")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Запись закрыта: занятие отменено или уже началось');
        $this->postJson("/api/v1/schedule-items/{$cancelled->id}/bookings")->assertStatus(422);
    }

    public function test_client_sees_only_own_bookings_with_status_filter(): void
    {
        $membership = $this->membershipFor($this->client);
        $other = User::factory()->client()->create();
        $otherMembership = $this->membershipFor($other);
        $workout = Workout::factory()->create();
        $trainer = User::factory()->trainer()->create();
        $make = fn (User $u, Membership $m, bool $cancelled = false) => WorkoutBooking::factory()
            ->when($cancelled, fn ($f) => $f->cancelled())
            ->create([
                'user_id' => $u->id,
                'membership_id' => $m->id,
                'schedule_item_id' => ScheduleItem::factory()->create(['workout_id' => $workout->id, 'trainer_id' => $trainer->id])->id,
            ]);
        $make($this->client, $membership);
        $make($this->client, $membership, true);
        $make($other, $otherMembership);

        $this->getJson('/api/v1/bookings')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/bookings?status=cancelled')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'cancelled');
    }

    public function test_client_can_cancel_booking_and_visit_returns_to_membership(): void
    {
        $membership = $this->membershipFor($this->client, 1);
        $item = $this->item();
        $first = $this->postJson("/api/v1/schedule-items/{$item->id}/bookings")->json('data.id');

        $this->deleteJson("/api/v1/bookings/{$first}")->assertNoContent();

        $this->assertDatabaseHas('workout_bookings', ['id' => $first, 'status' => 'cancelled']);
        $this->getJson('/api/v1/memberships')
            ->assertOk()
            ->assertJsonPath('data.0.id', $membership->id)
            ->assertJsonPath('data.0.used_visits', 0)
            ->assertJsonPath('data.0.visits_left', 1);
    }

    public function test_cancelling_twice_is_rejected(): void
    {
        $this->membershipFor($this->client);
        $item = $this->item();
        $id = $this->postJson("/api/v1/schedule-items/{$item->id}/bookings")->json('data.id');

        $this->deleteJson("/api/v1/bookings/{$id}")->assertNoContent();
        $this->deleteJson("/api/v1/bookings/{$id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Запись уже отменена');
    }

    public function test_cancellation_is_closed_two_hours_before_start(): void
    {
        $membership = $this->membershipFor($this->client);
        $item = $this->item(['starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)]);
        $booking = WorkoutBooking::factory()->create([
            'user_id' => $this->client->id,
            'schedule_item_id' => $item->id,
            'membership_id' => $membership->id,
        ]);

        $this->deleteJson("/api/v1/bookings/{$booking->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Отменить запись можно не позднее чем за 2 часа до начала');

        $this->assertDatabaseHas('workout_bookings', ['id' => $booking->id, 'status' => 'booked']);
    }

    public function test_client_cannot_cancel_foreign_booking(): void
    {
        $other = User::factory()->client()->create();
        $booking = WorkoutBooking::factory()->create([
            'user_id' => $other->id,
            'schedule_item_id' => $this->item()->id,
            'membership_id' => $this->membershipFor($other)->id,
        ]);

        $this->deleteJson("/api/v1/bookings/{$booking->id}")->assertStatus(403);

        $this->assertDatabaseHas('workout_bookings', ['id' => $booking->id, 'status' => 'booked']);
    }

    public function test_trainer_marks_visit_only_on_own_session(): void
    {
        $trainer = User::factory()->trainer()->create();
        $other = User::factory()->trainer()->create();
        $item = $this->item(['trainer_id' => $trainer->id, 'starts_at' => now()->addMinutes(10), 'ends_at' => now()->addHour()]);
        $booking = WorkoutBooking::factory()->create([
            'user_id' => $this->client->id,
            'schedule_item_id' => $item->id,
            'membership_id' => $this->membershipFor($this->client)->id,
        ]);

        Sanctum::actingAs($other);
        $this->postJson("/api/v1/bookings/{$booking->id}/visit")->assertStatus(403);

        Sanctum::actingAs($trainer);
        $this->postJson("/api/v1/bookings/{$booking->id}/visit")->assertStatus(201);
        $this->assertDatabaseHas('visits', ['workout_booking_id' => $booking->id]);

        $this->postJson("/api/v1/bookings/{$booking->id}/visit")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Посещение уже отмечено');
    }

    public function test_trainer_sees_attendees_only_of_own_session(): void
    {
        $trainer = User::factory()->trainer()->create();
        $item = $this->item(['trainer_id' => $trainer->id]);
        WorkoutBooking::factory()->create([
            'user_id' => $this->client->id,
            'schedule_item_id' => $item->id,
            'membership_id' => $this->membershipFor($this->client)->id,
        ]);

        Sanctum::actingAs(User::factory()->trainer()->create());
        $this->getJson("/api/v1/schedule-items/{$item->id}/bookings")->assertStatus(403);

        Sanctum::actingAs($trainer);
        $this->getJson("/api/v1/schedule-items/{$item->id}/bookings")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
