---
name: create-pr
description: Create, open, or submit a pull request in this repo. Use for every PR creation here, even routine ones — gh silently skips the repo's PR template and assignee rule; this skill encodes the CLI mechanics and post-create verification.
---

Norms live in `CONTRIBUTING.md` and `.github/PULL_REQUEST_TEMPLATE.md` — read both now; this skill adds mechanics only.

1. Compose `--body` from `.github/PULL_REQUEST_TEMPLATE.md`, following its inline instructions — `gh` never applies the template itself.
2. Base on `main` unless told otherwise.
3. Set `--assignee` per CONTRIBUTING's self-assign rule (`@me` when you are the author).
4. Verify with `gh pr view`: every template section present, assignee set, issue links resolve. Report what you verified.
