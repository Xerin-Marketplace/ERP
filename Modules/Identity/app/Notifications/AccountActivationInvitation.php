<?php

namespace Modules\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Activate your XERIN GROUP ERP account')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your ERP account has been provisioned and approved.')
            ->line('Click the button below to set your password and activate your account. This link expires in 72 hours and can be used only once.')
            ->action('Activate Account', url("/activate/{$this->token}"))
            ->line('If you did not apply for ERP access, please ignore this email.');
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'activation_invitation'];
    }
}
