<?php

namespace Modules\Identity\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Modules\Identity\Models\LoginHistory;

class RecordLoginHistory
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [self::class, 'onLogin']);
        $events->listen(Logout::class, [self::class, 'onLogout']);
        $events->listen(Failed::class, [self::class, 'onFailed']);
    }

    public function onLogin(Login $event): void
    {
        $this->record($event->user?->id, $event->user?->email, 'login');
    }

    public function onLogout(Logout $event): void
    {
        $this->record($event->user?->id, $event->user?->email, 'logout');
    }

    public function onFailed(Failed $event): void
    {
        $this->record($event->user?->id, $event->credentials['email'] ?? null, 'failed');
    }

    private function record(?int $userId, ?string $email, string $event): void
    {
        try {
            LoginHistory::create([
                'user_id' => $userId,
                'email' => $email,
                'event' => $event,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
            ]);
        } catch (\Throwable) {
            // never break auth flow for logging
        }
    }
}
