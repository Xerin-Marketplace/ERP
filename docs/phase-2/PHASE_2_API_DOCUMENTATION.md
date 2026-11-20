# Phase 2 — API / Route Documentation

## Web (auth + verified + account.active, /identity)
- GET users · GET users/create · POST users (invite) · GET users/{u} · PUT users/{u}
- POST users/{u}/role (roles.assign, tier-guarded) · suspend · activate · deactivate
- POST users/{u}/revoke-sessions · POST users/{u}/reset-password · POST users/{u}/resend-invitation · POST users/{u}/assignments
- GET/POST/PUT/DELETE roles[...], POST roles/{r}/clone
- GET invitations · POST invitations/{i}/revoke
- GET permissions · GET permissions/matrix · PUT permissions/matrix/{role}
- GET access (branch access / dept access / access history tabs)

## API v1 (/api/v1, Sanctum)
Existing IdentityApiController endpoints retained; permission middleware updated to canonical `users.*`/`roles.*` names. Organization endpoints unchanged from Phase 1.

All mutations are audited (`audit_logs`: user.invited, user.role_changed, user.password_reset_sent, role.permissions_synced, invitation.revoked, …).
