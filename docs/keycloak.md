# 9. Keycloak Integration

phpMyFAQ supports Keycloak as an OpenID Connect provider for frontend and administration logins.
The integration uses Authorization Code flow with PKCE.

Note:
The Keycloak integration is experimental. Keycloak also works with the generic
[OpenID Connect integration](openid-connect.md).

## 9.1 Prerequisites

Before you configure phpMyFAQ, make sure you have:

- a reachable Keycloak server, for example `https://sso.example.com`
- a realm for phpMyFAQ users, for example `faq`
- a confidential client for phpMyFAQ
- the public URL of your phpMyFAQ installation, for example `https://faq.example.com/`

## 9.2 Recommended Keycloak client settings

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

## 9.3 phpMyFAQ configuration

In the phpMyFAQ administration, open `Configuration` -> `Keycloak` (in the *Integrations* group of the configuration
tabs). The minimum configuration is:

1. Check `Enable Keycloak sign-in`
2. Set the `Keycloak base URL`, for example `https://sso.example.com`
3. Set the `Realm`
4. Set the `Client ID` and the `Client secret`
5. Set the `Redirect URI` to your phpMyFAQ callback URL. If you leave it empty, phpMyFAQ uses
   `<phpMyFAQ URL>/auth/keycloak/callback`.
6. Keep `Scopes` at least on `openid profile email`
7. Optionally set the `Logout redirect URL` to the page users should see after provider logout

phpMyFAQ derives the OpenID Connect discovery document from the base URL and the realm:
`<base URL>/realms/<realm>/.well-known/openid-configuration`. If Keycloak sign-in is enabled but the base URL or the
realm is missing, the login is aborted and the reason is written to the phpMyFAQ log.

Typical values:

- `keycloak.enable`: `true`
- `keycloak.baseUrl`: `https://sso.example.com`
- `keycloak.realm`: `faq`
- `keycloak.clientId`: `phpmyfaq`
- `keycloak.clientSecret`: `<client secret from Keycloak>`
- `keycloak.redirectUri`: `https://faq.example.com/auth/keycloak/callback`
- `keycloak.scopes`: `openid profile email`
- `keycloak.logoutRedirectUrl`: `https://faq.example.com/`

Optional user and group settings (all disabled by default):

- `keycloak.autoProvision`: `true` — create local users on their first successful Keycloak login
- `keycloak.groupAutoAssign`: `true` — assign mapped phpMyFAQ groups to newly provisioned users
- `keycloak.groupSyncOnLogin`: `true` — add and remove mapped group memberships on every login
- `keycloak.groupMapping`: `{"admin":"Administrators","faq-editors":"Editors"}` — JSON role-to-group mapping

## 9.4 User resolution and account linking

phpMyFAQ resolves Keycloak users in this order:

1. existing user linked by stored Keycloak subject (`sub`)
2. automatic provisioning of a new user, if enabled, named after the preferred username (or the email address if no preferred username is present)

The stored Keycloak subject is the only link between a local phpMyFAQ account and the external identity.
A Keycloak identity is never matched to an existing local account by username or email address: the
`preferred_username` and `email` claims are chosen by whoever controls the Keycloak account, so such a
match would let any Keycloak user take over a local account with a colliding name, including
administrators. If the login derived from the claims collides with an existing local account, or the
email address already belongs to a local account, the login is rejected and a warning is logged.

If automatic provisioning is disabled, only accounts that already store the Keycloak subject can sign in.
Accounts that predate Keycloak are not linked automatically. To link such an account manually, store the user's
Keycloak subject in the `keycloak_sub` column of the `faquserdata` table.

Provisioned users get the `name` claim (or the preferred username) as display name and the `email` claim as email
address. A Keycloak login only counts as the first factor: if the account has two-factor authentication enabled,
phpMyFAQ asks for the TOTP code before the session is granted.

## 9.5 Group mapping behavior

Group handling requires permission level `medium` and is intentionally conservative:

- phpMyFAQ reads the realm roles (`realm_access.roles`) and the client roles of the configured client
  (`resource_access.<client ID>.roles`) from the UserInfo response, so make sure your Keycloak role mappers add these
  claims to the UserInfo
- only roles listed in `keycloak.groupMapping` are managed by phpMyFAQ
- mapped groups are added when a user is provisioned and `keycloak.groupAutoAssign` is enabled
- when `keycloak.groupSyncOnLogin` is enabled, mapped groups are added and stale memberships of mapped groups are
  removed on every login
- a mapped phpMyFAQ group that does not exist yet is created automatically
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

## 9.6 Logout behavior

phpMyFAQ logs the user out locally and then redirects to the Keycloak end-session endpoint (with `id_token_hint`
and, if configured, `keycloak.logoutRedirectUrl` as `post_logout_redirect_uri`) when:

- Keycloak sign-in is enabled
- the current user is authenticated through Keycloak

If `keycloak.logoutRedirectUrl` is empty, Keycloak shows its own post-logout page.

For a reliable provider logout:

- set `keycloak.logoutRedirectUrl` in phpMyFAQ
- make sure the same URL is listed as a valid post-logout redirect URI in Keycloak

## 9.7 Troubleshooting

If login works but logout does not return to phpMyFAQ:

- verify `keycloak.logoutRedirectUrl`
- verify the matching valid post-logout redirect URI in Keycloak

If users are created but not assigned to groups:

- verify permission level `medium`
- verify `keycloak.groupAutoAssign` (new users) or `keycloak.groupSyncOnLogin` (existing users) is enabled
- verify the Keycloak role names exactly match the JSON mapping keys
- verify the roles are included in the UserInfo response

If group synchronization removes the wrong memberships:

- check `keycloak.groupMapping`
- remember that only mapped groups are managed

If an existing user cannot log in:

- check whether the stored Keycloak subject (`sub`) is already linked to another local account
- check whether the account stores the Keycloak subject at all; a local account whose username or email
  merely matches the Keycloak claims is rejected with a `not linked to the Keycloak subject` warning

If the login button redirects back to the start page without signing in, check the phpMyFAQ log. Typical causes are
a missing base URL or realm, a redirect URI that is not registered in Keycloak, or a subject mismatch between the ID
token and the UserInfo response.
