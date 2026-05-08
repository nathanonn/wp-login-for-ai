# WP Login for AI

WP Login for AI is a WordPress development helper for local AI-agent testing. It lets a browser switch to a WordPress user by visiting a front-end URL with an `autologwp` query parameter.

This is intentionally unsafe for production authentication. The shortcut only runs when WordPress reports the environment as `local` or `development`, and only from local hosts such as `localhost`, `127.0.0.1`, or `[::1]`.

## wp-env Usage

Start the local WordPress environment:

```bash
npm install
npx wp-env start
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai install
npx wp-env run cli wp plugin activate wp-login-for-ai
```

Confirm the plugin is active:

```bash
npx wp-env run cli wp plugin list
```

wp-env normally serves the development site at:

```text
http://localhost:8888
```

Log in as a username by opening:

```text
http://localhost:8888/?autologwp=admin
```

Log in as a matching email address by opening:

```text
http://localhost:8888/?autologwp=wordpress@example.com
```

A successful switch redirects to `wp-admin/` and removes `autologwp` from the final browser URL. Visiting the shortcut while already logged in replaces the current auth cookies and switches the browser session to the requested user.

Invalid users, empty values, blocked environments, and non-local hosts return machine-readable JSON errors and do not change the current logged-in user.

### Creating Test Users

Create additional local users through wp-env when a browser test needs to switch roles:

```bash
npx wp-env run cli wp user create editor editor@example.com --role=editor --user_pass=password
npx wp-env run cli wp user create subscriber subscriber@example.com --role=subscriber --user_pass=password
```

Then switch to those users with:

```text
http://localhost:8888/?autologwp=editor
http://localhost:8888/?autologwp=editor@example.com
http://localhost:8888/?autologwp=subscriber
```

### Browser Testing Flow

For AI-agent browser tests, use the shortcut instead of manually submitting `wp-login.php` when the task needs a known local user session.

```text
1. Start wp-env.
2. Activate `wp-login-for-ai`.
3. Create any role-specific test users needed for the test.
4. Open `http://localhost:8888/?autologwp=<username-or-email>`.
5. Wait for the redirect to `http://localhost:8888/wp-admin/`.
6. Assert the admin toolbar shows the expected user.
7. To switch users, open the shortcut again with another username or email.
```

The shortcut is for local development automation only. Do not rely on it for production, staging, shared preview sites, or public authentication.

## AI Agent Instructions

Add a section like this to a project's `AGENTS.md` or `CLAUDE.md` when the project uses this helper:

````md
## WP Login for AI

This project uses the `wp-login-for-ai` development plugin to let AI agents switch WordPress users in local wp-env browser tests.

Rules:

- Use this helper only in local wp-env or development environments.
- Never use it on production, staging, public preview, or shared remote sites.
- Start the local environment with `npx wp-env start`.
- Activate the plugin with `npx wp-env run cli wp plugin activate wp-login-for-ai`.
- Run WP-CLI commands through wp-env, for example `npx wp-env run cli wp user list`.
- Do not call the host machine's native `wp`, `php`, `composer`, or `phpunit` against the wp-env site.
- To log in or switch users, open `http://localhost:8888/?autologwp=<username-or-email>`.
- After using the shortcut, expect the final URL to be `http://localhost:8888/wp-admin/`.
- Verify the admin toolbar or profile area shows the expected user after each switch.
- Unknown users should return a JSON error and must not change the current session.
- Do not log secrets, auth cookies, passwords, or user metadata.

Common commands:

```bash
npx wp-env start
npx wp-env run cli wp plugin activate wp-login-for-ai
npx wp-env run cli wp user list --fields=ID,user_login,user_email,roles
npx wp-env run cli wp user create editor editor@example.com --role=editor --user_pass=password
```

Common browser URLs:

```text
http://localhost:8888/?autologwp=admin
http://localhost:8888/?autologwp=wordpress@example.com
http://localhost:8888/?autologwp=editor
```
````

## Verification

Run the required checks from the repository root:

```bash
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai test
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai lint
npm run test:smoke
npm run lint
npm test
```

## Packaging

Build a distribution ZIP from the repository root:

```bash
npm run package
```

The ZIP is written to `dist/wp-login-for-ai.zip`. It contains the installable `wp-login-for-ai/` plugin folder and excludes development-only files such as `vendor/`, `tests/`, and Composer metadata.

Publishing a GitHub Release runs the package workflow and uploads `wp-login-for-ai.zip` to the release assets. The workflow can also be run manually with an existing release tag.

## How This Was Built

This plugin was built in 28 minutes with zero supervision using Codex's `/goal` command. A spec went in, a working plugin with tests and Playwright screenshots came out.

The full workflow — from spec to scaffold to autonomous build — is documented here: [How to Use Codex /goal to Build WordPress Plugins](https://www.nathanonn.com/codex-goal-command-wordpress-plugin/).
