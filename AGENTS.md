# AGENTS.md - WP Login for AI

## Stack

- WordPress plugin under `wp-login-for-ai/`
- PHP 8.1+ with PSR-4 namespace `WpLoginForAi\`
- Development environment: wp-env (`.wp-env.json` at project root)
- Browser verification: playwright-cli (`.codex/skills/playwright-cli/` is available in this repo)
- Server-side verification: `wp eval` / `wp eval-file` via `npx wp-env run cli`

## Canonical Command Pattern

Anything provided by the wp-env container must be invoked through `npx wp-env run cli ...`.
Do not call the host's native `wp`, `composer`, `php`, or `phpunit` against this project.
Those host tools target the host PHP/MySQL environment and can produce misleading results.

| Tool | Do | Do Not |
| --- | --- | --- |
| WP-CLI | `npx wp-env run cli wp plugin list` | `wp plugin list` |
| WordPress eval | `npx wp-env run cli wp eval 'echo "OK";'` | `wp eval ...` directly |
| Composer | `npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai install` | `composer --working-dir=wp-login-for-ai install` |
| PHP scripts | `npx wp-env run cli php wp-content/plugins/wp-login-for-ai/script.php` | `php wp-login-for-ai/script.php` |
| PHPUnit | `npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai test` | `phpunit` or native composer |

These stay native because they are host-side tools:

- `npm`, `node`, and `npx`
- `playwright-cli`
- `git`

If a command is not clearly host-side, route it through wp-env.

## Harness Sandbox

If the agent harness running `/goal` restricts shell access, pre-approve or bypass it for wp-env, Docker, and playwright-cli commands. These tools touch Unix sockets, the network, and process-spawn paths that sandboxes often block.

Apply the bypass to:

- `npx wp-env start|stop|run|destroy|clean`
- any `docker` invocation
- any browser launch via `playwright-cli`

Native commands inside the wp-env cli container still require the wp-env wrapper, so the wrapper needs the same approval.

## Feature Rules

- The `autologwp` URL shortcut is a local/development testing helper, not a production authentication feature.
- The shortcut must only run when WordPress reports `local` or `development` via `wp_get_environment_type()`.
- The shortcut must only run on local development hosts such as `localhost`, `127.0.0.1`, or `[::1]`.
- The shortcut accepts either a WordPress username or an email address.
- The shortcut switches the current session to the matched user, even when another user is already logged in.
- Invalid users must fail without changing the current logged-in user.
- Do not add a settings UI unless a goal explicitly asks for one.
- No secrets, tokens, or user metadata should be logged.

## Conventions

- Folder names: lowercase kebab-case
- PHP class names: PascalCase, PSR-4 in `wp-login-for-ai/src/`
- Sanitize input with WordPress helpers before lookup.
- Escape all output with WordPress escaping helpers.
- Use `wp_safe_redirect()` for redirects.
- Nonces are normally required for state-changing requests; this local-only login shortcut is the documented exception and must remain blocked outside local/development environments.

## Canonical Commands

```bash
# Bring the dev env up (host-side)
npx wp-env start

# Install plugin dependencies (wp-env-routed composer)
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai install

# Run plugin smoke checks (wp-env-routed)
npx wp-env run cli wp plugin list
npx wp-env run cli wp plugin activate wp-login-for-ai
npx wp-env run cli wp eval 'echo "WP OK\n";'

# Run PHP checks (wp-env-routed composer)
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai test
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai lint

# Browser verification is host-side via the playwright-cli skill.
```

## Goal-Driven Workflow

Each goal lives at `goals/<slug>/`:

- `GOAL.md` defines the objective, scope, and acceptance criteria.
- `VERIFY.md` defines verification commands and evidence rules.
- `PROGRESS.md` is the audit trail `/goal` updates continuously.

To run this goal:

```text
/goal Complete goals/wp-login-for-ai/GOAL.md. Use goals/wp-login-for-ai/VERIFY.md as the verification contract. Update goals/wp-login-for-ai/PROGRESS.md continuously. Treat uncertainty as incomplete.
```
