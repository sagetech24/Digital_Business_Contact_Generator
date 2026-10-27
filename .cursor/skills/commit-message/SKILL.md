---
name: commit-message
description: >-
  Digital_Business_Contact_Generator repo only: group agent changes by conversation
  response, then git add, commit, and push each response's work in order. Never
  commit outside Digital_Business_Contact_Generator. Use when the user runs
  /commit-message, or asks to commit, push, summarize changes, write a commit
  message, or prepare a PR description for this plugin.
disable-model-invocation: true
---

# Commit Message → Commit → Push (per agent response)

**Repo scope: `Digital_Business_Contact_Generator/` only.** This skill never commits to any other git repo or path (not the parent `BSS/` workspace, not other plugins/themes, not other subprojects). Only files inside `Digital_Business_Contact_Generator/` may be staged, committed, or pushed.

Repo root: `wp-content/plugins/Digital_Business_Contact_Generator/` (run **all** git commands with
`git -C /Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator` or `working_directory` set there).

For `Digital_Business_Contact_Generator/`: analyze **uncommitted changes grouped by agent response**, then for each response **add → commit → push** before moving to the next.

## Default behavior (`/commit-message`)

Unless the user says **message only** or **PR description only**, do **not** stop at drafting text. Execute:

1. Inspect current git changes
2. Map changes to **agent responses** in this conversation (oldest response first)
3. For each response that introduced changes, in order: **stage → commit → push**
4. Report each commit hash and the final remote status

**One commit per agent response.** Do not squash multiple responses into one commit unless the user explicitly asks.

## Grouping by agent response

An **agent response** is one assistant turn where code was changed (a distinct task, fix, or feature slice). Use conversation history as the source of truth:

1. **Read the conversation** — walk assistant turns from oldest to newest. Each turn that edited files is a candidate commit.
2. **Match files to responses** — assign each changed file (or path) to the response that introduced or last meaningfully changed it. Use the response summary, stated goal, and described file edits.
3. **Handle overlap** — if a later response only touched files from an earlier response, attribute those edits to the later response (latest wins for that file in that turn).
4. **Untracked / ambiguous files** — if a file cannot be tied to a single response, group it with the response whose theme best fits, or the most recent response that likely created it.

If only **one** response made changes in this session, make a single commit and push once.

If **no** conversation history is available (e.g. changes made outside the chat), fall back to splitting by logical theme from the diff (same as before: one theme = one commit).

## Workflow

### 1. Gather changes (Digital_Business_Contact_Generator only)

Run in parallel **inside `Digital_Business_Contact_Generator/`**:

```bash
git -C /Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator status --short
git -C /Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator diff --stat
git -C /Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator diff
git -C /Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator diff --cached --stat
git -C /Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator diff --cached
git -C /Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator log -5 --oneline
git -C /Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator status -sb
```

Include untracked files under `Digital_Business_Contact_Generator/` that belong to the agent's work. **Ignore** changes outside this repo — do not stage, commit, push, or mention them as follow-up work. Never stage secrets (`.env`, credentials, tokens).

### 2. Build the per-response commit queue

Produce an ordered queue before running any git write commands. Example:

| # | Response theme (from conversation) | Files |
|---|--------------------------------------|-------|
| 1 | Add IBEX org + isolated card template | `template/single-person_details_IBEX.php`, `css/...` |
| 2 | Split shared CSS into per-template files | `css/*.css`, `template/*.php` |
| 3 | Document IBEX in README | `README.md` |

Rules:

- **Order = conversation order** (first response committed first).
- **One response → one commit** — message summarizes that response's work only.
- **Do not mix files from different responses** in the same commit when they can be separated at path level.
- If one file was edited across multiple responses and cannot be split without `git add -p`, put it in the **last** response that changed it, or use one broader commit for that file only.

### 3. For each response commit (repeat until queue is empty)

**a. Stage only that response's files**

```bash
git add -- path/to/file1 path/to/file2
```

Do not stage files belonging to later responses.

**b. Write the commit message from that response's summary**

Derive `type(scope): subject` and bullets from what that specific response accomplished — not from the whole session.

```bash
git commit -m "$(cat <<'EOF'
type(scope): concise imperative summary (≤ 72 chars)

- Bullet explaining what this response changed and why
- Another bullet if needed

EOF
)"
```

**c. Push to the tracked remote branch**

```bash
git push -u origin HEAD
```

Use `required_permissions: ["all"]` (or equivalent) so push can authenticate. Do **not** force-push (`--force`, `--force-with-lease`) unless the user explicitly asks.

**d. Verify, then continue to the next response**

```bash
git status -sb
```

Finish push of commit N before staging commit N+1.

### 4. Finish

Summarize for the user:

- Each response theme → commit subject + short hash
- That each was pushed (or any push failure)
- Remaining unstaged/untracked files **inside `Digital_Business_Contact_Generator/`**, if any

Do not suggest committing files outside `Digital_Business_Contact_Generator/`.

## Commit Format

```
type(scope): concise imperative summary (≤ 72 chars)

- Bullet explaining what changed and why
- Another bullet if needed
```

### Type Table

| Type | When to use |
|------|-------------|
| `feat` | New feature or capability |
| `fix` | Bug fix |
| `refactor` | Code restructuring, no behavior change |
| `style` | Formatting, whitespace, missing semicolons |
| `perf` | Performance improvement |
| `chore` | Build, config, dependency updates |
| `docs` | Documentation only |
| `test` | Adding or updating tests |

### Scope Map (derive from changed file paths)

| Path pattern | Scope |
|---|---|
| `Digital_Business_Contact_Generator.php` | `core` |
| `includes/class-bss-business-contact-organization.php` | `organization` |
| `includes/class-bss-business-contact-card-template.php` | `template` |
| `includes/class-bss-business-contact-customizer.php`, `admin/customizer/**` | `customizer` |
| `includes/class-bss-business-contact-import-export.php`, `admin/import-export-page.php` | `import-export` |
| `template/**` | `templates` |
| `css/**` | `styles` |
| `assets/**` | `assets` |
| `README.md`, `docs/**` | `docs` |
| `.cursor/**` | `skills` |
| Multiple scopes | pick the dominant one, or use the parent feature name |

## Safety rules

- **Digital_Business_Contact_Generator only** — never run `git add`, `commit`, or `push` outside `/Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator`.
- Never update git config.
- Never use interactive git (`-i`, `git add -p` when it requires a TTY).
- Never skip hooks (`--no-verify`) unless the user explicitly asks.
- Never amend a commit that was already pushed, unless the user explicitly asks and conditions for amend are met.
- Never commit `.env`, credentials, or API tokens; warn and skip them.
- **Sequential only:** finish push of response N before starting response N+1.
- If push fails (auth/network), stop the queue, report the error, and leave remaining changes uncommitted or already committed locally as appropriate.
- If commit fails (hook), fix the issue and create a **new** commit; do not amend a failed commit.

## Message-only / PR-only modes

- **Message only** — draft one Conventional Commit per agent response; do not `git add`, `commit`, or `push`.
- **PR description** — also produce the PR section below; do not open a PR unless asked.

## PR Description Format

When asked for a PR description, use:

```markdown
## Summary
Brief 1–2 sentence overview of the change.

## Changes
- Bullet per agent response / logical change

## Notes
- Migration steps, breaking changes, or review hints (omit section if none)
```

## Examples

**Single response → single commit:**
```
feat(templates): add IBEX card layout with isolated stylesheet

- Register ibex organisation and /ibex/{slug} permalinks
- Add single-person_details_IBEX.php/.css matching brand sample
- Load per-template CSS so IBEX styles do not affect other orgs
```

**Multi-response session (what `/commit-message` should do):**

| Response | Action |
|----------|--------|
| Response 1 — IBEX template | `git add` → commit `feat(templates): add IBEX card with isolated CSS` → `git push` |
| Response 2 — per-template CSS split | `git add` → commit `refactor(styles): give each card template its own CSS file` → `git push` |
| Response 3 — README | `git add` → commit `docs: document IBEX org and per-template stylesheets` → `git push` |

Each row is a full add → commit → push cycle before the next response is processed.
