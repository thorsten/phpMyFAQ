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
- `phpMyFAQ-<version>.php.sbom.json.asc`
- `phpMyFAQ-<version>.js.sbom.json.asc`
- `phpMyFAQ-<version>.sbom.json.asc`

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
build/release/4.2.0/phpMyFAQ-4.2.0.php.sbom.json
build/release/4.2.0/phpMyFAQ-4.2.0.js.sbom.json
build/release/4.2.0/phpMyFAQ-4.2.0.sbom.json
build/release/4.2.0/ARTIFACTS.txt
```

The release helper invokes `scripts/generate-sbom.sh` after packaging, so
CycloneDX Software Bill of Materials files are emitted alongside the archives
without an extra manual step. See section 13.13 for the standalone usage of
the SBOM helper.

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
- `phpMyFAQ-<version>.php.sbom.json.asc`
- `phpMyFAQ-<version>.js.sbom.json.asc`
- `phpMyFAQ-<version>.sbom.json.asc`

The `SHA256SUMS` manifest covers both archives and all three SBOM files, and
detached signatures are produced for each of them.

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

The public release-signing key is published at:

```text
docs/keys/phpmyfaq-release-public-key.asc
```

The file is an ASCII-armored OpenPGP public key block
(`-----BEGIN PGP PUBLIC KEY BLOCK-----`). Only the public key is committed to
the repository; the primary private key and its revocation certificate are
kept offline, and the signing subkey lives on the dedicated release signing
machine (or a hardware token).

To export the public key from the release keyring, run:

```bash
gpg --armor --export <FINGERPRINT> \
  > docs/keys/phpmyfaq-release-public-key.asc
```

Replace `<FINGERPRINT>` with the full 40-character fingerprint from
section 13.10. Never export the file with `--export-secret-keys`.

## 13.9 Verification

After downloading a release, users should have these files:

- `phpMyFAQ-<version>.zip`
- `phpMyFAQ-<version>.tar.gz`
- `phpMyFAQ-<version>.php.sbom.json`
- `phpMyFAQ-<version>.js.sbom.json`
- `phpMyFAQ-<version>.sbom.json`
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
gpg --verify phpMyFAQ-<version>.php.sbom.json.asc phpMyFAQ-<version>.php.sbom.json
gpg --verify phpMyFAQ-<version>.js.sbom.json.asc phpMyFAQ-<version>.js.sbom.json
gpg --verify phpMyFAQ-<version>.sbom.json.asc phpMyFAQ-<version>.sbom.json
```

## 13.10 Fingerprint publication

Release key fingerprint:

```text
TODO FILL IN  TODO FILL IN  TODO FILL IN  TODO FILL IN  TODO FILL IN
```

<!--
  Replace the placeholder line above with the full 40-character fingerprint
  exactly as produced by:

      gpg --fingerprint release@phpmyfaq.de

  Keep the grouping used by GnuPG (ten groups of four hex characters,
  separated by double spaces between the fifth and sixth group).
-->

Key metadata:

- UID: `phpMyFAQ Release Signing Key <release@phpmyfaq.de>`
- Long key ID: `TODO FILL IN` (16 hex characters)
- Created: `TODO FILL IN`
- Expires: `TODO FILL IN`

Publish this exact fingerprint in every one of the following locations:

- this document
- the GitHub release notes for each tagged release
- the project website release page
- any third-party mirror that ships the phpMyFAQ archives

Use one exact fingerprint value everywhere. Do not publish shortened or
inconsistent variants. If the key is ever rotated or revoked, update this
section and announce the change in the release notes of the first release
signed with the new key.

## 13.11 Release publication checklist

Publish these files with each release:

- `phpMyFAQ-<version>.zip`
- `phpMyFAQ-<version>.tar.gz`
- `phpMyFAQ-<version>.php.sbom.json`
- `phpMyFAQ-<version>.js.sbom.json`
- `phpMyFAQ-<version>.sbom.json`
- `SHA256SUMS`
- `SHA256SUMS.asc`
- optional detached signatures for each archive and each SBOM file

The release notes should also include:

- the release key fingerprint
- a link to the verification instructions
- a link to the public key location

## 13.13 Software Bill of Materials (SBOM)

Each release ships a [CycloneDX](https://cyclonedx.org/) 1.5 Software Bill of
Materials that enumerates every pinned dependency of the packaged code. Three
files are produced per release:

- `phpMyFAQ-<version>.php.sbom.json` — Composer (PHP) dependency graph only.
- `phpMyFAQ-<version>.js.sbom.json` — pnpm (JavaScript/TypeScript) dependency graph only.
- `phpMyFAQ-<version>.sbom.json` — combined PHP + JavaScript/TypeScript graph.

The SBOMs are included in `SHA256SUMS` and each one gets its own detached GPG
signature during the signing phase, so downstream consumers can verify the
provenance of the bill of materials using the same release key that signs the
archives.

### 13.13.1 Automatic generation

`scripts/prepare-release-artifacts.sh` calls the SBOM helper automatically, so
the release directory already contains the three JSON files before signing.
Nothing extra is required for a standard release.

### 13.13.2 Standalone generation

To generate the SBOM files without running a full release build, use the
helper directly:

```bash
./scripts/generate-sbom.sh
```

Both arguments are optional:

```bash
./scripts/generate-sbom.sh [source-dir] [output-dir]
```

- `source-dir` defaults to the repository root.
- `output-dir` defaults to `build/release/<version>/`, where `<version>` is
  resolved from `scripts/get-version.php`.

Environment variables:

- `VERSION` — override the release version used in filenames and the CycloneDX
  metadata.
- `CDXGEN_VERSION` — pin a specific `@cyclonedx/cdxgen` version (defaults to
  `latest`).
- `CDXGEN_SPEC_VERSION` — override the CycloneDX spec version (defaults to
  `1.5`).
- `PHP_BIN` — override the PHP binary used to resolve the release version.

### 13.13.3 Requirements

The helper needs:

- `pnpm` on the `PATH` (used via `pnpm dlx` to run `@cyclonedx/cdxgen`).
- A valid `composer.lock` in the source directory.
- A valid `pnpm-lock.yaml` in the source directory.
- `php` (or `PHP_BIN`) to resolve the release version when `VERSION` is not set.

## 13.14 Notes

- The package payload is the `phpmyfaq/` directory prepared from a clean git checkout.
- The helper installs production dependencies and runs the frontend production build before packaging.
- TCPDF fonts and examples are removed from the packaged checkout, matching the existing release process.
- Use `./scripts/sign-release-artifacts.sh` to generate checksums and signatures.
- Use `./scripts/generate-sbom.sh` to regenerate the CycloneDX SBOM files outside of a full release build.

## 13.15 Key rotation and revocation

The release key is long-lived but not permanent. Plan for three scenarios:
routine expiry extension, planned rotation, and emergency revocation after a
compromise.

### 13.15.1 Extending the expiration date

When the key is approaching its expiry but is otherwise healthy, extend the
validity instead of generating a new key. This keeps the fingerprint stable
and avoids re-establishing trust.

```bash
gpg --edit-key <FINGERPRINT>
gpg> expire          # extend the primary key
gpg> key 1           # select the signing subkey
gpg> expire          # extend the subkey
gpg> save
```

After extending, re-export the public key and update the repository:

```bash
gpg --armor --export <FINGERPRINT> \
  > docs/keys/phpmyfaq-release-public-key.asc
```

Also push the updated key to any keyserver the project uses:

```bash
gpg --keyserver keys.openpgp.org --send-keys <FINGERPRINT>
```

Commit the refreshed `docs/keys/phpmyfaq-release-public-key.asc` and update
the `Expires:` line in section 13.10.

### 13.15.2 Rotating the signing subkey only

Rotating the **signing subkey** while keeping the **primary key** preserves
the fingerprint in section 13.10 and keeps all historic signatures valid.
This is the preferred rotation path for routine key hygiene.

```bash
gpg --edit-key <FINGERPRINT>
gpg> addkey          # follow the prompts: sign-only, Ed25519 or RSA 4096
gpg> save
```

Then:

1. Re-export the public key to `docs/keys/phpmyfaq-release-public-key.asc`
   so downstream users receive the new subkey.
2. Revoke the previous signing subkey once the new one is in place:
   `gpg --edit-key <FINGERPRINT>` → `key <N>` → `revkey` → `save`.
3. Re-export the public key again so the revocation is published.
4. Update the signing machine / hardware token with the new subkey only.
5. Note the rotation in the release notes of the first release signed with
   the new subkey.

### 13.15.3 Full key rotation

If the primary key must change (for example, the algorithm needs upgrading
or the old key is considered too weak), generate a new key following the
steps in the "how to add a new release signing key" flow and then:

1. Sign the new key with the old key to create an explicit bridge of trust:

   ```bash
   gpg --default-key <OLD_FINGERPRINT> --sign-key <NEW_FINGERPRINT>
   ```

2. Publish a transition statement in the release notes and on the project
   website. The statement must be signed with the **old** key and announce
   the **new** fingerprint.
3. Update `docs/release.md` section 13.10 with the new fingerprint, long
   key ID, creation date, and expiration.
4. Replace `docs/keys/phpmyfaq-release-public-key.asc` with the export of
   the new key.
5. Keep signing releases with the old key for one transition release, so
   users have time to import the new key before it becomes mandatory.
6. Retire the old key after the transition release by importing its
   revocation certificate and publishing the revocation.

### 13.15.4 Emergency revocation after compromise

If the signing key or its passphrase is suspected to be compromised, act
immediately and assume every signature made after the suspected compromise
date is untrustworthy.

1. Import the pre-generated revocation certificate from offline backup:

   ```bash
   gpg --import revocation-cert.asc
   ```

2. Push the revocation to keyservers:

   ```bash
   gpg --keyserver keys.openpgp.org --send-keys <FINGERPRINT>
   ```

3. Commit an updated `docs/keys/phpmyfaq-release-public-key.asc` that
   includes the revocation signature, and a clearly marked security advisory
   in the release notes and on the project website.
4. Generate a brand-new release key (see the new-key flow) and publish the
   new fingerprint in section 13.10 with a note that all releases after the
   compromise date must be re-verified against the new key.
5. Re-sign the currently published release archives and SBOMs with the new
   key. Update `SHA256SUMS.asc` and every `<file>.asc` artifact in place.
   Keep the archives themselves byte-identical — only the detached
   signatures change.
6. File a security advisory in the repository's security advisories section
   so downstream package maintainers are notified through the normal GitHub
   security channel.

The revocation certificate can only be created while the private key is
still available. Always generate it at key-creation time (see the new-key
flow) and store it with the offline backup of the primary key.

### 13.15.5 Post-rotation checklist

After any of the flows above, verify the following before cutting the next
release:

- `gpg --show-keys docs/keys/phpmyfaq-release-public-key.asc` lists the
  expected primary key, active signing subkey, and (if applicable)
  revocation status.
- Section 13.10 reflects the current fingerprint, long key ID, creation
  date, and expiration.
- A dry-run `./scripts/sign-release-artifacts.sh 0.0.0-signtest` succeeds
  end-to-end, including the internal `gpg --verify` pass on every
  `.asc` file.
- CI secrets (`GPG_KEY_ID`, `GPG_PASSPHRASE`, any imported signing subkey
  export) are updated to match the new key material.
