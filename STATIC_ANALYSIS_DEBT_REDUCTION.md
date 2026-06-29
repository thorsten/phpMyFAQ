# Static Analysis Debt Reduction Plan (Mago `analyze`)

Plan to gradually pay down the static-analysis tech debt currently suppressed by
`mago-analyze-baseline.toml`. CI stays green at every step; we only ever shrink
the baseline, never grow it.

_Generated 2026-06-29 against mago 1.42.0._

## Background

`composer analyze:all` (raw `mago analyze`, no baseline) reports **7,198 issues**
(4,792 errors, 2,235 warnings, 171 help). CI is nonetheless green because
`.github/workflows/build.yml` runs analyze **with** the baseline:

```
./phpmyfaq/src/libs/bin/mago analyze --baseline=mago-analyze-baseline.toml --reporting-format=github
```

The baseline (`mago-analyze-baseline.toml`, 3,377 entries — each with a `count`
field, summing to the 7,198 occurrences) records every existing issue as accepted
debt. CI only fails on **new** issues introduced beyond the baseline.

"Reduce the errors and warnings" therefore means: **fix real issues and remove the
corresponding entries from the baseline**, so the ledger shrinks toward empty.

### Composer scripts (Phase 0 — done)

- `composer analyze` — baseline-aware, mirrors CI (green when no new issues).
- `composer analyze:all` — raw run, shows the full 7,198-issue debt.
- `composer analyze:baseline` — regenerate the baseline after fixing code:
  removes outdated entries (fixed issues) without hiding new ones, keeps a `.bkp`.

The lint side has the analogous `mago-lint-baseline.toml`; the same approach
applies to `composer lint` if/when we choose to mirror it.

## The fix → shrink workflow

For every batch of fixes:

1. Fix the code in a small, reviewable PR (one file or a small group).
2. `composer analyze:baseline` — drops the now-fixed entries from the baseline.
3. Commit the shrunken `mago-analyze-baseline.toml` alongside the code change.
4. CI (`--baseline`) stays green; the diff on the baseline file shows progress.

Guardrails to consider adding to CI:

- `--verify-baseline` / `--fail-on-out-of-sync-baseline` so the ledger cannot
  silently drift out of sync with the code.

## What the 7,198 issues are (by root cause)

Overwhelmingly **type-safety** findings. Counts are occurrences from `analyze:all`.

| Cluster | ~Count | Root cause | Fix pattern |
|---|---:|---|---|
| `mixed-*` — argument (1,245), assignment (1,440), property-access (527), operand (387), array-access (272), method-access (253), return (195) | **~4,700** | Untyped data from request input (`Filter::filterVar`, `json_decode` objects), DB rows, and untyped properties | Type the boundaries: typed request accessors/DTOs, typed DB-row shapes, annotate properties & returns |
| Null-safety — `possible-method-access-on-null` (348), `possibly-null-argument` (253), `possibly-null-operand` (155), `nullable-return-statement` (56), `possibly-null-property-access` (20) | **~900** | Missing null guards; nullable returns not declared | Guard clauses, `?->`, accurate `?T` return types |
| `non-existent-method` (100), `-property` (80), `-constant` (76), `-function` (16) | **~270** | **Likely real bugs** or missing type info on dynamic objects | Inspect each: fix genuine defects; annotate magic `__get`/`__call` with `@property`/`@method` |
| `less-specific-*` — return, nested-argument, argument, nested-return | **~225** | Generics/array shapes wider than declared | Tighten `@param`/`@return` shapes & generics |
| Redundant / dead / unused (help) — `redundant-comparison/-logical-operation/-null-coalesce/-cast`, `unused-property` | **~170** | Cleanups | Mostly auto-fixable |

### Hot spots by directory

| Directory | ~Issues |
|---|---:|
| `Controller/Administration/Api` | 1,120 |
| `Controller/Administration` | 377 |
| `Controller/Frontend` | 318 |
| `Database` | 298 |
| `Faq` | 255 |
| `Controller/Frontend/Api` | 252 |
| `Setup` | 229 |
| `Administration`, `User`, `Auth`, `Helper` | ~200 each |

The controllers dominate because request input is `mixed` at its source — typing a
handful of input boundaries cascades into removing dozens of downstream `mixed-*`
errors each.

## Phased plan

### Phase 0 — Make debt visible & safe to chip at — DONE
- Baseline-aware `composer analyze` + `analyze:all` + `analyze:baseline` scripts.
- This document records the fix → shrink workflow.
- TODO (optional): add `--verify-baseline` to CI.

### Phase 1 — Free wins (in progress)

> **Important finding: do NOT trust `mago analyze --fix` blindly here.**
> On mago 1.42.0 the "8 safe fixes" included changes that **break the code**:
> `Translation.php` got `$x = &self::$translation?->loadedLanguages;` — taking a
> reference to a nullsafe chain is a **fatal parse error** ("Cannot take reference
> of a nullsafe chain", confirmed via `php -l`). Others removed `(string)` casts
> feeding directly into SQL `escape()` on `mixed` data (reducing safety) and added
> pointless `?->` to inner index expressions whose outer access stayed `->`.
> All 8 were reverted. **Every auto-fix must be reviewed individually with `php -l`
> + the full test suite; the "safe" classification is not reliable.**

Hand-reviewed cleanups instead. **Done (commit-ready, all 6586 tests green, lint clean):**

| Change | File(s) | Baseline entries removed |
|---|---|---|
| De-promote unused `$tableName` property (param still used) | `Configuration/ConfigurationRepository.php` | 1 |
| Remove dead injected `Ldap` dep + redundant constructor | `Controller/Administration/Api/LdapController.php` | 1 |
| Remove dead injected `Comments` dep + unused import | `Controller/Administration/NewsController.php` | 1 |
| Remove dead injected `Configuration` dep + import; update call site + test | `Service/McpServer/McpSdkRuntime.php`, `PhpMyFaqMcpServer.php`, `McpSdkRuntimeTest.php` | 1 |

Baseline: 3377 → 3373 entries. Verified each property was genuinely unused
(no `$this->` access, no trait/reflection usage, call sites/tests updated).

**Deferred (need deeper review before touching):**
- `RouteCollectionBuilder::$configuration` — unused, but removal ripples into
  `Kernel::loadRoutes()` (the `$configuration` fetch + closure capture become
  dead) and `RouteCollectionBuilderTest`; routing is critical-path.
- `Configuration.php` `$config` / `$logger` / `$pluginManager` — central class;
  confirm no dynamic/trait/reflection access before removing.

**Remaining Phase 1 candidates:** the `redundant-*` help cluster — but treat each
with suspicion: a "redundant condition" often reflects mago's mistaken type
inference, and removing it can drop a real defensive check. Review case-by-case.

### Phase 2 — `non-existent-*` triage (in progress)

> **Finding: the ~270 `non-existent-*` are NOT real bugs.** Triage showed they are
> all **analyzer-modeling gaps** — missing definition files, an unstubbed
> extension, and traits analyzed in isolation. The code is correct at runtime.
> The "likely real bugs" assumption in the original plan was wrong.

Root causes and remediation:

| Sub-category | Count | Root cause | Action |
|---|---:|---|---|
| `non-existent-function` | 16 | All `sqlsrv_*` — mago ships no stubs for the SQL Server extension | **Left baselined** (ignored for now, by request — a `sqlsrv` stub was prototyped then reverted; revisit if/when the SQL Server driver gets attention) |
| `non-existent-constant` | 76 → 1 | `PMF_*`/`AAD_OAUTH_*` defined in files outside mago's analysis path | **Done:** added the 3 tracked constant files + `phpmyfaq/src/stubs/azure.php` to `includes`. 1 left (`PMF_MULTI_INSTANCE_CONFIG_DIR`, a runtime multisite `define()` behind a `defined()` guard — left baselined) |
| `non-existent-method` | 100 | Traits analyzed standalone (member lives on host class), and containers typed as a base class (`Auth`) when methods live on the driver interface (`AuthDriverInterface`) | **Deferred** — needs trait annotations / tighter type hints (see below) |
| `non-existent-property` | 80 | Same trait-standalone gap (e.g. `ConfigurationMethodsTrait::$config` is `Configuration::$config`) | **Deferred** — trait annotations |

**Phase 2 results so far:** 7194 → 7086 issues (**108 false positives cleared**),
baseline 3373 → 3301 entries. **Zero logic changes, zero runtime risk** — only
`mago.toml` config and one analyzer-only stub file (`src/stubs/azure.php`, never
autoloaded; PSR-4 maps `phpMyFAQ\` to `phpmyfaq/src/phpMyFAQ` only, so
`src/stubs/*` is invisible at runtime). The `sqlsrv_*` findings (18) are left
baselined by request. CI green throughout.

> **This validated the Phase 1 decision to keep `Configuration::$config`.** It was
> flagged `unused-property` only because `ConfigurationMethodsTrait` (which reads
> `$this->config`) is analyzed in isolation — the property is very much used.

**Deferred: trait & interface typing (180 findings).** This is the real remaining
work and needs care, not a stub:
- **Traits** (`ConfigurationMethodsTrait`, `CurrentUserSessionLookupTrait`,
  `CurrentUserAccountStateTrait`, …): tell the analyzer what the host provides —
  e.g. a `@phpstan-require-extends`/`@phpstan-require-implements` bound or
  `@property`/`@method` docblocks on the trait. Verify mago honors the chosen
  mechanism on a single trait before rolling out.
- **Base-vs-interface typing**: e.g. `CurrentUser::$authContainer` is typed
  `Auth` but the code calls `isValidLogin()`/`checkCredentials()` which live on
  `AuthDriverInterface`. Tighten the property/return type to the interface (or a
  union). A genuine type-precision improvement, but touches auth-critical code —
  do it deliberately with the full suite as the gate.

### Phase 3 — Type the request boundary (the big lever, multi-week, file-by-file)
- Order: `Controller/Administration/Api` → other `Controller/*` → `Database` → `Faq`.
- Introduce typed accessors for request input and typed DB-row shapes so `mixed`
  collapses at the source rather than fixing 4,700 symptoms individually.
- One PR per file / small group; regenerate baseline per PR for measurable progress.
- This phase accounts for the bulk (~4,700) of the ledger.

### Phase 4 — Null-safety & specificity (ongoing)
- Add guard clauses / nullsafe operators and accurate nullable return types (~900).
- Tighten `less-specific-*` array shapes and generics (~225).

### Phase 5 — Ratchet
- As each category reaches zero, optionally promote it to a hard gate so it cannot
  regress, and continue shrinking toward a baseline-free state.

## Sequencing rationale

Phases 1–2 are low-risk, high-signal, and surface real bugs quickly. Phase 3 is
where most of the volume actually lives, because it is a small number of `mixed`
*sources*, not 4,700 independent fixes — typing each boundary pays down many
downstream findings at once.
