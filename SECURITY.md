# Security Policy

We take the security of phpMyFAQ and its ecosystem seriously.
If you believe you have found a vulnerability, please report it privately so we can triage and fix it promptly.

This document is the single point of contact for vulnerability reports concerning phpMyFAQ and
serves as our coordinated vulnerability disclosure (CVD) policy. Our internal handling procedure is
documented in [docs/security-process.md](docs/security-process.md).

## Supported Versions

The following branches are supported for security fixes:

| Version | Status              | Minimum PHP | Supported until       |
|---------|---------------------|-------------|-----------------------|
| 4.2.x   | Active support      | 8.4         | Until 4.3.0 + 6 months |
| 4.1.x   | Security fixes only | 8.3         | 2027-06-30            |
| < 4.1   | End of life (EOL)   | 8.2         | Unsupported           |

We may backport critical fixes at our discretion. Users should keep up to date with the latest
minor/patch releases. Running an EOL version is not a supported configuration; reports affecting
only EOL versions will be closed with an upgrade recommendation.

## Report a Vulnerability (private)

Please use one of the following private channels:

- GitHub Private Report (preferred): https://github.com/thorsten/phpMyFAQ/security/advisories/new
- Email: security@phpmyfaq.de
- Machine-readable contact information: https://www.phpmyfaq.de/.well-known/security.txt (RFC 9116)

Reports are accepted in English and German.

When reporting, please include where possible:

- A clear description of the issue and its impact
- A minimal, reproducible proof of concept (PoC)
- Affected versions, configuration, and environment (PHP version, DB type, OS)
- Any relevant logs, stack traces, or screenshots
- Your contact information and whether you wish to be credited

Please do not open a public issue, pull request, or discussion for security reports.

## Our Response and Timelines

We aim to meet the following response targets:

| Stage                     | Target                     |
|---------------------------|----------------------------|
| Acknowledgement of report | 2 business days            |
| Initial triage and severity assessment | 7 calendar days |
| Fix — Critical            | 7–14 days                  |
| Fix — High                | 30 days                    |
| Fix — Medium              | 60 days                    |
| Fix — Low                 | 90 days                    |

Severity is assessed using CVSS v3.1 (and v4.0 where applicable). These are targets, not contractual
guarantees; phpMyFAQ is provided without warranty under the terms of its licence. Organisations that
require contractual response times can obtain professional support directly from the maintainer —
see https://www.phpmyfaq.de/support. There are no third-party support vendors endorsed by the
project.

We provide periodic updates while triage and remediation are in progress.

## Coordinated Disclosure

- Please do not publish or share details publicly until a fix is available and users have had a
  reasonable time to update.
- We coordinate a disclosure date with you; our default disclosure window is up to 90 days from the
  report, adjusted by severity and complexity.
- If a vulnerability is already being exploited in the wild, we may shorten the window and publish an
  advisory with mitigations before a full fix is available.
- We credit reporters in release notes and advisories unless you prefer to remain anonymous.

We do not operate a bug bounty programme and do not offer monetary rewards.

## Scope Guidance

In scope:

- Remote code execution, SQL injection, authentication or authorization bypass
- Cross-site scripting (XSS) and request forgery (CSRF) with meaningful impact
- Server-side request forgery (SSRF), insecure direct object references (IDOR)
- Template injection, unsafe deserialization, path traversal
- Sensitive information exposure (for example secrets or private data)
- Vulnerabilities in first-party plugins and themes shipped with phpMyFAQ

Out of scope (non-exhaustive):

- Denial of service without a clear, reproducible, product-side fix
- Third-party services and dependencies outside this repository — please report those upstream and
  let us know so we can track the advisory
- Clickjacking on non-sensitive pages, missing SPF/DMARC, best-practice recommendations only
- Issues requiring non-default, unrealistic, or deliberately insecure configurations
- Findings from automated scanners without a demonstrated, exploitable impact
- Social engineering of maintainers or users

## Advisories, CVEs, and SBOM

- Fixes ship in regular releases; notes appear in the changelog and GitHub release notes.
- For qualifying issues we publish a GitHub Security Advisory and request a CVE identifier through
  GitHub as CNA.
- All published advisories are listed at
  https://github.com/thorsten/phpMyFAQ/security/advisories
- Each release includes a CycloneDX software bill of materials (SBOM) as a release artifact.
- Dependency updates are automated via Dependabot; static analysis runs in CI on every pull request.
- Where possible we publish mitigations or workarounds before a patch is available.

## Deployment Security

Security also depends on how phpMyFAQ is operated. See the hardening notes in
[docs/deployment.md](docs/deployment.md) and [docs/installation.md](docs/installation.md) for
recommended file permissions, TLS configuration, and settings that should be changed from their
defaults before going into production.

## Safe Harbor

We will not pursue legal action against researchers who:

- Act in good faith and avoid privacy violations, data destruction, or service disruption
- Report vulnerabilities privately and give us reasonable time to remediate
- Respect our users' data and do not exfiltrate beyond what is necessary to prove impact

This safe harbor covers the phpMyFAQ project. It does not bind third parties whose systems happen to
run phpMyFAQ — do not test against installations you do not own or have written permission to test.

## Regulatory Context

phpMyFAQ is free and open source software, licensed under the Mozilla Public License 2.0. The
software itself is made available to everyone free of charge; development is funded by voluntary
donations and sponsorship. Security fixes and advisories are published for all users at no cost,
ship in public releases, and are never conditional on paid services.

Optional professional services (custom development, consulting, installation, training, and
prioritised handling of issues) are offered separately by the maintainer — see
https://www.phpmyfaq.de/support. These services do not change how vulnerabilities are triaged,
fixed, or disclosed under this policy.

This policy, together with the process documented in [docs/security-process.md](docs/security-process.md),
describes our coordinated vulnerability disclosure process and single reporting point.

Organisations subject to their own regulatory requirements (for example the EU Cyber Resilience Act,
NIS2, or ISO 27001) that need documented assurances beyond this policy should contact
security@phpmyfaq.de to discuss what we can provide.

If you have any questions about this policy, contact us at security@phpmyfaq.de.

Thank you for helping keep phpMyFAQ and its users safe.

Copyright © 2001–2026 Thorsten Rinne and the phpMyFAQ Team
