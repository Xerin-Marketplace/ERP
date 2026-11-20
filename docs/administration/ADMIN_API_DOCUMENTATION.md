# ADMIN ROUTES (web)

GET  /identity/admin-console                          admin.console.view
GET  /identity/admin-console/branch-admins            super-admin only
POST /identity/admin-console/branch-admins            {user_id, branch_id,
       delegable_roles[], delegable_permissions[], expires_at?}
POST .../branch-admins/{delegation}/suspend           super-admin only
POST .../branch-admins/{delegation}/reactivate        super-admin only
POST .../branch-admins/{delegation}/revoke            super-admin only
POST /identity/users/{user}/role                      roles.assign +
       AdminDelegationService::assertCanAssignRole (branch+delegable check)
