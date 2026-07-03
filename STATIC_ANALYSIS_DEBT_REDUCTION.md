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

**Trait & interface typing (180 findings) — spiked; recommend leaving baselined.**
A spike (2026-07-01) evaluated every remediation and found none is worth the
risk/effort. Details:

- **mago has no trait-context docblock mechanism.** Tested on
  `ConfigurationMethodsTrait` (72 findings): `@mixin Configuration` and
  `@property` are both ignored — mago analyzes traits standalone. The only tool
  is the inline `@mago-ignore analysis:<rule>` pragma.
- **A blanket pragma is *worse* than the baseline.** The baseline suppresses each
  finding *by count*, so a genuinely new `non-existent-*` (a real typo) in the
  trait still surfaces. A trait-level `@mago-ignore` suppresses the rule for the
  whole trait forever, hiding future real bugs. Net regression in bug-detection.
- **The only true fix for traits is a structural move** (relocate host property
  declarations into the 1:1 trait) — touches central classes (`Configuration`,
  `CurrentUser`) for zero runtime benefit.
- **"Missing interface methods" can't just be added to the interface.**
  `PermissionInterface` (50 findings) declares only `hasPermission()`, but the
  called group methods (`addGroup`, `getGroupName`, `getAllGroupsOptions`,
  `grantGroupRight`, `deleteGroup`, …) exist **only** on `MediumPermission`, not
  `BasicPermission`. Adding them to the interface would force `BasicPermission`
  to implement group methods it deliberately lacks. The correct fix is a new
  `GroupPermissionInterface` + retyped call sites — a design change to the
  **security-sensitive** permission subsystem. Same shape for
  `AuthDriverInterface` and the `Auth` container.
- **External-lib interfaces** (`SessionInterface`, `Http\Promise\Promise`) can't
  be fixed from here at all.

**Conclusion:** the baseline already suppresses these false positives precisely
and safely (CI green, no real bugs). Clearing them means either risky refactors
of central/security code or a net loss in bug-detection. **Leave baselined** and
spend effort on Phase 3, where fixes are additive and genuinely improve safety.
Revisit the permission/auth interface design only if it's being reworked for
other reasons.

### Phase 3 — Type the request boundary (the big lever, multi-week, file-by-file)
- Order: `Controller/Administration/Api` → other `Controller/*` → `Database` → `Faq`.
- Introduce typed accessors for request input and typed DB-row shapes so `mixed`
  collapses at the source rather than fixing 4,700 symptoms individually.
- One PR per file / small group; regenerate baseline per PR for measurable progress.
- This phase accounts for the bulk (~4,700) of the ledger.

**Proof-of-pattern slice (2026-07-01): `CommentController::delete`.** Established
`AbstractController::getJsonObject(Request): \stdClass` — decodes the JSON body
with `JSON_THROW_ON_ERROR` and validates it is an object, so callers stop leaking
`mixed` from `json_decode()` (also a real robustness fix: the old code would fatal
on malformed input at `$data->data->…`). Result: the method's error-level findings
(`mixed-property-access`, `mixed-argument`, `possible-method-access-on-null`,
`less-specific-nested-argument-type`) were eliminated; ~10 findings cleared,
baseline 3301 → 3294, all 43 CommentController tests green.

**What the slice taught about Phase 3 economics — read before scaling:**
- `getJsonObject()` fixes property-*access* errors, but `mixed` *values* still flow
  from JSON leaves. Getting a method to literal zero needs per-field casting, and
  some casts (`(array) $mixed`) mago itself rejects (`invalid-type-cast`). Chasing
  the last `mixed-assignment` *warnings* is low-value; target the *errors*.
- Use native `filter_var($x, FILTER_VALIDATE_INT)` (returns `int|false`) over
  `Filter::filterVar()` when you need a typed scalar — mago narrows it cleanly.
- The single inherent `mixed` boundary (the `json_decode` call) is documented with
  `/* @mago-expect analysis:mixed-assignment - … */` rather than baselined.
- At ~10 findings per method by hand, the ~4,700 `mixed-*` cluster is genuinely
  multi-month unless a boundary-typing multiplier is applied first — see below.

**`Filter::filterVar()` conditional-return spike (2026-07-03) — CONFIRMED viable,
high value.** `Filter::filterVar(): mixed` is called ~470× with a constant filter
(161× `FILTER_VALIDATE_INT`, 281× `FILTER_SANITIZE_SPECIAL_CHARS`, 19× `BOOLEAN`),
so its `mixed` return is a primary multiplier of `mixed-*`. Findings:
- **mago does NOT honor named-constant conditional types** — `@return ($filter is
  FILTER_VALIDATE_INT ? …)` makes mago parse `FILTER_VALIDATE_INT` as a *class
  name* (`non-existent-class-like`).
- **mago DOES honor literal-int conditional types.** With
  `@return ($filter is 515 ? string|null : ($filter is 257 ? int|null : ($filter
  is 258 ? bool|null : mixed)))` (515/257/258 = the SANITIZE_SPECIAL_CHARS /
  VALIDATE_INT / VALIDATE_BOOLEAN values), a single 1-line annotation moved the
  totals: `mixed-argument` 1224→842, `mixed-assignment` 1436→1064,
  **total 7076→6591 (−485)**.
- The trade-off is a **feature, not a cost**: it surfaces ~377 findings the `mixed`
  type was masking, and they are *higher-signal* than what they replace — 261
  `possibly-null-argument` (real: `filterVar` returns `null` on invalid input, so
  passing it to an `int` param unguarded is a latent bug) and 14
  `impossible-type-comparison` that expose **dead/wrong guards** (e.g.
  `VotingController.php:79` checks `$vote === false`, but the value is `int|null`
  and can never be `false`).

**Rollout — DONE (2026-07-03).**
1. Added the conditional return type to `Filter::filterVar()`, refined to
   `@template TDefault` + `@return ($filter is 515 ? string|TDefault : ($filter is
   257 ? int|TDefault : ($filter is 258 ? bool|TDefault : mixed)))`. The `TDefault`
   template models the `$default` argument, so default-providing call sites
   (e.g. `Filter::filterVar($x, …, [])`) type correctly instead of over-reporting
   null — this alone removed ~92 false positives vs. the naive `|null` form.
   Documented as an interim measure linking mago #1117; migrate to native
   `filter_var()` when that inference matures.
2. Fixed the real dead/incorrect guards the new type exposed (~18): all
   `impossible-type-comparison` (`=== false` on `int|null` → `=== null`) across
   Bookmark/Voting/Attachment/News/Pdf/Setup/User controllers, plus a redundant
   ternary (`ChatSseController`), an always-true guard (`Tracking`), and an
   `array_filter` predicate returning `int|null` instead of `bool` (`TagsHelper`).
3. Regenerated the baseline for the remainder.

**Net: total findings 7076 → 6494 (−582); baseline 3294 → 3128 entries (−166).**
The ~270 findings the change surfaced (225 `possibly-null-argument`, 24
`possibly-null-operand`, 15 `possibly-invalid-argument`, misc) are **baselined as
Phase 4 work** — they are the *precise* form of the null-safety debt that was
previously hidden inside `mixed-*`, not new risk. None are guaranteed-null; the
null path is generally reachable only via malformed requests (route `\d+`
requirements guard the happy path). Fixing them (guards / non-null defaults) is
Phase 4.

**Discovered pre-existing bug (out of scope, flagged for follow-up):**
`UserController::updateUserRights` does `Filter::filterVar($data->userRights,
FILTER_SANITIZE_SPECIAL_CHARS, [])` — `filter_var()` on an array returns `false`,
so this always yields `[]`; the subsequent `is_array()` normalisation then keeps
`[]`, meaning submitted rights may never be applied. Needs its own investigation
with tests (security-sensitive); not touched here.

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

---

## Current state & forward plan (updated 2026-07-03)

`composer analyze:all` now reports **6,132 issues** (was 7,198), baseline **2,889
entries**. Done so far: `Filter::filterVar` conditional return type (−485), the
full controller null-safety sweep (~50 files), and two core classes. CI is green
via the baseline; every step kept the full test suite passing.

### What the remaining 6,132 are

| Cluster | ~Count | Root cause |
|---|---:|---|
| `mixed-*` (assignment 1060, argument 839, property-access 512, operand 464, array-access 272, method-access 253, return 192, coercion 59) | **~3,650** | **Untyped database rows.** `DatabaseDriver::fetchObject(): mixed`, `query(): mixed`, `fetchRow(): mixed`, `fetchArray(): array\|false\|null` — every row object/array is untyped, so all downstream access is `mixed`. Plus `$faq->faqRecord[...]`, config values, and residual request input. |
| `ambiguous-object-property-access` | 180 | 177 are "generic `object`" — the objects returned by `fetchObject()`. |
| null-safety (`possible-method-access-on-null` 347, `possibly-null-argument` 244, `possibly-null-operand` 124, `nullable-return` 56) | **~770** | Missing guards; nullable returns not declared. |
| `non-existent-method`/`-property` | 180 | Traits analyzed standalone + base-vs-interface typing. **Established false positives — leave baselined.** |
| return-type accuracy (`invalid-return` 96, `less-specific-return`/`-argument` 123, `mixed-property-type-coercion` 59) | ~280 | Return/param annotations wider or narrower than reality. |
| `possibly-false-argument` 75, `possibly-invalid-argument` 173, `invalid-iterator` 51, misc | ~300 | Assorted. |

### The lever: type the database-row boundary (Phase 5)

`mixed-*` is ~60% of the debt and, like the `filterVar` cluster, comes from a
**small number of untyped sources**, not thousands of independent fixes. The
highest-ROI move is to type the `DatabaseDriver` fetch API:

1. `fetchObject(mixed $result): ?\stdClass` (instead of `: mixed`) — resolves the
   180 `ambiguous-object-property-access` and a large share of the 512
   `mixed-property-access` at once (row property reads become `stdClass` access;
   leaf values stay `mixed` but the *access* is valid).
2. `fetchArray(): ?array<string, scalar|null>` and `fetchRow()`/`fetchAll()`
   shapes — collapses `mixed-array-access` / `mixed-assignment` on row arrays.
3. For hot tables (faqdata, faquser, faqcategories, faqconfig) consider a typed
   row value-object / `@return` array shape so leaf values are typed too.

Expect this to surface **more precise** null-safety findings (as the `filterVar`
change did) — that is a feature. Do it **incrementally, one driver/consumer at a
time**, regenerating the baseline per PR.

### Forward phases

- **Phase 5 — DB-row typing (the big lever).** Tighten `DatabaseDriver` fetch
  return types; migrate hot consumers (`Faq`, `User`, `Search`, `Category`,
  `Setup`). Biggest single reduction; do it carefully — the DB layer is core.
- **Phase 6 — null-safety.** Work the `possible-method-access-on-null` (347) and
  remaining `possibly-null-*` with guards / `?->` / accurate `?T` returns
  (partly surfaced by Phase 5).
- **Phase 7 — return-type accuracy.** `invalid-return`, `nullable-return`,
  `less-specific-*`, `mixed-property-type-coercion`.
- **Leave baselined:** trait-standalone + base-vs-interface `non-existent-*`
  (~180) and the `sqlsrv` extension findings — analyzer-modeling gaps, not bugs.

### Guardrails (learned this session)

- **The unit suite does NOT build the DI container or run the app.** Phase 1's
  "remove unused dependency" edits passed all 6,586 unit tests and mago, yet
  broke `services.php` DI wiring — which only surfaced in the nightly **e2e**
  (empty seed) and was fixed in `fix(di): correct stale service arguments`. Any
  change that alters a constructor signature or control flow (null→default,
  guards) MUST be validated against: the full unit suite **+** a DI
  container-instantiation smoke test **+** the e2e seed/browse flow
  (`bin/e2e local`).
- **Add a container smoke test** that iterates `services.php` definitions and
  `->get()`s each service — it would have caught the DI regression in CI.
- Keep using the baseline shrink workflow (`composer analyze:baseline`); one
  file / small group per PR so each step is reviewable and revertible.
- Watch behavior, not just types: a `null → '' / 0` default can change a control
  branch that keyed on `=== null`. Prefer guards that mirror the method's
  existing invalid-input handling over blanket defaults on public page
  controllers.

### Sizing

`mixed-*` (~3,650) is genuinely a multi-week effort even with the DB-layer lever,
because each typed source cascades but also surfaces follow-on findings to
triage. Realistic target: Phase 5 first (largest, highest-leverage), then 6 and 7.
Trait/interface and extension findings stay baselined indefinitely.
