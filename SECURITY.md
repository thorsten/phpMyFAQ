# Security Policy

We take the security of phpMyFAQ and its ecosystem seriously.
If you believe you have found a vulnerability, please report it privately so we can triage and fix it promptly.

## Supported Versions

The following branches are supported for security fixes:

| Version | Status                 | Notes                         |
|---------|------------------------|-------------------------------|
| 4.1.x   | Active support         | Features and security fixes   |
| 4.0.x   | Security fixes only    | No new features               |
| < 4.0   | End of life (EOL)      | No fixes, please upgrade      |

We may backport critical fixes at our discretion. Users should keep up to date with the latest minor/patch releases.

## Report a Vulnerability (private)

Please use one of the following private channels:

- GitHub Private Report (preferred): https://github.com/thorsten/phpMyFAQ/security/advisories/new
- Email: security@phpmyfaq.de

When reporting, please include where possible:
- A clear description of the issue and its impact
- A minimal, reproducible proof of concept (PoC)
- Affected versions, configuration, and environment (PHP version, DB type, OS)
- Any relevant logs, stack traces, or screenshots
- Your contact information and whether you wish to be credited

## Our Response & Timelines

We try to strive to follow these SLAs:
- Acknowledgement: within 2 days
- Initial triage: within 7 days
- Fix timeline (target may vary by complexity):
  - Critical: 7–14 days
  - High: within 30 days
  - Medium: within 60 days
  - Low: within 90 days

We’ll provide periodic updates while triage and remediation are in progress.

## Coordinated Disclosure

- Please do not publish or share details publicly until a fix is available and users have had a reasonable time to update.
- We coordinate on a release date with you; our default disclosure window is up to 90 days from a report, adjusted by severity and complexity.
- We will credit reporters in release notes and/or advisories unless you prefer to remain anonymous.

## Scope Guidance

In scope examples:
- Remote code execution, SQL injection, authentication/authorization bypass
- Cross-site scripting (XSS) and request forgery (CSRF) with meaningful impact
- Sensitive information exposure (e.g., secrets, private data)

Out of scope examples (non-exhaustive):
- Denial of service without a clear, reproducible, and product-side fix
- Third‑party service issues outside this repository
- Clickjacking on non-sensitive pages, missing SPF/DMARC, best‑practice recommendations only
- Issues requiring non-default, unrealistic, or deliberately insecure configurations

## Receiving Fixes & Advisories

- Fixes are shipped in regular releases; notes appear in the changelog and/or GitHub release notes.
- For qualifying issues, we may publish a GitHub Security Advisory and request a CVE.
- Where possible, we will suggest mitigation or workarounds until a patch is available.

## Safe Harbor

We will not pursue legal action against researchers who:
- Act in good faith and avoid privacy violations, data destruction, or service disruption
- Report vulnerabilities privately and give us reasonable time to remediate
- Respect our users’ data and do not exfiltrate beyond what is necessary to prove impact

If you have any questions about this policy, contact us at security@phpmyfaq.de.

Thank you for helping keep phpMyFAQ and its users safe.

Copyright © 2001–2026 Thorsten Rinne and the phpMyFAQ Team
