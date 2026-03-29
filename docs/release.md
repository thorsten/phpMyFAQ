# 13. Release

This document defines the canonical release artifact layout for phpMyFAQ and the local signing step for those artifacts.

## 13.1 Release directory

All release packages are prepared in:

```text
build/release/<version>/
```

Examples:

```text
build/release/4.2.0/
build/release/nightly-2026-03-25/
```

## 13.2 Canonical artifact names

Each release produces these package files:

- `phpMyFAQ-<version>.zip`
- `phpMyFAQ-<version>.tar.gz`

Examples:

- `phpMyFAQ-4.2.0.zip`
- `phpMyFAQ-4.2.0.tar.gz`
- `phpMyFAQ-nightly-2026-03-25.zip`
- `phpMyFAQ-nightly-2026-03-25.tar.gz`

## 13.3 Signing outputs

The signing phase adds these files to the same directory:

- `SHA256SUMS`
- `SHA256SUMS.asc`
- `phpMyFAQ-<version>.zip.asc`
- `phpMyFAQ-<version>.tar.gz.asc`

## 13.4 Build helper

Use the release helper script to prepare the canonical package layout from the current git index:

```bash
./scripts/prepare-release-artifacts.sh 4.2.0
```

This creates:

```text
build/release/4.2.0/phpMyFAQ-4.2.0.zip
build/release/4.2.0/phpMyFAQ-4.2.0.tar.gz
build/release/4.2.0/hashes-4.2.0.json
build/release/4.2.0/ARTIFACTS.txt
```

## 13.5 Signing command

To generate checksums and signatures:

```bash
./scripts/sign-release-artifacts.sh 4.2.0
```

The signing helper creates:

- `SHA256SUMS`
- `SHA256SUMS.asc`
- `phpMyFAQ-<version>.zip.asc`
- `phpMyFAQ-<version>.tar.gz.asc`

The helper also verifies the generated checksums and signatures before it exits.

## 13.6 Environment variables

Optional variables for GPG signing:

- `GPG_KEY_ID`
- `GPG_PASSPHRASE`

Example:

```bash
GPG_KEY_ID=0123456789ABCDEF \
GPG_PASSPHRASE='secret' \
./scripts/sign-release-artifacts.sh 4.2.0
```

## 13.7 Local checksum-only mode

If no release key is available, generate and verify checksums only:

```bash
SKIP_GPG=1 ./scripts/sign-release-artifacts.sh 4.2.0
```

This mode creates:

- `SHA256SUMS`

It does not create detached signatures.

## 13.8 Public key location

The public release-signing key should be published at:

```text
docs/keys/phpmyfaq-release-public-key.asc
```

The repository currently reserves that location but does not ship a fake placeholder key.
Add the real armored public key there once the dedicated release-signing key is created.

## 13.9 Verification

After downloading a release, users should have these files:

- `phpMyFAQ-<version>.zip`
- `phpMyFAQ-<version>.tar.gz`
- `SHA256SUMS`
- `SHA256SUMS.asc`
- `phpmyfaq-release-public-key.asc`

Import the release key:

```bash
gpg --import phpmyfaq-release-public-key.asc
```

Verify the checksum manifest signature:

```bash
gpg --verify SHA256SUMS.asc SHA256SUMS
```

Verify the package checksums:

```bash
sha256sum -c SHA256SUMS
```

Optional detached signature verification:

```bash
gpg --verify phpMyFAQ-<version>.zip.asc phpMyFAQ-<version>.zip
gpg --verify phpMyFAQ-<version>.tar.gz.asc phpMyFAQ-<version>.tar.gz
```

## 13.10 Fingerprint publication

When the real release key is available, publish the full fingerprint in:

- this document
- the GitHub release notes
- the project website release page

Use one exact fingerprint value everywhere. Do not publish shortened or inconsistent variants.

## 13.11 Release publication checklist

Publish these files with each release:

- `phpMyFAQ-<version>.zip`
- `phpMyFAQ-<version>.tar.gz`
- `SHA256SUMS`
- `SHA256SUMS.asc`
- optional detached signatures for each archive

The release notes should also include:

- the release key fingerprint
- a link to the verification instructions
- a link to the public key location

## 13.12 Notes

- The package payload is the `phpmyfaq/` directory prepared from a clean git checkout.
- The helper installs production dependencies and runs the frontend production build before packaging.
- TCPDF fonts and examples are removed from the packaged checkout, matching the existing release process.
- Use `./scripts/sign-release-artifacts.sh` to generate checksums and signatures.
