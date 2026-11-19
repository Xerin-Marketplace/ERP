<?php

namespace Modules\HR\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HRNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $subject,
        private string $body,
        private ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject)->line($this->body);
        if ($this->actionUrl) {
            $mail->action('Open in HR', $this->actionUrl);
        }
        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return ['subject' => $this->subject, 'body' => $this->body, 'url' => $this->actionUrl];
    }
}
