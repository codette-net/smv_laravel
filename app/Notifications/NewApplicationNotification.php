<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplicationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Application $application) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nieuwe sollicitatie voor '.$this->application->vacancy->title)
            ->greeting('Nieuwe sollicitatie ontvangen')
            ->line($this->application->candidate_name.' heeft gesolliciteerd op '.$this->application->vacancy->title.'.')
            ->line('E-mailadres: '.$this->application->candidate_email);
    }
}
