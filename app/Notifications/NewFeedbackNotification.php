<?php

namespace App\Notifications;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewFeedbackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Feedback $feedback) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Új hibabejelentés érkezett')
            ->greeting('Új hibabejelentés!')
            ->line("**Beküldő:** {$this->feedback->name} ({$this->feedback->email})")
            ->line("**Oldal:** {$this->feedback->url}")
            ->line('**Leírás:**')
            ->line($this->feedback->description)
            ->action('Megtekintés az adminban', url('/admin/feedbacks/' . $this->feedback->id))
            ->salutation('MagyarSzigeteles.hu rendszer');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
