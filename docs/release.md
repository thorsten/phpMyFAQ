# 16. Release

This document defines the canonical release artifact layout for phpMyFAQ and the local signing step for those artifacts.

## 16.1 Release directory

All release packages are prepared in:

```text
build/release/<version>/
```

Examples:

```text
build/release/4.2.0/
build/release/nightly-2026-03-25/
```

## 16.2 Canonical artifact names

Each release produces these package files:

- `phpMyFAQ-<version>.zip`
- `phpMyFAQ-<version>.tar.gz`

Examples:

- `phpMyFAQ-4.2.0.zip`
- `phpMyFAQ-4.2.0.tar.gz`
- `phpMyFAQ-nightly-2026-03-25.zip`
- `phpMyFAQ-nightly-2026-03-25.tar.gz`

## 16.3 Signing outputs

The signing phase adds these files to the same directory:

- `SHA256SUMS`
- `SHA256SUMS.asc`
- `phpMyFAQ-<version>.zip.asc`
- `phpMyFAQ-<version>.tar.gz.asc`
- `phpMyFAQ-<version>.php.sbom.cdx.json.asc`
- `phpMyFAQ-<version>.js.sbom.cdx.json.asc`
- `phpMyFAQ-<version>.sbom.cdx.json.asc`

## 16.4 Build helper

Use the release helper script to prepare the canonical package layout from the current git index:

```bash
./scripts/prepare-release-artifacts.sh 4.2.0
```

The version argument is optional. Without it, the version reported by `phpMyFAQ\System::getVersion()` (via
`scripts/get-version.php`) is used. `pnpm release:artifacts` runs the same script.

This creates:

```text
build/release/4.2.0/phpMyFAQ-4.2.0.zip
build/release/4.2.0/phpMyFAQ-4.2.0.zip.md5
build/release/4.2.0/phpMyFAQ-4.2.0.zip.sha256
build/release/4.2.0/phpMyFAQ-4.2.0.tar.gz
build/release/4.2.0/phpMyFAQ-4.2.0.tar.gz.md5
build/release/4.2.0/phpMyFAQ-4.2.0.tar.gz.sha256
build/release/4.2.0/hashes-4.2.0.json
build/release/4.2.0/phpMyFAQ-4.2.0.php.sbom.cdx.json
build/release/4.2.0/phpMyFAQ-4.2.0.js.sbom.cdx.json
build/release/4.2.0/phpMyFAQ-4.2.0.sbom.cdx.json
build/release/4.2.0/ARTIFACTS.txt
```

`hashes-<version>.json` is the file hash manifest used by the verification API (see section 16.15.2); it is not
uploaded to the download server. The `.sha256` files are only written if `sha256sum` or `shasum` is available.

The helper also generates the CycloneDX SBOM files, see section 16.12.

The intermediate directories `build/checkout/` and `build/package/` are removed when the script exits. Set
`KEEP_BUILD=1` to keep them for debugging.

## 16.5 Signing command

To generate checksums and signatures:

```bash
./scripts/sign-release-artifacts.sh 4.2.0
```

As with the build helper, the version argument is optional. `pnpm release:sign` runs the same script. The release
directory must already contain both archives and all three SBOM files, otherwise the script aborts.

The signing helper creates the files listed in section 16.3. The `SHA256SUMS` manifest covers both archives and all
three SBOM files, and detached signatures are produced for the manifest and each of these files.

The helper also verifies the generated checksums and signatures before it exits.

## 16.6 Environment variables

Optional variables for GPG signing:

- `GPG_KEY_ID` — key to sign with (passed as `--local-user`); without it, GnuPG uses its default key
- `GPG_PASSPHRASE` — passphrase for non-interactive signing (uses `--pinentry-mode loopback`)

Example:

```bash
GPG_KEY_ID=0123456789ABCDEF \
GPG_PASSPHRASE='secret' \
./scripts/sign-release-artifacts.sh 4.2.0
```

`GPG_PASSPHRASE` is passed to `gpg` on the command line and is therefore visible in the process list of the signing
machine while the helper runs. Prefer leaving it unset and unlocking the key through `gpg-agent` instead.

## 16.7 Local checksum-only mode

If no release key is available, generate and verify checksums only:

```bash
SKIP_GPG=1 ./scripts/sign-release-artifacts.sh 4.2.0
```

This mode creates:

- `SHA256SUMS`

It does not create detached signatures.

## 16.8 Public key location

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
section 16.10. Never export the file with `--export-secret-keys`.

<a id="139-verification"></a>

## 16.9 Verification

After downloading a release, users should have these files:

- `phpMyFAQ-<version>.zip`
- `phpMyFAQ-<version>.tar.gz`
- `phpMyFAQ-<version>.php.sbom.cdx.json`
- `phpMyFAQ-<version>.js.sbom.cdx.json`
- `phpMyFAQ-<version>.sbom.cdx.json`
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

If you downloaded only some of the files, add `--ignore-missing` (GNU coreutils). On macOS, use
`shasum -a 256 -c SHA256SUMS`.

Optional detached signature verification (the GitHub release only carries the `.asc` files of the archives and of
`SHA256SUMS`; the SBOM signatures are available from download.phpmyfaq.de):

```bash
gpg --verify phpMyFAQ-<version>.zip.asc phpMyFAQ-<version>.zip
gpg --verify phpMyFAQ-<version>.tar.gz.asc phpMyFAQ-<version>.tar.gz
gpg --verify phpMyFAQ-<version>.php.sbom.cdx.json.asc phpMyFAQ-<version>.php.sbom.cdx.json
gpg --verify phpMyFAQ-<version>.js.sbom.cdx.json.asc phpMyFAQ-<version>.js.sbom.cdx.json
gpg --verify phpMyFAQ-<version>.sbom.cdx.json.asc phpMyFAQ-<version>.sbom.cdx.json
```

## 16.10 Fingerprint publication

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

## 16.11 Release publication checklist

Publish the archives, SBOM files, `SHA256SUMS`, and `SHA256SUMS.asc` listed in section 16.9 with each release, plus the
detached signatures for each archive and each SBOM file. The one-command release (section 16.15) uploads all of them to
download.phpmyfaq.de and attaches all but the SBOM signatures to the GitHub release.

The release notes should also include:

- the release key fingerprint
- a link to the verification instructions
- a link to the public key location

The `github-release` stage adds the two links automatically; the fingerprint has to be added by hand.

## 16.12 Software Bill of Materials (SBOM)

Each release ships a [CycloneDX](https://cyclonedx.org/) 1.5 Software Bill of
Materials that enumerates every pinned dependency of the packaged code. Three
files are produced per release:

- `phpMyFAQ-<version>.php.sbom.cdx.json` — Composer (PHP) dependency graph only.
- `phpMyFAQ-<version>.js.sbom.cdx.json` — pnpm (JavaScript/TypeScript) dependency graph only.
- `phpMyFAQ-<version>.sbom.cdx.json` — combined PHP + JavaScript/TypeScript graph.

The SBOMs are included in `SHA256SUMS` and each one gets its own detached GPG
signature during the signing phase, so downstream consumers can verify the
provenance of the bill of materials using the same release key that signs the
archives.

### 16.12.1 Automatic generation

`scripts/prepare-release-artifacts.sh` calls the SBOM helper automatically, so
the release directory already contains the three JSON files before signing.
Nothing extra is required for a standard release.

### 16.12.2 Standalone generation

To generate the SBOM files without running a full release build, use the
helper directly (or `pnpm sbom`):

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

### 16.12.3 Requirements

The helper needs:

- `pnpm` on the `PATH` (used via `pnpm dlx` to run `@cyclonedx/cdxgen`).
- A valid `composer.lock` in the source directory.
- A valid `pnpm-lock.yaml` in the source directory.
- `php` (or `PHP_BIN`) to resolve the release version when `VERSION` is not set.

## 16.13 Notes

- The package payload is the `phpmyfaq/` directory exported from the git index (`git checkout-index`), so staged but
  uncommitted changes end up in the package. Build releases from a clean working tree.
- The helper installs production dependencies (`composer install --no-dev`) and runs the frontend production build
  (`pnpm build:prod`) before packaging.
- JavaScript source maps are removed from the built assets.
- The TCPDF `examples` and `test` directories and the `util` and `target` directories of `tc-lib-pdf-font` are removed
  from the packaged checkout.

## 16.14 Key rotation and revocation

The release key is long-lived but not permanent. Plan for three scenarios:
routine expiry extension, planned rotation, and emergency revocation after a
compromise.

### 16.14.1 Extending the expiration date

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
the `Expires:` line in section 16.10.

### 16.14.2 Rotating the signing subkey only

Rotating the **signing subkey** while keeping the **primary key** preserves
the fingerprint in section 16.10 and keeps all historic signatures valid.
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

### 16.14.3 Full key rotation

If the primary key must change (for example, the algorithm needs upgrading
or the old key is considered too weak), generate a new key together with its
revocation certificate and then:

1. Sign the new key with the old key to create an explicit bridge of trust:

   ```bash
   gpg --default-key <OLD_FINGERPRINT> --sign-key <NEW_FINGERPRINT>
   ```

2. Publish a transition statement in the release notes and on the project
   website. The statement must be signed with the **old** key and announce
   the **new** fingerprint.
3. Update `docs/release.md` section 16.10 with the new fingerprint, long
   key ID, creation date, and expiration.
4. Replace `docs/keys/phpmyfaq-release-public-key.asc` with the export of
   the new key.
5. Keep signing releases with the old key for one transition release, so
   users have time to import the new key before it becomes mandatory.
6. Retire the old key after the transition release by importing its
   revocation certificate and publishing the revocation.

### 16.14.4 Emergency revocation after compromise

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
4. Generate a brand-new release key and publish the
   new fingerprint in section 16.10 with a note that all releases after the
   compromise date must be re-verified against the new key.
5. Re-sign the currently published release archives and SBOMs with the new
   key. Update `SHA256SUMS.asc` and every `<file>.asc` artifact in place.
   Keep the archives themselves byte-identical — only the detached
   signatures change.
6. File a security advisory in the repository's security advisories section
   so downstream package maintainers are notified through the normal GitHub
   security channel.

The revocation certificate can only be created while the private key is
still available. Always generate it at key-creation time and store it with
the offline backup of the primary key.

### 16.14.5 Post-rotation checklist

After any of the flows above, verify the following before cutting the next
release:

- `gpg --show-keys docs/keys/phpmyfaq-release-public-key.asc` lists the
  expected primary key, active signing subkey, and (if applicable)
  revocation status.
- Section 16.10 reflects the current fingerprint, long key ID, creation
  date, and expiration.
- A test run of `./scripts/sign-release-artifacts.sh 0.0.0-signtest` succeeds
  end-to-end, including the internal `gpg --verify` pass on every
  `.asc` file. The script needs the archives and SBOM files in
  `build/release/0.0.0-signtest/`, so run
  `./scripts/prepare-release-artifacts.sh 0.0.0-signtest` first.
- Any stored `GPG_KEY_ID` and `GPG_PASSPHRASE` values on the signing
  machine are updated to match the new key material.

## 16.15 One-command release

The whole release — packages, download.phpmyfaq.de, api.phpmyfaq.de,
www.phpmyfaq.de, and the GitHub release — is orchestrated by:

```bash
./scripts/release.sh <version>
```

### 16.15.1 Configuration

Copy `scripts/release.conf.example` to `~/.config/phpmyfaq/release.conf` and
fill in the local checkout paths of the two site repositories and the three
rsync targets. The location can be overridden with the `PMF_RELEASE_CONF`
environment variable.

### 16.15.2 Stages

The script runs six idempotent stages in fixed order:

1. `preflight` — clean working trees in all three repositories, the version
   reported by `scripts/get-version.php` matches `<version>`, a CHANGELOG
   section exists, `git pull --ff-only` of both site repositories,
   non-interactive SSH to all three targets, `gh auth status`, and a GPG
   secret key (skipped with `SKIP_GPG=1`). Nothing is published if any
   check fails.
2. `build` — `prepare-release-artifacts.sh` + `sign-release-artifacts.sh`
   (skipped when `build/release/<version>/SHA256SUMS` already exists).
3. `publish-packages` — rsync the release directory (without
   `hashes-<version>.json` and `ARTIFACTS.txt`) to download.phpmyfaq.de,
   then verify that the `info/<version>` endpoint reports the version and
   the MD5 checksum of the ZIP archive.
4. `update-api` — copy `hashes-<version>.json`, bump the stable or dev
   version constants, test, commit, push, deploy, then verify `/versions`
   and `/verify/<version>`. Deploy uses `rsync --delete`: any server-only
   file on `API_SSH_TARGET` that isn't part of this repo checkout will be
   deleted — verify the server directory before first use.
5. `update-www` — refresh `data/*.json`, insert a news draft generated from
   the CHANGELOG, **pause for manual review**, then test, build, commit,
   push, deploy, and verify the homepage. Deploy uses `rsync --delete`: any
   server-only file on `WWW_SSH_TARGET` that isn't part of the built output
   will be deleted — verify the server directory before first use.
6. `github-release` — create the GitHub release with notes from the
   CHANGELOG plus links to the verification instructions and the public
   key (`--prerelease` for development versions). Attached are both
   archives, `SHA256SUMS`, `SHA256SUMS.asc`, the `.asc` files of both
   archives, and the three SBOM files. If the release already exists,
   missing assets are uploaded.

Publishing the GitHub release also triggers `.github/workflows/docker-publish.yml`, which builds and pushes the
production Docker images.

### 16.15.3 Options

- `--from <stage>` — resume from a stage after fixing a failure; every
  failure message names the exact resume command.
- `--dry-run` — print every mutating command (build and signing scripts,
  file copies, constant patches, composer, pnpm, git, rsync, gh) instead
  of executing it. The read-only preflight checks still run; the review
  pause and the post-deploy verification are skipped.
- `--type stable|development` — override the type derived from the version
  string (a version containing `-` is treated as development).
- `--codename <name>` — the release codename used in the news draft
  (defaults to `TBD`; edit during the review pause).
- `--print-type` — print the derived release type and exit.
- `-h`, `--help` — print the usage and exit.

`PHP_BIN` overrides the PHP binary. `SKIP_GPG=1` is honoured by the
preflight and build stages (see section 16.7), but the `github-release`
stage still expects the `.asc` files.

### 16.15.4 Helpers

- `php scripts/release-changelog.php <version>` — print the CHANGELOG
  section for a version.
- `php scripts/release-news-draft.php <version> [date] [codename]
  [news-file]` — print a news entry draft, or insert it into a news file.
