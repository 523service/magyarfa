<?php

namespace Tests\Feature;

use App\Enums\FeedbackStatus;
use App\Livewire\FeedbackButton;
use App\Models\Feedback;
use App\Models\User;
use App\Notifications\NewFeedbackNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class FeedbackButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_button_renders(): void
    {
        Livewire::test(FeedbackButton::class)
            ->assertSet('showModal', false)
            ->assertSet('submitted', false);
    }

    public function test_modal_opens(): void
    {
        Livewire::test(FeedbackButton::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->assertSet('submitted', false);
    }

    public function test_modal_closes(): void
    {
        Livewire::test(FeedbackButton::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    public function test_prefills_name_and_email_when_logged_in(): void
    {
        $user = User::factory()->create([
            'name' => 'Teszt Elek',
            'email' => 'teszt@pelda.hu',
        ]);

        Livewire::actingAs($user)
            ->test(FeedbackButton::class)
            ->assertSet('name', 'Teszt Elek')
            ->assertSet('email', 'teszt@pelda.hu');
    }

    public function test_name_email_are_empty_for_guest(): void
    {
        Livewire::test(FeedbackButton::class)
            ->assertSet('name', '')
            ->assertSet('email', '');
    }

    public function test_validates_required_fields(): void
    {
        Livewire::test(FeedbackButton::class)
            ->call('submit')
            ->assertHasErrors(['name', 'description'])
            ->assertHasNoErrors(['email']);
    }

    public function test_validates_email_format_when_provided(): void
    {
        Livewire::test(FeedbackButton::class)
            ->set('name', 'Teszt Elek')
            ->set('email', 'nem-email')
            ->set('description', 'Ez egy tesztelési hiba leírása.')
            ->call('submit')
            ->assertHasErrors(['email']);
    }

    public function test_can_submit_without_email(): void
    {
        Notification::fake();

        config(['shop.feedback_email' => '']);

        Livewire::test(FeedbackButton::class)
            ->set('name', 'Vendég Felhasználó')
            ->set('description', 'Ez egy részletes hiba leírása az oldalon.')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('feedbacks', [
            'name' => 'Vendég Felhasználó',
            'email' => null,
        ]);
    }

    public function test_validates_description_minimum_length(): void
    {
        Livewire::test(FeedbackButton::class)
            ->set('name', 'Teszt Elek')
            ->set('email', 'teszt@pelda.hu')
            ->set('description', 'Rövid')
            ->call('submit')
            ->assertHasErrors(['description']);
    }

    public function test_guest_can_submit_feedback(): void
    {
        Notification::fake();

        config(['shop.feedback_email' => '']);

        Livewire::test(FeedbackButton::class)
            ->set('name', 'Vendég Felhasználó')
            ->set('email', 'vendeg@pelda.hu')
            ->set('description', 'Ez egy részletes hiba leírása a weboldalon.')
            ->set('currentUrl', 'https://pelda.hu/termekek')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('feedbacks', [
            'name' => 'Vendég Felhasználó',
            'email' => 'vendeg@pelda.hu',
            'status' => FeedbackStatus::New->value,
            'user_id' => null,
        ]);
    }

    public function test_logged_in_user_submit_sets_user_id(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FeedbackButton::class)
            ->set('description', 'Ez egy részletes hiba leírása a weboldalon.')
            ->set('currentUrl', 'https://pelda.hu/termekek')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSet('name', $user->name)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('feedbacks', [
            'email' => $user->email,
            'user_id' => $user->id,
            'status' => FeedbackStatus::New->value,
        ]);
    }

    public function test_notification_sent_on_submit(): void
    {
        Notification::fake();

        config(['shop.feedback_email' => 'admin@pelda.hu']);

        Livewire::test(FeedbackButton::class)
            ->set('name', 'Teszt Elek')
            ->set('email', 'teszt@pelda.hu')
            ->set('description', 'Ez egy részletes hiba leírása a weboldalon.')
            ->call('submit');

        Notification::assertSentOnDemand(NewFeedbackNotification::class);
    }

    public function test_no_notification_sent_when_feedback_email_not_configured(): void
    {
        Notification::fake();

        config(['shop.feedback_email' => '']);

        Livewire::test(FeedbackButton::class)
            ->set('name', 'Teszt Elek')
            ->set('email', 'teszt@pelda.hu')
            ->set('description', 'Ez egy részletes hiba leírása a weboldalon.')
            ->call('submit');

        Notification::assertNothingSent();
    }

    public function test_description_is_reset_after_successful_submit(): void
    {
        Notification::fake();

        Livewire::test(FeedbackButton::class)
            ->set('name', 'Teszt Elek')
            ->set('email', 'teszt@pelda.hu')
            ->set('description', 'Ez egy részletes hiba leírása a weboldalon.')
            ->call('submit')
            ->assertSet('description', '');
    }

    public function test_device_info_stored_with_feedback(): void
    {
        Notification::fake();

        Livewire::test(FeedbackButton::class)
            ->set('name', 'Teszt Elek')
            ->set('email', 'teszt@pelda.hu')
            ->set('description', 'Ez egy részletes hiba leírása a weboldalon.')
            ->set('screenWidth', 1920)
            ->set('screenHeight', 1080)
            ->call('submit');

        $feedback = Feedback::first();
        $this->assertNotNull($feedback->device_info);
        $this->assertSame(1920, $feedback->device_info['screen_width']);
        $this->assertSame(1080, $feedback->device_info['screen_height']);
    }
}
