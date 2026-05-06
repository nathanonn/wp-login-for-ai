# GOAL.md - WP Login for AI

## 1. Objective

Implement a simple WordPress development plugin that lets an AI agent switch WordPress users by visiting a URL containing `autologwp=<username-or-email>`. The feature must work in the local wp-env site, accept either a username or email address, replace any existing logged-in session with the requested user, and stay blocked outside local/development environments.

By the end of this goal, the repository should support:

- A working `autologwp` query parameter on the front-end request lifecycle.
- Username and email lookup for WordPress users.
- Safe session switching between users without visiting `wp-login.php`.
- Documentation and verification evidence for local-only use.

## 2. Background

AI agents testing WordPress plugins often need to switch between admin, editor, author, customer, or subscriber accounts. Today the agent has to log out, open `wp-login.php`, and submit credentials for each switch, which burns browser steps and tokens.

Relevant existing behavior:

- The repository is a fresh scaffold for a new plugin.
- wp-env is mapped to `./wp-login-for-ai` and defaults to `http://localhost:8888`.
- The plugin entry file exists but does not yet implement login behavior.

Problem / opportunity:

- A local-only URL shortcut can reduce test friction while keeping the unsafe behavior out of production sites.

## 3. Source of Truth

/goal must treat these as the authoritative references:

- `AGENTS.md`
- `goals/wp-login-for-ai/VERIFY.md`
- Existing plugin files under `wp-login-for-ai/`
- Existing tests under `wp-login-for-ai/tests/` if present
- WordPress coding standards already used in the repo

If anything conflicts, follow this priority order:

1. This `GOAL.md`
2. `goals/wp-login-for-ai/VERIFY.md`
3. `AGENTS.md`
4. Existing code conventions
5. README / older docs

## 4. Scope

### In scope

- Detect `autologwp` on front-end requests and treat the value as a username or email address.
- Look up the requested WordPress user and log in as that user in the current browser session.
- If another user is already logged in, switch the current session to the requested user.
- Redirect after a successful switch to `wp-admin/` using a safe WordPress redirect.
- Return safe, machine-readable failures for invalid users or blocked environments.
- Enforce local/development-only execution.
- Add capability checks, input validation, output escaping where relevant.
- Add or update tests inside `wp-login-for-ai/tests/` where practical.
- Update `README.md` examples.

### Out of scope

- Production use.
- Public internet autologin behavior.
- A settings page or admin UI.
- Shared secrets, tokens, signed links, REST endpoints, WP-CLI commands, or third-party services.
- New admin redesign.
- New paid third-party services.
- Database schema changes.
- Behavior in other plugins outside this feature's surface.

## 5. Allowed Files / Areas

/goal may edit:

- `wp-login-for-ai/**`
- `goals/wp-login-for-ai/PROGRESS.md`
- `README.md`
- `composer.json` only if needed for autoload or dev scripts
- `package.json` only if needed for verification scripts

/goal should avoid editing:

- generated build files
- unrelated plugin modules
- production configuration files
- `package-lock.json` / `composer.lock` unless dependencies change intentionally

## 6. User Stories

### US-001 - Log in through a URL

As an AI testing agent,
I want to visit a URL with `autologwp=<username-or-email>`,
so that I can become the target WordPress user without submitting the login form.

Acceptance criteria:

- [ ] AC-001.1 - Visiting `http://localhost:8888/?autologwp=admin` logs the browser in as the `admin` user.
- [ ] AC-001.2 - Visiting `http://localhost:8888/?autologwp=wordpress@example.com` logs the browser in as the matching email user.
- [ ] AC-001.3 - A successful login redirects to `http://localhost:8888/wp-admin/` or the equivalent wp-env admin URL without leaving `autologwp` in the final URL.

### US-002 - Switch users without manual logout

As an AI testing agent,
I want the URL shortcut to replace the current logged-in user,
so that I can move between accounts in one browser session.

Acceptance criteria:

- [ ] AC-002.1 - If the browser is already logged in as one user, visiting the shortcut for another user switches the session to the new user.
- [ ] AC-002.2 - The switch updates WordPress auth cookies and current user state so wp-admin shows the new user's identity.

### US-003 - Fail safely

As a site owner,
I want the shortcut constrained to local development and invalid requests handled safely,
so that the plugin cannot become a production backdoor.

Acceptance criteria:

- [ ] AC-003.1 - The shortcut only runs when `wp_get_environment_type()` is `local` or `development`.
- [ ] AC-003.2 - The shortcut only runs for local development hosts such as `localhost`, `127.0.0.1`, or `[::1]`.
- [ ] AC-003.3 - Requests for an unknown username or email fail without changing the current logged-in user.
- [ ] AC-003.4 - Blocked or invalid requests return a safe machine-readable error and do not emit PHP warnings or notices.

## 7. Business / Functional Rules

- BR-001 - The query parameter name is exactly `autologwp`.
- BR-002 - The parameter value can be either a username or an email address.
- BR-003 - Email lookup must use WordPress user APIs rather than manual database queries.
- BR-004 - Username lookup must use WordPress user APIs rather than manual database queries.
- BR-005 - A successful switch must clear or replace the previous auth cookies before setting the new user's auth cookies.
- BR-006 - A successful switch must call the appropriate WordPress login hooks so normal login-dependent behavior can run.
- BR-007 - The feature is intentionally local/development-only and must not be positioned as production authentication.
- BR-008 - Do not expose private metadata to unauthorized users.
- BR-009 - Do not modify posts.
- BR-010 - Return machine-readable errors for blocked or invalid programmatic surfaces.
- BR-011 - Preserve backward compatibility with existing plugin behavior.

## 8. Technical Constraints

- TC-001 - Must follow the `WpLoginForAi\` PSR-4 convention in `wp-login-for-ai/composer.json`.
- TC-002 - Must remain compatible with WordPress 6.0+ and PHP 8.1+ as declared in `wp-login-for-ai/wp-login-for-ai.php`.
- TC-003 - Must not introduce new runtime dependencies without documenting why.
- TC-004 - Must use WordPress APIs for user lookup, cookie changes, redirect handling, sanitization, and error responses.
- TC-005 - Must not require JavaScript.
- TC-006 - Must not require database schema changes.

## 9. Error Handling

/goal must handle:

- Missing `autologwp` parameter by doing nothing.
- Empty `autologwp` parameter.
- Unknown username.
- Unknown email address.
- Request outside `local` or `development` environment.
- Request from a non-local host.
- User switch while already logged in as another user.

Expected behavior:

- Missing parameter leaves the request untouched.
- Invalid or blocked requests do not change the current logged-in user.
- Invalid or blocked requests return a clear machine-readable error with an appropriate HTTP status.
- No raw PHP warnings or notices may leak to output.
- Errors should be machine-readable for the URL shortcut surface.

## 10. Security / Permission Requirements

- The shortcut must be blocked unless `wp_get_environment_type()` returns `local` or `development`.
- The shortcut must be blocked unless the request host is local development oriented, such as `localhost`, `127.0.0.1`, or `[::1]`.
- No authenticated capability check is required before login because the feature's purpose is to create a local test session from a URL. The local/development environment gate is the security boundary.
- The `autologwp` value must be unslashed and sanitized before use.
- User lookup must go through `get_user_by()` or equivalent WordPress APIs.
- Redirects must use `wp_safe_redirect()` and exit immediately afterward.
- Any HTML output must be escaped with WordPress escaping helpers.
- No secrets, credentials, auth cookies, or sensitive user metadata may be logged.

## 11. Data / Migration Requirements

- Database changes required: No
- Migration required: No
- Backward compatibility required: Yes

Details:

- No custom tables, options, or user meta are required.
- Not applicable. No migration is needed.
- Not applicable. Rollback is plugin deactivation or removal.

## 12. Documentation Requirements

/goal must update:

- [ ] `README.md` usage example
- [ ] Local/development-only warning
- [ ] Example URLs for username and email login
- [ ] Inline comments only where the why is non-obvious
- [ ] Changelog entry if the project keeps one

## 13. Definition of Done

The goal is complete only when:

- [ ] Every acceptance criterion is implemented.
- [ ] Every required verification command in `goals/wp-login-for-ai/VERIFY.md` passes or has a documented external blocker.
- [ ] New or changed behavior has tests where practical.
- [ ] Existing behavior is not regressed.
- [ ] `README.md` is updated.
- [ ] `goals/wp-login-for-ai/PROGRESS.md` contains final evidence.
- [ ] /goal has performed a completion audit mapping each AC to evidence.

## 14. Completion Audit Format

Before marking the goal complete, /goal must update `goals/wp-login-for-ai/PROGRESS.md` with this table:

| Requirement | Evidence | Status |
| ----------- | -------- | ------ |
| AC-001.1 | file / test / command output | Pass / Fail / Blocked |
| AC-001.2 | file / test / command output | Pass / Fail / Blocked |
| AC-001.3 | file / test / command output | Pass / Fail / Blocked |
| AC-002.1 | file / test / command output | Pass / Fail / Blocked |
| AC-002.2 | file / test / command output | Pass / Fail / Blocked |
| AC-003.1 | file / test / command output | Pass / Fail / Blocked |
| AC-003.2 | file / test / command output | Pass / Fail / Blocked |
| AC-003.3 | file / test / command output | Pass / Fail / Blocked |
| AC-003.4 | file / test / command output | Pass / Fail / Blocked |

## 15. Stop Conditions

/goal must stop and ask for human review if:

- A required architectural decision is ambiguous.
- A required command would be destructive, such as deleting users or resetting the database.
- A migration is needed but not specified in Section 11.
- Tests fail for reasons unrelated to this goal.
- The implementation requires touching out-of-scope files.
- Secrets, credentials, production data, or paid services are required.
- Making the shortcut usable outside local/development environments appears necessary.

## 16. Notes for Future Goals

- Optional follow-up: add a signed-token mode for non-local staging sites.
- Optional follow-up: add `redirect_to` support if agents need to land on arbitrary same-site URLs after switching.
