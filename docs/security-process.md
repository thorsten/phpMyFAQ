# 17. Security Process

Internal runbook for handling vulnerability reports in phpMyFAQ. The public-facing policy is
[SECURITY.md](https://github.com/thorsten/phpMyFAQ/blob/main/SECURITY.md);
this document describes what happens on our side after a report arrives.

Audience: maintainers with security triage rights. It also serves as the audit trail description for
organisations evaluating phpMyFAQ.

## 17.1 Roles

| Role                | Responsibility                                                        |
|---------------------|-----------------------------------------------------------------------|
| Security contact    | Monitors security@phpmyfaq.de and GitHub private reports, acknowledges reports, owns the timeline |
| Triage maintainer   | Reproduces, assesses severity, decides in/out of scope                |
| Fix owner           | Develops and tests the patch in a private fork                        |
| Release manager     | Cuts the release, publishes the advisory, coordinates disclosure      |

One person may hold several roles. If a role is unstaffed for a report, the security contact
escalates rather than letting the clock run.

## 17.2 Prerequisites

- Admin access to the GitHub repository (Security tab, private advisory forks)
- Access to the security@phpmyfaq.de mailbox
- Ability to publish releases and update the website
- PGP key for encrypted correspondence

## 17.3 Intake

Reports arrive through GitHub private reporting or security@phpmyfaq.de. Both channels are checked at
least daily.

1. Acknowledge within 2 business days. Use the template below.
2. Make sure a **draft GitHub Security Advisory** exists immediately, even before triage. GitHub private
   reports already create one; for email reports, create it yourself. This is the single record for the
   report — do not track security work in public issues.
3. Assign an internal identifier (`PMF-SEC-YYYY-NNN`) and record the intake date.
4. If the report arrived by email, transfer the details into the draft advisory and continue the
   conversation from there where the reporter has a GitHub account.

Never forward report details to third parties, screenshots into chat channels, or paste PoCs into
external tools.

## 17.4 Triage

Complete within 7 calendar days of intake.

1. **Reproduce** against the latest supported release in a clean environment. Record the exact
   version, PHP version, database, and configuration used.
2. **Decide scope** using the
   [scope guidance in SECURITY.md](https://github.com/thorsten/phpMyFAQ/blob/main/SECURITY.md#scope-guidance).
   If out of scope, reply with a short explanation and close the advisory — politely, and with reasoning the reporter can check. Reports
   that only affect end-of-life versions are closed with an upgrade recommendation.
3. **Score severity** with CVSS v3.1 (add v4.0 where useful). Record the vector string in the
   advisory. Do not adjust the score to fit a preferred release timeline.
4. **Determine affected versions**: which branches contain the vulnerable code, and which of them are
   still supported (see the
   [supported versions table](https://github.com/thorsten/phpMyFAQ/blob/main/SECURITY.md#supported-versions)).
   Record the introducing commit where it can be identified.
5. **Assign a CWE** identifier.
6. Inform the reporter of the outcome, the severity, and the target fix date.

Severity guides the timeline, not whether the issue gets fixed:

| Severity | CVSS   | Fix target | Backport                        |
|----------|--------|------------|---------------------------------|
| Critical | 9.0+   | 7–14 days  | All supported branches          |
| High     | 7.0+   | 30 days    | All supported branches          |
| Medium   | 4.0+   | 60 days    | Active branch, backport if cheap |
| Low      | < 4.0  | 90 days    | Active branch only              |

## 17.5 Fix

1. Create a **private fork** from the draft advisory (GitHub provides this) and develop the patch
   there. Never push a security fix to a public branch before the release.
2. Write a **regression test** that fails without the patch. A security fix without a test is not
   complete.
3. Keep the commit message factual and non-descriptive of the exploit until disclosure
   (`Fix input validation in X`, not `Fix RCE via unserialize in X`).
4. Check for **variants**: the same pattern elsewhere in the codebase. Most reports are one instance
   of a class of bug. Search for the pattern before closing.
5. Backport, according to the table above.
6. Where a fix takes longer than the target, publish a **mitigation** — a configuration change, a
   `.htaccess` rule, a disabled feature — and communicate it to the reporter and, if the risk
   warrants it, publicly.

## 17.6 CVE assignment

1. In the draft advisory, use **Request CVE** — GitHub is a CNA and issues the identifier.
2. Fill in affected package (`thorsten/phpmyfaq` on Packagist), affected version ranges, patched
   version, CWE, CVSS vector, and a description written for a system administrator, not a
   pentester.
3. Credit the reporter as they requested. Check spelling and affiliation with them first.
4. Do not publish the advisory yet — publication is coordinated with the release.

## 17.7 Release and disclosure

1. Merge the private fork, cut the release, publish artifacts including the SBOM.
2. Publish the GitHub Security Advisory. This propagates to the GitHub Advisory Database, Packagist,
   and downstream scanners.
3. Update `CHANGELOG.md` with the CVE identifier and the advisory link.
4. Announce on the website, the release notes, and the project's usual channels. State clearly which
   versions are affected and what the upgrade path is.
5. Notify the reporter that disclosure has happened and thank them.
6. Check that the patched Docker images (`ghcr.io/thorsten/phpmyfaq`) were published. The
   `docker-publish.yml` workflow builds them for every release tag; if it failed, rerun it — a patched
   tarball with a stale image helps nobody.

Order matters: release first, advisory second, announcement third. Publishing an advisory before the
release exposes users with no upgrade path. The only exception is a vulnerability that is already being
exploited in the wild: then the advisory with mitigations may go out before the fix, as stated in
SECURITY.md.

The disclosure date is agreed with the reporter. The default window is up to 90 days from the report,
adjusted by severity and complexity.

## 17.8 After the fact

Within two weeks of disclosure:

- Record in the security log: identifier, CVE, severity, dates for each stage, whether targets were
  met, and the root cause class.
- Ask whether tooling could have caught it. If a Semgrep rule, a static analysis check, or a test
  pattern would have found it, add the rule — that is the real return on the incident.
- For High and Critical issues, write a short internal note on why the code was written that way.
  Not blame; pattern recognition.

Review the security log annually: which classes of vulnerability recur and whether response targets
are being met in practice. This log is what an enterprise evaluator will ask to see.

## 17.9 Records to keep

For each report, retain: intake date and channel, acknowledgement date, triage outcome and CVSS
vector, fix commit, release version, CVE identifier, disclosure date, reporter credit preference.

The draft advisory holds most of this automatically — the point is that the record exists per report
and is retrievable, rather than reconstructed from memory later.

## 17.10 Templates

**Acknowledgement**

> Thank you for reporting this to phpMyFAQ. We have received your report.
>
> We will complete initial triage within 7 days and come back to you with our assessment of severity
> and a target fix date. In the meantime, please keep the details private in line with our
> coordinated disclosure policy.
>
> If you have further details or an updated proof of concept, please add them to this advisory.

**Triage result — accepted**

> We have reproduced the issue against version X.Y.Z and assess it as <severity> (CVSS <vector>).
> We are targeting a fix in version X.Y.Z+1, expected by <date>.
>
> We would like to credit you as "<name>" in the advisory and release notes — please confirm or tell
> us how you would prefer to be credited, including if you would rather remain anonymous.

**Triage result — out of scope**

> Thank you for taking the time to look at phpMyFAQ. We assess this report as out of scope, because
> <reason, referencing the scope guidance in SECURITY.md>.
>
> If you believe we have misunderstood the impact, please reply with the additional detail and we
> will take another look.

**Disclosure notification**

> Version X.Y.Z has been released, and the advisory is now public:
> <advisory link>. The issue was assigned <CVE identifier>.
>
> Thank you again for the report and for handling it responsibly.
