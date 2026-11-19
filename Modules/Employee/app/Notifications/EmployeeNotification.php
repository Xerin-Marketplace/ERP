<?php

namespace Modules\Employee\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $subject,
        private string $body,
        private ?string $actionUrl = null,
        private ?string $actionLabel = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject)->line($this->body);
        if ($this->actionUrl) {
            $mail->action($this->actionLabel ?? 'Open', $this->actionUrl);
        }
        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return ['subject' => $this->subject, 'body' => $this->body, 'url' => $this->actionUrl];
    }
}
