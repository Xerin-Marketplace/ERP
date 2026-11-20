<?php

namespace Modules\Cargo\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Customer-facing shipment status email (spec §12.2). */
class PackageStatusUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $subjectLine,
        private string $body,
        private ?string $trackingUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = (new MailMessage)
            ->subject($this->subjectLine)
            ->greeting('Dear customer,')
            ->line($this->body);
        if ($this->trackingUrl) {
            $m->action('Track shipment', $this->trackingUrl);
        }
        return $m->line('Xerin Group Company Limited — Dubai & Tanzania');
    }
}
