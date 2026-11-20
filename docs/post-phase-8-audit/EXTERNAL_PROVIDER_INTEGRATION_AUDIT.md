# External Provider Integration Audit

| Provider | Status | Evidence | Gap |
|---|---|---|---|
| Email | PARTIAL→WORKING | `MAIL_MAILER=sendmail` configured on server (Postfix/Plesk); test mail accepted by Postfix | Gmail rejects: SPF/DKIM/PTR pending at DNS (management task); invitation mails only work once auth passes |
| SMS | MISSING | no provider code/config anywhere; `customer_messages` can record sms channel but nothing sends | choose provider (Beem/Africa's Talking/Twilio) — MANAGEMENT_DECISION |
| WhatsApp | MISSING | no code/config | needs Business Cloud API + templates + consent — MANAGEMENT_DECISION |
| S3 storage | BLOCKED | `s3` disk defined in filesystems.php, no bucket/env configured, all uploads local | decide provider + set env — PRODUCTION_READINESS |
| Payment gateway | MISSING | `payment_records` manual only; no gateway, no webhooks, no idempotent callbacks | Mobile money (M-Pesa/Tigo) decision required |
| Maps/routing | MISSING | distance pricing returns base+estimated flag (P6 honest gap) | provider credentials needed |
| Airline/carrier feeds | MISSING | flight events entered manually (verified-only model) | no GDS/live feed — likely out of scope |
| Customs (TRA) | MISSING | import docs recorded, no electronic filing | explicitly deferred in P5 |
| Mobile app | MISSING | no repo; delivery driver + portal APIs are contract-ready | new build decision |

Rule applied: presence of env vars/config stubs ≠ operational.
