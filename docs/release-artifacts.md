# 13. Release Artifacts

This document defines the canonical artifact layout for phpMyFAQ releases.

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

## 13.3 Reserved signing outputs

The signing phase will add these files to the same directory:

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

## Notes

- The package payload is the `phpmyfaq/` directory prepared from a clean git checkout.
- The helper installs production dependencies and runs the frontend production build before packaging.
- TCPDF fonts and examples are removed from the packaged checkout, matching the existing release process.
- Signing is intentionally handled in a later phase so artifact naming and layout stay stable first.
