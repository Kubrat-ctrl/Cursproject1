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

class ScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_is_paginated_with_meta_and_links(): void
    {
        $workout = Workout::factory()->create();
        $trainer = User::factory()->trainer()->create();
        ScheduleItem::factory()->count(7)->create(['workout_id' => $workout->id, 'trainer_id' => $trainer->id]);

        $this->getJson('/api/v1/schedule-items?per_page=3&page=2')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 3)
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_schedule_can_be_filtered_by_trainer(): void
    {
        $workout = Workout::factory()->create();
        $first = User::factory()->trainer()->create();
        $second = User::factory()->trainer()->create();
        ScheduleItem::factory()->count(2)->create(['workout_id' => $workout->id, 'trainer_id' => $first->id]);
        ScheduleItem::factory()->create(['workout_id' => $workout->id, 'trainer_id' => $second->id]);

        $this->getJson("/api/v1/schedule-items?trainer_id={$first->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.trainer.id', $first->id);
    }

    public function test_schedule_search_finds_by_workout_name(): void
    {
        $yoga = Workout::factory()->create(['name' => 'Хатха-йога']);
        $box = Workout::factory()->create(['name' => 'Тайский бокс']);
        ScheduleItem::factory()->create(['workout_id' => $yoga->id]);
        ScheduleItem::factory()->create(['workout_id' => $box->id]);

        $this->getJson('/api/v1/schedule-items?search=йога')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.workout.name', 'Хатха-йога');
    }

    public function test_schedule_can_be_sorted_by_capacity(): void
    {
        $workout = Workout::factory()->create();
        $trainer = User::factory()->trainer()->create();
        ScheduleItem::factory()->create(['workout_id' => $workout->id, 'trainer_id' => $trainer->id, 'capacity' => 5]);
        ScheduleItem::factory()->create(['workout_id' => $workout->id, 'trainer_id' => $trainer->id, 'capacity' => 20]);

        $this->getJson('/api/v1/schedule-items?sort=capacity&order=desc')
            ->assertOk()
            ->assertJsonPath('data.0.capacity', 20)
            ->assertJsonPath('data.1.capacity', 5);
    }

    public function test_schedule_rejects_unknown_sort_field_and_order(): void
    {
        $this->getJson('/api/v1/schedule-items?sort=price&order=up&per_page=500')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort', 'order', 'per_page']);
    }

    public function test_admin_creates_schedule_item(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $workout = Workout::factory()->create();
        $trainer = User::factory()->trainer()->create();

        $this->postJson('/api/v1/schedule-items', [
            'workout_id' => $workout->id,
            'trainer_id' => $trainer->id,
            'starts_at' => now()->addDays(2)->setTime(10, 0)->toDateTimeString(),
            'ends_at' => now()->addDays(2)->setTime(11, 0)->toDateTimeString(),
            'room' => 'Зал 1',
            'capacity' => 12,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.room', 'Зал 1')
            ->assertJsonPath('data.status', 'scheduled');

        $this->assertDatabaseHas('schedule_items', ['trainer_id' => $trainer->id, 'capacity' => 12]);
    }

    public function test_schedule_item_validation_errors(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/schedule-items', [
            'workout_id' => 9999,
            'trainer_id' => User::factory()->client()->create()->id,
            'starts_at' => now()->subDay()->toDateTimeString(),
            'ends_at' => now()->subDays(2)->toDateTimeString(),
            'capacity' => 0,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['workout_id', 'trainer_id', 'starts_at', 'ends_at', 'room', 'capacity']);
    }

    public function test_trainer_cannot_have_overlapping_sessions(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $workout = Workout::factory()->create();
        $trainer = User::factory()->trainer()->create();
        ScheduleItem::factory()->create([
            'workout_id' => $workout->id,
            'trainer_id' => $trainer->id,
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'ends_at' => now()->addDays(2)->setTime(11, 0),
        ]);

        $this->postJson('/api/v1/schedule-items', [
            'workout_id' => $workout->id,
            'trainer_id' => $trainer->id,
            'starts_at' => now()->addDays(2)->setTime(10, 30)->toDateTimeString(),
            'ends_at' => now()->addDays(2)->setTime(11, 30)->toDateTimeString(),
            'room' => 'Зал 2',
            'capacity' => 10,
        ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'У тренера уже есть занятие в это время');
    }

    public function test_cancelling_session_cancels_its_bookings(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $item = ScheduleItem::factory()->create();
        $type = MembershipType::factory()->create(['visits_limit' => null, 'duration_days' => 30]);
        $client = User::factory()->client()->create();
        $membership = Membership::factory()->forType($type)->create(['user_id' => $client->id]);
        $booking = WorkoutBooking::factory()->create([
            'user_id' => $client->id,
            'schedule_item_id' => $item->id,
            'membership_id' => $membership->id,
        ]);

        $this->postJson("/api/v1/schedule-items/{$item->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('schedule_items', ['id' => $item->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('workout_bookings', ['id' => $booking->id, 'status' => 'cancelled']);

        $this->postJson("/api/v1/schedule-items/{$item->id}/cancel")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Занятие уже отменено');
    }

    public function test_client_cannot_cancel_session(): void
    {
        Sanctum::actingAs(User::factory()->client()->create());
        $item = ScheduleItem::factory()->create();

        $this->postJson("/api/v1/schedule-items/{$item->id}/cancel")->assertStatus(403);
    }
}
