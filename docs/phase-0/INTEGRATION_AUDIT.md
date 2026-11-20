# Integration Audit

- `system_integrations` registry + UI exists (name/type/keys metadata) — registry only, no adapter framework.
- Notifications: Laravel `Notification` infra used (invites, ticket events; `Notification::fake()` in tests) — in-app + mail channels.
- No WhatsApp/SMS provider adapters (spec calls WhatsApp business-critical for cargo updates).
- No webhook verification/outbound webhook system.
- Queue: `database` driver — works now; Redis recommended before cargo notification volume.
- No integration monitoring/retry dashboard.
