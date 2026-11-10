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

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_membership_sales_report(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $type = MembershipType::factory()->create(['price' => 4000, 'duration_days' => 30, 'visits_limit' => 8]);
        Membership::factory()->count(2)->forType($type)->create();

        $this->getJson('/api/v1/reports/memberships-sales?from='.now()->subDay()->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.total_sold', 2)
            ->assertJsonPath('data.total_revenue', 8000)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_sales_report_requires_period(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/reports/memberships-sales')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from', 'to']);
    }

    public function test_bookings_by_status_report_counts_each_status(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $item = ScheduleItem::factory()->create();
        WorkoutBooking::factory()->count(2)->create(['schedule_item_id' => $item->id]);
        WorkoutBooking::factory()->cancelled()->create(['schedule_item_id' => $item->id]);

        $this->getJson('/api/v1/reports/bookings-by-status')
            ->assertOk()
            ->assertJsonPath('data.booked', 2)
            ->assertJsonPath('data.cancelled', 1);
    }

    public function test_popular_workouts_report_orders_by_bookings(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $popular = Workout::factory()->create(['name' => 'Сайкл']);
        $quiet = Workout::factory()->create(['name' => 'Табата']);
        $popularItem = ScheduleItem::factory()->create(['workout_id' => $popular->id]);
        $quietItem = ScheduleItem::factory()->create(['workout_id' => $quiet->id]);
        WorkoutBooking::factory()->count(3)->create(['schedule_item_id' => $popularItem->id]);
        WorkoutBooking::factory()->create(['schedule_item_id' => $quietItem->id]);

        $this->getJson('/api/v1/reports/popular-workouts?from='.now()->toDateString().'&to='.now()->addDays(20)->toDateString())
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Сайкл')
            ->assertJsonPath('data.0.bookings', 3)
            ->assertJsonPath('data.1.name', 'Табата');
    }

    public function test_popular_workouts_validates_limit(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/reports/popular-workouts?limit=100')
            ->assertStatus(422)
            ->assertJsonValidationErrors('limit');
    }

    public function test_reports_are_forbidden_for_clients(): void
    {
        Sanctum::actingAs(User::factory()->client()->create());

        $this->getJson('/api/v1/reports/bookings-by-status')->assertStatus(403);
        $this->getJson('/api/v1/reports/popular-workouts')->assertStatus(403);
        $this->getJson('/api/v1/reports/memberships-sales?from=2026-01-01&to=2026-12-31')->assertStatus(403);
    }
}
