# Contributing

<!-- contract:branch-naming -->
## Branch naming

```
<gh-username>/<type>[/<id>]/<slug>
```

- **`<gh-username>`** — the GitHub username of the account that owns the branch.
- **`<type>`** — one of `feat` | `fix` | `chore` | `ci` | `docs` | `refactor` | `perf` | `test`.
- **`<id>`** — *optional*: include only when a tracking issue exists; omit the whole segment otherwise. Encode the source: Linear → `linear-<issue-id>` (the full key, e.g. `linear-FIL-42`); GitHub → `gh-<number>`.
- **`<slug>`** — short, lowercase, kebab-case.

Examples:

```
<username>/feat/linear-FIL-42/add-csv-export
<username>/fix/gh-108/token-refresh-loop
<username>/chore/bump-dependencies (no issue → id segment dropped)
```

One issue per branch is the norm. Issue ↔ branch linking is driven by the PR body and commit messages citing the tracker's native issue key (e.g. a Linear key like `ABC-123`, or a GitHub `#number`). The Linear branch token carries the native key too, so Linear attaches the branch automatically; the GitHub token stays `gh-<number>` (`#` doesn't belong in a ref).
<!-- /contract:branch-naming -->

<!-- contract:commit-style -->
## Commit messages

Conventional Commits v1.0.0, distilled:

```
<type>[(scope)][!]: <subject>

[body]

[footer(s)]
```

- **`<type>`** — the same set as branch naming (that section is the authoritative list). `feat` = new capability (SemVer **MINOR**); `fix` = bug patch (SemVer **PATCH**); the rest never bump a version by themselves.
- **`(scope)`** — *optional*: a parenthesized noun naming the area touched — `feat(parser): …`.
- **`!`** — breaking change (SemVer **MAJOR**). Always mark it with `!`; when the subject alone doesn't convey the impact, add a `BREAKING CHANGE: <impact>` footer.
- **`<subject>`** — imperative mood, lowercase, no trailing period, ≤ 72 characters: it completes *"this commit will …"*.
- **body** — *optional*: prose after one blank line; the *why*, not the *what*.
- **footer(s)** — *optional*: git trailers (`Token: value`), one per line. Link work here by citing the issue's native key — `Refs: ABC-123`, `Closes #108`.

One logical change per commit.

```
feat(export): add csv export
chore: bump dependencies
```

```
fix(auth)!: reject expired tokens on refresh

Expired tokens were silently reissued, extending sessions forever.

BREAKING CHANGE: refresh now returns 401 for expired tokens
Refs: ABC-123
```
<!-- /contract:commit-style -->

<!-- contract:pr-process -->
## Pull requests

Follow the PR template: [`.github/PULL_REQUEST_TEMPLATE.md`](.github/PULL_REQUEST_TEMPLATE.md).

- **Assignee** — whoever opens the PR assigns themselves; every PR carries its author as assignee.
<!-- /contract:pr-process -->

<!-- contract:issue-process -->
## Issues

File through the repo's issue forms: **Bug report** for defects, **Task** for everything else (feature, improvement, chore, docs, research) — the forms are the door.

One concern per issue.
<!-- /contract:issue-process -->

<!-- contract:pr-classification-wp -->
## PR classification

Every PR must be **classified** before it can merge: it either carries a **version
milestone** (it produces a user-facing changelog entry) or the **`skip-changelog`**
label (it does not).
<!-- /contract:pr-classification-wp -->

<!-- contract:docblock-provenance-wp -->
## Docblock provenance (`@since` / `@version`)

`@since <version>` (phpDoc) records when code was introduced and when it notably changed.
Every tagged-tier file declares at least one `@since`. Per-member completeness and
change-line accuracy are review-enforced.

**PHP — one `@since` per construct:**

- The **type** (`class`/`interface`/`trait`/`enum`), on its own docblock — never a separate
  file header.
- **Every member:** each method (public, protected, **and** private, including abstract and
  interface methods), declared property, constant, and enum case gets its own tag — never
  one shared tag for a group.
- **Parameters get none**, promoted constructor properties included (they are parameters).
- Placement: after any description, before `@param`/`@return`/`@var`, and **above** any
  attribute line; keep existing CONTRACT/WHY/GOTCHA prose.

**JS modules, view templates, and the two root bootstrap files** (`woocommerce-parcelas.php`,
`uninstall.php`) carry a single **file-level** `@since` only — no per-export or per-member tags.

**Format:** bare semver, one space — `@since 1.0.1`.

**Changes = stacked `@since`, newest-first** (phpDoc reads repeats as a changelog). Add a
line above the introduction line only when a construct's **own** body or signature notably
changes — not when a helper it calls changes, and not for trivial edits (rename, formatting,
comments). Each change line carries a short description; the introduction line carries none.

```php
/**
 * @since 1.0.1 <what changed>
 * @since 1.0.0
 */
```

**Version value = the PR's GitHub milestone** (see
[**PR classification**](#pr-classification)): a new construct, or a notable own-code change to one, is stamped with that
milestone; a `skip-changelog` PR with no milestone uses the release it first ships in.

**`@version`** is reserved for a genuinely independently-versioned file (e.g. a vendored file
tracking its upstream version) — used **nowhere** today.

**Out of scope:** `tests/` and build configs (`vite.config.js`, `playwright.config.js`).
<!-- /contract:docblock-provenance-wp -->
