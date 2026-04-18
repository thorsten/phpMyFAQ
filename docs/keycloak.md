# Keycloak Integration

phpMyFAQ supports Keycloak as an OpenID Connect provider for frontend and administration logins.
The integration uses Authorization Code flow with PKCE.

## 1. Prerequisites

Before you configure phpMyFAQ, make sure you have:

- a reachable Keycloak server, for example `https://sso.example.com`
- a realm for phpMyFAQ users, for example `faq`
- a confidential client for phpMyFAQ
- the public URL of your phpMyFAQ installation, for example `https://faq.example.com/`

## 2. Recommended Keycloak client settings

Recommended client settings for a phpMyFAQ installation at `https://faq.example.com/`:

- Client type: confidential
- Standard flow enabled
- Direct access grants are disabled unless required for another integration
- Service accounts disabled
- PKCE code challenge method: `S256`
- Root URL: `https://faq.example.com/`
- Home URL: `https://faq.example.com/`
- Valid redirect URIs:
  - `https://faq.example.com/auth/keycloak/callback`
- Valid post logout redirect URIs:
  - `https://faq.example.com/`
- Web origins:
  - `https://faq.example.com`

## 3. phpMyFAQ configuration

In phpMyFAQ, open:

- `Configuration`
- `Security`
- `Keycloak`

Typical values:

- `keycloak.enable`: `true`
- `keycloak.baseUrl`: `https://sso.example.com`
- `keycloak.realm`: `faq`
- `keycloak.clientId`: `phpmyfaq`
- `keycloak.clientSecret`: `<client secret from Keycloak>`
- `keycloak.redirectUri`: `https://faq.example.com/auth/keycloak/callback`
- `keycloak.scopes`: `openid profile email`
- `keycloak.logoutRedirectUrl`: `https://faq.example.com/`

Optional user and group settings:

- `keycloak.autoProvision`: `true`
- `keycloak.groupAutoAssign`: `true`
- `keycloak.groupSyncOnLogin`: `true`
- `keycloak.groupMapping`: `{"admin":"Administrators","faq-editors":"Editors"}`

## 4. User resolution and account linking

phpMyFAQ resolves Keycloak users in this order:

1. existing user linked by stored Keycloak subject (`sub`)
2. preferred username from Keycloak
3. existing user by email address
4. automatic provisioning, if enabled

The stored Keycloak subject is the durable link between a local phpMyFAQ account and the external identity.

If automatic provisioning is disabled, users must already exist in phpMyFAQ before they can sign in.

## 5. Group mapping behavior

Group handling is intentionally conservative:

- only roles listed in `keycloak.groupMapping` are managed by phpMyFAQ
- mapped groups are added on login when `keycloak.groupAutoAssign` is enabled
- stale memberships are removed only for mapped groups when `keycloak.groupSyncOnLogin` is enabled
- phpMyFAQ groups outside the configured mapping are left untouched

Example mapping:

```json
{
  "admin": "Administrators",
  "faq-editors": "Editors"
}
```

This means:

- Keycloak role `admin` maps to phpMyFAQ group `Administrators`
- Keycloak role `faq-editors` maps to phpMyFAQ group `Editors`

## 6. Logout behavior

phpMyFAQ logs the user out locally and then redirects to Keycloak logout when:

- Keycloak sign-in is enabled
- the current user is authenticated through Keycloak

For a reliable provider logout:

- set `keycloak.logoutRedirectUrl` in phpMyFAQ
- make sure the same URL is listed as a valid post-logout redirect URI in Keycloak

## 7. Troubleshooting

If login works but logout does not return to phpMyFAQ:

- verify `keycloak.logoutRedirectUrl`
- verify the matching valid post-logout redirect URI in Keycloak

If users are created but not assigned to groups:

- verify permission level `medium`
- verify `keycloak.groupAutoAssign` is enabled
- verify the Keycloak role names exactly match the JSON mapping keys

If group synchronization removes the wrong memberships:

- check `keycloak.groupMapping`
- remember that only mapped groups are managed

If an existing user cannot log in:

- check whether the stored Keycloak subject (`sub`) is already linked to another local account
