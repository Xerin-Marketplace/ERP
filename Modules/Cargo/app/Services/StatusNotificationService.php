<?php

namespace Modules\Cargo\Services;

use Illuminate\Support\Facades\Notification;
use Modules\Cargo\Models\CargoPackage;
use Modules\Cargo\Notifications\PackageStatusUpdate;
use Modules\Commercial\Models\Customer;
use Modules\Portal\Services\PortalService;

/**
 * Spec §12.2 — customer-facing status notifications.
 *
 * Internal cargo events are mapped to customer-safe milestone messages
 * and fanned out over the customer's preferred channels
 * (communication_preferences.channels: whatsapp|sms|email|in_app).
 *
 * Every outbound message is recorded in customer_messages with an
 * honest delivery status:
 *   in_app / email → 'sent' (in-app visible immediately; mail queued)
 *   whatsapp / sms → 'queued' (gateway integration pending — never
 *   claimed delivered, per PortalService's contract).
 */
class StatusNotificationService
{
    /** Internal event → customer-safe template. Unknown = stays internal. */
    private const TEMPLATES = [
        'received'  => 'Your shipment {tracking} has been received at our Dubai facility.',
        'inspected' => 'Shipment {tracking} has passed inspection.',
        'packed'    => 'Shipment {tracking} has been packed and sealed for dispatch.',
        'departed'  => 'Shipment {tracking} has departed for Tanzania.',
        'arrived'   => 'Shipment {tracking} has arrived in Tanzania.',
        'customs'   => 'Shipment {tracking} is under customs clearance.',
        'stored'    => 'Shipment {tracking} is in secure storage at our warehouse.',
        'ready'     => 'Shipment {tracking} is ready for release at our Tanzania office.',
        'released'  => 'Shipment {tracking} has been released.',
        'delivered' => 'Shipment {tracking} has been delivered.',
    ];

    public function __construct(private PortalService $portal) {}

    /** Fan out a package milestone to the owning customer (if known). */
    public function notifyPackageEvent(CargoPackage $package, string $event): void
    {
        $template = self::TEMPLATES[$event] ?? null;
        if (! $template) return; // internal ops events never reach customers

        $customer = $package->customer ?? $package->booking?->customer;
        if (! $customer) return; // unmatched cargo has nobody to notify

        $tracking = $package->tracking_no ?: $package->reference;
        $body = strtr($template, ['{tracking}' => $tracking]);
        $this->fanOut($customer, "Shipment update: {$tracking}", $body, 'shipment', $package->id);
    }

    private function fanOut(Customer $customer, string $subject, string $body, string $type, int $id): void
    {
        $channels = $customer->communicationPreference?->channels ?? [];
        foreach (['in_app', 'email', 'whatsapp', 'sms'] as $channel) {
            if (! ($channels[$channel] ?? true)) continue;

            $message = $this->portal->queueMessage($customer, $channel, $body, [
                'subject' => $subject, 'direction' => 'out',
                'related_type' => $type, 'related_id' => $id,
            ]);

            if ($channel === 'in_app') {
                $message->update(['status' => 'sent']); // visible in portal now
            } elseif ($channel === 'email' && $customer->email) {
                try {
                    Notification::route('mail', $customer->email)
                        ->notify(new PackageStatusUpdate($subject, $body,
                            url('/track')));
                    $message->update(['status' => 'sent']);
                } catch (\Throwable) {
                    $message->update(['status' => 'failed']);
                }
            }
            // whatsapp/sms stay 'queued' — recorded for the gateway worker.
        }
    }
}
