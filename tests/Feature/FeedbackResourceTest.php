<?php

namespace Tests\Feature;

use App\Enums\FeedbackStatus;
use App\Filament\Resources\Feedbacks\FeedbackResource;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackResourceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_feedback_list_requires_admin(): void
    {
        $response = $this->get('/admin/feedbacks');

        $response->assertRedirect();
    }

    public function test_feedback_list_loads_for_admin(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get('/admin/feedbacks');

        $response->assertStatus(200);
    }

    public function test_feedback_list_shows_feedbacks(): void
    {
        $admin = $this->adminUser();

        Feedback::factory()->create([
            'name' => 'Nagy Béla',
            'email' => 'bela@pelda.hu',
            'status' => FeedbackStatus::New,
        ]);

        $response = $this->actingAs($admin)->get('/admin/feedbacks');

        $response->assertStatus(200);
        $response->assertSee('Nagy Béla');
        $response->assertSee('bela@pelda.hu');
    }

    public function test_feedback_view_page_loads(): void
    {
        $admin = $this->adminUser();

        $feedback = Feedback::factory()->create([
            'name' => 'Kiss Anna',
            'description' => 'Tesztelési hiba a termék oldalon.',
        ]);

        $response = $this->actingAs($admin)->get('/admin/feedbacks/' . $feedback->id);

        $response->assertStatus(200);
        $response->assertSee('Kiss Anna');
    }

    public function test_feedback_navigation_badge_shows_new_count(): void
    {
        Feedback::factory()->count(3)->create(['status' => FeedbackStatus::New]);
        Feedback::factory()->count(2)->create(['status' => FeedbackStatus::Resolved]);

        $badge = FeedbackResource::getNavigationBadge();

        $this->assertSame('3', $badge);
    }

    public function test_feedback_navigation_badge_returns_null_when_no_new(): void
    {
        Feedback::factory()->count(2)->create(['status' => FeedbackStatus::Resolved]);

        $badge = FeedbackResource::getNavigationBadge();

        $this->assertNull($badge);
    }

    public function test_feedback_status_can_be_updated(): void
    {
        $feedback = Feedback::factory()->create(['status' => FeedbackStatus::New]);

        $feedback->update(['status' => FeedbackStatus::InProgress]);

        $this->assertDatabaseHas('feedbacks', [
            'id' => $feedback->id,
            'status' => FeedbackStatus::InProgress->value,
        ]);
    }

    public function test_feedback_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $feedback = Feedback::factory()->create(['user_id' => $user->id]);

        $this->assertSame($user->id, $feedback->user->id);
    }

    public function test_feedback_user_is_nullable(): void
    {
        $feedback = Feedback::factory()->create(['user_id' => null]);

        $this->assertNull($feedback->user);
    }
}
