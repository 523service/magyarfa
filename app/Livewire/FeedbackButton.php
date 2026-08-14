<?php

namespace App\Livewire;

use App\Models\Feedback;
use App\Notifications\NewFeedbackNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

class FeedbackButton extends Component
{
    public bool $showModal = false;

    public bool $submitted = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('required|string|min:10|max:5000')]
    public string $description = '';

    public string $currentUrl = '';

    public int $screenWidth = 0;

    public int $screenHeight = 0;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->name = Auth::user()->name ?? '';
            $this->email = Auth::user()->email ?? '';
        }
    }

    public function openModal(): void
    {
        $this->submitted = false;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['description', 'submitted']);
        $this->resetValidation();
    }

    public function submit(): void
    {
        $this->validate();

        $feedback = Feedback::create([
            'user_id' => Auth::id(),
            'name' => $this->name,
            'email' => $this->email ?: null,
            'description' => $this->description,
            'url' => $this->currentUrl,
            'status' => 'new',
            'device_info' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'accept_language' => request()->header('Accept-Language'),
                'screen_width' => $this->screenWidth,
                'screen_height' => $this->screenHeight,
            ],
        ]);

        $feedbackEmail = config('shop.feedback_email');

        if ($feedbackEmail) {
            try {
                Notification::route('mail', $feedbackEmail)
                    ->notify(new NewFeedbackNotification($feedback));
            } catch (Throwable) {
            }
        }

        $this->submitted = true;
        $this->reset(['description']);
    }

    public function render(): View
    {
        return view('livewire.feedback-button');
    }
}
