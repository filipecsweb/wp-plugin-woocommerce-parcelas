---
name: file-issue
description: File, create, or report an issue for this repo, on GitHub or Linear. Use for every issue creation here, even quick ones — the CLI cannot render GitHub issue forms and the API ignores the blank-issue policy; this skill encodes how to honor them anyway.
---

Norms live in `CONTRIBUTING.md` (Issues) and `.github/ISSUE_TEMPLATE/` — read both now; this skill adds mechanics only.

GitHub:
1. Pick the applicable form in `.github/ISSUE_TEMPLATE/` — each form states its purpose.
2. Read the form at creation time; replicate its fields as `### <Label>` headings in the issue body, honoring each field's required/"N/A" semantics as written.

Linear: use native fields (title, description, labels, priority).

Verify the created issue — every required field present and non-empty — and report what you verified.
