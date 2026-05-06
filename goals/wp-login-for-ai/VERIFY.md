# VERIFY.md - WP Login for AI

## 0. Setup Prerequisites

This goal assumes the following are available before /goal runs verification:

- wp-env mapped at `.wp-env.json` with base URL `http://localhost:8888`.
- The project dependencies installed with `npm install` at the repository root.
- Composer available inside the wp-env cli container.
- playwright-cli skill or binary available. In this repo, see `.codex/skills/playwright-cli/SKILL.md`.
- WordPress test user available. wp-env defaults include `admin` / `password`.
- Harness sandbox bypass for wp-env, Docker, and playwright-cli commands. If the harness running /goal sandboxes shell access, pre-approve or bypass it for those tool families before running verification.

If a tool is not available or a sandbox blocks it, /goal must document the gap in `PROGRESS.md` rather than skipping the check silently.

## 1. Verification Philosophy

/goal must verify the result with real evidence, not assumptions.

Passing tests are useful only if they cover the requirements in `GOAL.md`.
If a test or check does not cover a requirement, /goal must add a better check or document the gap in `PROGRESS.md`.

## 2. Environment Assumptions

Expected environment:

- Runtime: PHP 8.1+ provided by wp-env
- Package manager (PHP): composer inside the wp-env cli container
- Package manager (JS): npm on the host
- Database: MySQL provided by wp-env
- Browser test runner: playwright-cli on the host
- Base URL: `http://localhost:8888`

Routing rule: anything provided by wp-env (WP-CLI, composer, php, phpunit) must be invoked via `npx wp-env run cli ...`. Never call the host's native `wp`, `composer`, `php`, or `phpunit` against this project. npm, node, npx, playwright-cli, and git stay native.

Sandbox rule: if the harness sandboxes shell access, the wp-env wrapper itself needs the bypass because `npx wp-env run cli ...` talks to Docker. Browser launches via `playwright-cli` need the same treatment.

Before running checks, /goal should inspect the repo and confirm commands actually exist. Then route the existing WordPress/PHP commands through wp-env.

## 3. Required Commands

Run these before marking the goal complete:

```bash
npm install
npx wp-env start
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai install
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai test
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai lint
npm run test:smoke
npm run lint
npm test
```

Expected result:

- All commands exit with code `0`.
- Any failure must be fixed or documented in `PROGRESS.md` as an external blocker.

## 4. Targeted Checks

Use these for faster inner-loop work while developing:

```bash
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai lint
npx wp-env run cli wp plugin activate wp-login-for-ai
npx wp-env run cli wp eval 'echo "WP OK\n";'
```

When to run:

- After editing related plugin files.
- After adding or updating tests.
- Before running the full required command list.

## 5. WordPress Smoke Checks

Use wp-env's built-in WP-CLI to confirm the plugin is healthy:

```bash
npx wp-env run cli wp plugin list
npx wp-env run cli wp plugin activate wp-login-for-ai
npx wp-env run cli wp eval 'echo "WP OK\n";'
```

Expected:

- Plugin appears in `wp plugin list` and is `active`.
- `wp eval` prints `WP OK` with no PHP warnings or notices.

## 6. PHP-Internal Checks

Use these for behavior that lives server-side and can be proven without the browser.

### Check P-001 - AC-003.1: Environment gate blocks production

Command:

```bash
npx wp-env run cli wp eval-file goals/wp-login-for-ai/test-artifacts/p-001-environment-gate.php
```

The eval file should simulate or directly call the plugin's environment-check function and prove that `production` is blocked while `local` and `development` are allowed.

Expected output:

```text
PASS: environment gate
```

Evidence to record in `PROGRESS.md`:

- command run
- expected output
- actual output

### Check P-002 - AC-003.3: Unknown user does not change the current user

Command:

```bash
npx wp-env run cli wp eval-file goals/wp-login-for-ai/test-artifacts/p-002-invalid-user.php
```

The eval file should set a known current user, invoke the invalid-user path, and assert that the user ID is unchanged.

Expected output:

```text
PASS: invalid user preserves session
```

Evidence to record in `PROGRESS.md`:

- command run
- expected output
- actual output

## 7. Browser Checks (playwright-cli)

Use these for behavior visible through HTTP and wp-admin.

Session naming convention: `goal-wp-login-for-ai`.

### Check B-001 - AC-001.1: Username URL login

Steps:

```text
1. Open http://localhost:8888/?autologwp=admin
2. Wait for navigation to finish.
3. Assert the final URL starts with http://localhost:8888/wp-admin/
4. Assert the wp-admin toolbar or profile area indicates the browser is logged in as admin.
5. Assert the final URL does not contain autologwp.
```

Evidence to record in `PROGRESS.md`:

- session name `goal-wp-login-for-ai`
- final URL or relevant DOM snippet
- screenshot path under `goals/wp-login-for-ai/test-artifacts/B-001/` if produced

### Check B-002 - AC-001.2: Email URL login

Steps:

```text
1. Create or confirm a test user whose email is wordpress@example.com.
2. Open http://localhost:8888/?autologwp=wordpress@example.com
3. Wait for navigation to finish.
4. Assert the final URL starts with http://localhost:8888/wp-admin/
5. Assert wp-admin shows the matching user identity.
```

Evidence to record in `PROGRESS.md`:

- session name `goal-wp-login-for-ai`
- final URL or relevant DOM snippet
- screenshot path under `goals/wp-login-for-ai/test-artifacts/B-002/` if produced

### Check B-003 - AC-002.1 and AC-002.2: Switch from one user to another

Steps:

```text
1. Create or confirm two users, for example admin and editor.
2. Open http://localhost:8888/?autologwp=admin and assert wp-admin shows admin.
3. Open http://localhost:8888/?autologwp=<editor-login-or-email>.
4. Assert wp-admin shows the editor user.
5. Assert the browser did not visit wp-login.php during the switch.
```

Evidence to record in `PROGRESS.md`:

- session name `goal-wp-login-for-ai`
- final URL or relevant DOM snippet
- screenshot path under `goals/wp-login-for-ai/test-artifacts/B-003/` if produced

### Check B-004 - AC-003.3 and AC-003.4: Invalid user fails safely

Steps:

```text
1. Start logged in as admin with http://localhost:8888/?autologwp=admin.
2. Open http://localhost:8888/?autologwp=definitely-not-a-user.
3. Assert the response is a safe machine-readable error.
4. Open http://localhost:8888/wp-admin/
5. Assert the browser is still logged in as admin.
```

Evidence to record in `PROGRESS.md`:

- session name `goal-wp-login-for-ai`
- final URL or relevant DOM snippet
- screenshot path under `goals/wp-login-for-ai/test-artifacts/B-004/` if produced

## 8. Regression Checks

/goal must confirm these existing behaviors still work:

- [ ] Requests without `autologwp` behave like normal WordPress requests.
- [ ] The standard `wp-login.php` form still works.
- [ ] Plugin activates and deactivates without warnings.
- [ ] No PHP warnings or notices appear in `wp eval 'echo "OK";'` output.

## 9. Security Checks

/goal must verify:

- [ ] The shortcut is blocked outside `local` and `development` environments.
- [ ] The shortcut is blocked for non-local hosts.
- [ ] Unknown usernames and emails fail safely.
- [ ] Invalid inputs fail safely with no fatal errors.
- [ ] Inputs are sanitized with the appropriate WordPress helpers.
- [ ] Outputs are escaped with `esc_html` / `esc_attr` / `esc_url` where applicable.
- [ ] Redirects use `wp_safe_redirect()`.
- [ ] No secrets, auth cookies, or sensitive user metadata are logged.

## 10. Documentation Checks

/goal must verify:

- [ ] `README.md` includes the feature name and usage examples.
- [ ] `README.md` clearly says the plugin is local/development-only and unsafe for production.
- [ ] Commands in `README.md` actually work in wp-env.
- [ ] Configuration names in docs match the code.
- [ ] Changelog entry is updated if the project keeps one.

## 11. Evidence Format

/goal must add this section to `goals/wp-login-for-ai/PROGRESS.md` before marking complete. The tables below are schema-only examples. Leave the data rows empty here; /goal populates them at completion time.

```md
## Final Verification Evidence

### Commands Run

| Command | Result | Notes |
| ------- | ------ | ----- |
|         |        |       |

### PHP-Internal Check Evidence

| AC ID | Check ID | Expected | Actual | Status |
| ----- | -------- | -------- | ------ | ------ |
|       |          |          |        |        |

### Browser Check Evidence

| AC ID | Check ID | Session | Evidence | Status |
| ----- | -------- | ------- | -------- | ------ |
|       |          |         |          |        |

### Acceptance Criteria Evidence

| AC ID | Evidence | Status |
| ----- | -------- | ------ |
|       |          |        |

### Files Changed

- _(populated by /goal)_

### Remaining Risks

- _(populated by /goal, "None known" if clean)_
```

## 12. Failure Handling

If a required check fails:

1. Identify whether the failure is related to this goal.
2. Fix related failures.
3. Re-run the targeted check.
4. Re-run the full required-command list before completion.
5. If unrelated or blocked, document it in `PROGRESS.md` and continue with the next item.

/goal must not mark the goal complete if any required verification is missing, failing, or uncertain.

## 13. Budget-Limit Behavior

If /goal reaches a token or time budget:

- Stop new substantive work.
- Update `goals/wp-login-for-ai/PROGRESS.md`:
  - completed requirements
  - unverified requirements
  - blockers
  - recommended next `/goal` objective
