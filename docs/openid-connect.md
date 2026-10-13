# OpenID Connect Integration

phpMyFAQ supports any OpenID Connect (OIDC) provider for frontend and administration logins,
for example Authentik, Okta, Auth0 or Zitadel. The integration uses the Authorization Code flow
with PKCE, validates the ID token signature against the provider's JWKS, and links accounts by
the `sub` claim. For Keycloak, prefer the dedicated [Keycloak Integration](keycloak.md).

## 1. Prerequisites

- a reachable OIDC provider that publishes a discovery document at
  `.../.well-known/openid-configuration`
- a confidential client (client ID and client secret) for phpMyFAQ
- the public URL of your phpMyFAQ installation, for example `https://faq.example.com/`

## 2. Provider client settings

- Client type: confidential
- Grant: Authorization Code with PKCE (`S256`)
- Redirect URI: `https://faq.example.com/auth/oidc/callback`
- Post-logout redirect URI: `https://faq.example.com/`
- Signing algorithm: RS256 (RS384 and RS512 are accepted too; ES256 and HS256 are not)
- Scopes: `openid profile email`, plus the scope that exposes groups if you want group mapping

### Authentik example

1. Create an *OAuth2/OpenID Provider* with client type *Confidential*, redirect URI
   `https://faq.example.com/auth/oidc/callback`, signing key *authentik Self-signed Certificate*.
2. Create an *Application* for it with slug `faq`.
3. The discovery URL is `https://auth.example.com/application/o/faq/.well-known/openid-configuration`.
4. Authentik's default `profile` scope already adds a `groups` claim with the user's group names.

## 3. phpMyFAQ configuration

In the administration, open `Configuration` -> `OpenID Connect` and set:

| Setting | Example |
| --- | --- |
| Enable OpenID Connect sign-in | checked |
| Provider name | `Authentik` |
| Discovery URL | `https://auth.example.com/application/o/faq/.well-known/openid-configuration` |
| Client ID | `phpmyfaq` |
| Client secret | from the provider |
| Redirect URI | `https://faq.example.com/auth/oidc/callback` (optional, this is the default) |
| Scopes | `openid profile email` |
| Logout redirect URL | `https://faq.example.com/` |

The login pages then show a *Sign in with Authentik* button.

## 4. User resolution and account linking

1. An account whose stored OIDC issuer and subject equal the ID token's `iss` and `sub` claims is used.
2. Otherwise, when automatic provisioning is enabled, a new account is created with the
   `preferred_username` claim as login (or the `email` claim when there is no username).
3. If that login or email already belongs to an account that is not linked, the login is refused
   and logged. phpMyFAQ never links an existing account to a subject at login time.

> **Note:** The `sub` claim is only unique per issuer, so account links are bound to the issuer
> (`oidc_iss`) as well as the subject (`oidc_sub`). Switching `oidc.discoveryUrl` to another
> provider therefore never maps its users onto existing accounts. Users provisioned by the old
> provider are refused (logged as an issuer mismatch or as an account not linked to this issuer
> and subject) and must be re-linked or deleted.

## 5. Group mapping

Requires permission level `medium`.

- *Groups claim*: name of the claim that holds the user's groups as a list of strings
  (default `groups`).
- *Group mapping*: JSON object from provider group name to phpMyFAQ group name, for example
  `{"faq-admins":"Administrators","faq-editors":"Editors"}`. Only mapped groups are managed.
- *Automatically assign groups*: add mapped groups on first login.
- *Synchronize groups on login*: also remove mapped groups the user no longer holds.

## 6. Logout

Logout clears the phpMyFAQ session and redirects to the provider's `end_session_endpoint` with
`id_token_hint` and, when configured, the logout redirect URL as `post_logout_redirect_uri`. When the
logout redirect URL is empty, no `post_logout_redirect_uri` is sent and the provider shows its own
post-logout page. Providers without an end-session endpoint
only get a local logout.

## 7. Troubleshooting

- *Button missing*: `Enable OpenID Connect sign-in` is off.
- *Redirect loops back to the start page*: check the phpMyFAQ log; usual causes are a wrong
  discovery URL, an unsupported signing algorithm, a redirect URI mismatch, or a `sub` mismatch
  between ID token and UserInfo.
- *User exists but cannot log in*: the account is not linked to this issuer and subject. Delete
  and let it be re-provisioned, or set both `oidc_sub` and `oidc_iss` for the account in the
  `faquserdata` table.
- *No groups assigned*: permission level must be `medium`, the claim must be a list of strings,
  and the group names must match the mapping keys exactly.
