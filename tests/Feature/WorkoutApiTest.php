<?php

namespace Tests\Feature;

use App\Models\ScheduleItem;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkoutApiTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'name' => 'Новая программа',
        'description' => 'Описание',
        'duration_minutes' => 60,
        'level' => 'beginner',
    ];

    public function test_guest_can_list_workouts(): void
    {
        Workout::factory()->count(3)->create();

        $this->getJson('/api/v1/workouts')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_admin_can_create_workout(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/workouts', $this->payload)
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Новая программа')
            ->assertJsonPath('data.level', 'beginner');

        $this->assertDatabaseHas('workouts', ['name' => 'Новая программа', 'duration_minutes' => 60]);
    }

    public function test_client_cannot_create_workout(): void
    {
        Sanctum::actingAs(User::factory()->client()->create());

        $this->postJson('/api/v1/workouts', $this->payload)
            ->assertStatus(403)
            ->assertJsonPath('message', 'Недостаточно прав для этого действия');

        $this->assertDatabaseMissing('workouts', ['name' => 'Новая программа']);
    }

    public function test_guest_cannot_create_workout(): void
    {
        $this->postJson('/api/v1/workouts', $this->payload)->assertStatus(401);
    }

    public function test_workout_validation_errors(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/workouts', ['name' => '', 'duration_minutes' => 5, 'level' => 'expert'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'duration_minutes', 'level']);
    }

    public function test_missing_workout_returns_404_in_unified_format(): void
    {
        $this->getJson('/api/v1/workouts/9999')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Вид занятия не найден');
    }

    public function test_workout_with_schedule_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $workout = Workout::factory()->create();
        ScheduleItem::factory()->create(['workout_id' => $workout->id]);

        $this->deleteJson("/api/v1/workouts/{$workout->id}")->assertStatus(409);

        $this->assertDatabaseHas('workouts', ['id' => $workout->id]);
    }

    public function test_admin_can_delete_unused_workout(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $workout = Workout::factory()->create();

        $this->deleteJson("/api/v1/workouts/{$workout->id}")->assertNoContent();

        $this->assertDatabaseMissing('workouts', ['id' => $workout->id]);
    }
}
