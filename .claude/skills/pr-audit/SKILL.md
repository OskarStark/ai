---
name: pr-audit
description: >-
  Audits a pull request against this repository's conventions and coding
  standards. Produces a checklist report with pass/fail per item and a
  prioritised list of required changes before the PR can be merged.
argument-hint: '<pr-number>'
---

## Goal

Perform a thorough audit of the given PR against the project conventions defined
in `CLAUDE.md` and `.github/PULL_REQUEST_TEMPLATE.md`. Output a structured
report the maintainer can use to give actionable review feedback.

---

## Step 0 — Pre-flight checks

Before doing anything else, verify GitHub CLI authentication:

```
gh auth status 2>&1
```

If this fails (exit code non-zero or output contains "not logged in"), stop
immediately and tell the user:

> GitHub authentication is required for this skill. Please run `gh auth login`
> first, then re-run the skill.

Do NOT proceed to any other step if this check fails.

---

## Step 1 — Fetch PR metadata

Run the following in parallel:

```
gh pr view <pr-number> --json title,body,author,files,commits,labels
gh pr diff <pr-number>
```

From the diff, collect:
- Which `src/<component>/` directories are touched
- Whether any new PHP classes are introduced
- Whether any public API is changed (method signatures, removed classes/methods,
  renamed parameters)
- Whether test files are included

---

## Step 2 — Check PR description

Parse the PR body against the template table:

| Field | Rule |
|---|---|
| `Bug fix?` | Must be `yes` or `no` — not left as placeholder |
| `New feature?` | Must be `yes` or `no` |
| `Docs?` | Must be `yes` or `no` |
| `Issues` | Must reference at least one issue (`Fix #N`) **or** contain an explanatory description below the table |
| `License` | Must be `MIT` |

Also verify that there is an actual description below the table explaining the
rationale behind the PR (what problem it solves, what it changes, or why it is
needed). A PR body that contains only the template table with no explanatory
text fails this check.

---

## Step 3 — Changelog and upgrade docs

**If `New feature? yes`:**
- For every touched `src/<component>/` directory, verify that
  `src/<component>/CHANGELOG.md` (and for bridges:
  `src/<component>/src/Bridge/<Name>/CHANGELOG.md`) has a new entry under the
  current version heading. Report each missing file.

**If any public API was removed, renamed, or had a signature change:**
- Verify that `UPGRADE.md` at the repo root contains an entry for the affected
  component. Report if missing.

---

## Step 4 — Code conventions

Read changed PHP files from the diff and check each rule below. For each
violation, record the file path and line number.

### 4a — General

- [ ] No use of `empty()` — prefer `[] === $array`, `'' === $string`,
  `null === $value`
- [ ] No global exception classes — `\RuntimeException`,
  `\InvalidArgumentException`, `\LogicException`, etc. must be replaced with
  project-specific exceptions
- [ ] No short-circuit evaluation used as control flow (e.g. `$x || doSomething()`) — use classic `if` statements
- [ ] Newline at end of every file

### 4b — New classes

- [ ] Each newly introduced class has an `@author` tag in its docblock
- [ ] Array shapes used for parameter and return types where applicable (e.g.
  `@param array{key: string} $options`)

### 4c — Tests

- [ ] Test methods use `self::assert*` not `$this->assert*`
- [ ] No risky tests (tests without at least one assertion, or that catch broad
  `\Exception` without asserting anything)
- [ ] `MockHttpClient` used instead of mocking the HTTP client or manually
  building response mocks
- [ ] `assertSame()` preferred over `assertEquals()` for scalar comparisons

### 4d — Commits

- [ ] No commit message mentions "Claude", "Co-Authored-By: Claude", or similar

---

## Step 5 — Generate report

Output a Markdown report with the following structure:

```
## PR Audit: #<number> — <title>

### PR Description
- [ ] or [x] for each template field

### Changelog / Upgrade Docs
- [ ] or [x] per affected component

### Code Conventions
#### General
- findings or ✅ all good

#### New Classes
- findings or ✅ none / all good

#### Tests
- findings or ✅ all good

#### Commits
- findings or ✅ all good

---
### Summary
**Status:** Ready to merge / Changes required

**Required changes:**
1. …
2. …
```

Keep findings concise: one line per issue, with file path and line number where
applicable. Do not repeat passing items verbosely — a single `✅ all good` per
section is enough when nothing is wrong.
