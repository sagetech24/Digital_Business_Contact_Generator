---
name: summarize-commit-push-message
description: >-
  Summarize uncommitted work in Digital_Business_Contact_Generator, write
  Conventional Commit messages, then git add / commit / push — only inside this
  plugin. Use when the user runs /summarize-commit-push-message, or asks to summarize changes,
  create a commit message, commit, or push for this plugin.
disable-model-invocation: true
---
# Summarize → Commit → Push (plugin-scoped)

## Hard scope (non-negotiable)

This skill’s **parent directory** is the only git scope:

```
/Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator/
```

That folder is the git repo root. Resolve it as the directory that contains this skill’s `.cursor/skills/` tree (two levels up from this `SKILL.md`).

**Never:**

- Inspect, stage, commit, or push paths **above** that parent (`plugins/`, `wp-content/`, `BSS/`, other plugins, themes, `registration-manager/`, etc.)
- Run `git` without `-C` pointing at that parent (or an equivalent cwd locked inside it)
- Mention outside-repo dirty files as work to commit

**Always:**

- Run every git command with  
  `git -C /Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator …`  
  or `working_directory` set to that path
- Treat only relative paths under that parent as valid stage targets

## Default behavior

When the user invokes this skill (or says e.g. “summarize the change and create a commit message”), **do not stop at draft text** unless they say **message only** or **PR description only**.

Execute:

1. Inspect git status/diff **inside the parent only**
2. Summarize changes for the user
3. Group by **agent response** (oldest first); one commit per response
4. For each group: **stage → commit → push**
5. Report hashes + push result

## Grouping by agent response

An **agent response** = one assistant turn that changed files.

1. Walk conversation turns oldest → newest
2. Assign each changed file under the parent to the response that last meaningfully edited it
3. If a file was edited across turns and cannot be split without interactive `git add -p`, attribute it to the **latest** response that changed it (or one broader commit for that file)
4. No conversation history → split by logical theme from the diff (one theme = one commit)

## Workflow

### 1. Gather (parent only)

```bash
REPO="/Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator"
git -C "$REPO" status --short
git -C "$REPO" status -sb
git -C "$REPO" diff --stat
git -C "$REPO" diff
git -C "$REPO" diff --cached --stat
git -C "$REPO" diff --cached
git -C "$REPO" log -5 --oneline
```

Include untracked files **under `$REPO` only**. Skip secrets (`.env`, credentials, tokens).

### 2. Queue commits

| # | Response theme | Files (relative to parent) |
|---|----------------|----------------------------|
| 1 | … | `template/…`, `css/…` |

- Order = conversation order
- Do not mix separable response file sets in one commit

### 3. Per response: add → commit → push

```bash
REPO="/Applications/MAMP/htdocs/BSS/wp-content/plugins/Digital_Business_Contact_Generator"
git -C "$REPO" add -- path/relative/to/parent1 path/relative/to/parent2
git -C "$REPO" commit -m "$(cat <<'EOF'
type(scope): concise imperative summary (≤ 72 chars)

- Bullet: what changed and why
- Another bullet if needed

EOF
)"
git -C "$REPO" push -u origin HEAD
git -C "$REPO" status -sb
```

- Use full permissions for push auth
- **No force-push** unless the user explicitly asks
- Finish push of commit N before staging N+1
- If push fails, stop the queue and report
- If commit fails on a hook, fix and create a **new** commit (do not amend a failed commit)

### 4. Finish report

- Summary of the change(s)
- Each theme → subject + short hash + pushed/failed
- Any remaining dirty files **inside the parent only**

## Commit format

```
type(scope): concise imperative summary (≤ 72 chars)

- Bullet explaining what changed and why
```

| Type | When |
|------|------|
| `feat` | New feature |
| `fix` | Bug fix |
| `refactor` | Restructure, no behavior change |
| `style` | Formatting only |
| `perf` | Performance |
| `chore` | Build/config/deps |
| `docs` | Docs only |
| `test` | Tests |

| Path under parent | Scope |
|-------------------|--------|
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
| Multiple | dominant path, or feature name |

## Modes

- **Default** — summarize + commit + push (parent only)
- **Message only** — draft Conventional Commits; no add/commit/push
- **PR description only** — use the template below; do not open a PR unless asked

```markdown
## Summary
1–2 sentences

## Changes
- Bullet per response / logical change

## Notes
- Migrations / review hints (omit if none)
```

## Safety

- Never update git config
- Never interactive git (`-i`, TTY `add -p`)
- Never `--no-verify` unless the user asks
- Never amend an already-pushed commit unless the user asks and amend rules allow it
