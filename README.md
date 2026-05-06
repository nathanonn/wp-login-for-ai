# WP Login for AI

WP Login for AI is a WordPress development helper for local AI-agent testing. It lets a browser switch to a WordPress user by visiting a front-end URL with an `autologwp` query parameter.

This is intentionally unsafe for production authentication. The shortcut only runs when WordPress reports the environment as `local` or `development`, and only from local hosts such as `localhost`, `127.0.0.1`, or `[::1]`.

## Usage

Start the local WordPress environment:

```bash
npm install
npx wp-env start
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai install
npx wp-env run cli wp plugin activate wp-login-for-ai
```

Log in as a username:

```text
http://localhost:8888/?autologwp=admin
```

Log in as a matching email address:

```text
http://localhost:8888/?autologwp=wordpress@example.com
```

A successful switch redirects to `wp-admin/` and removes `autologwp` from the final browser URL. Visiting the shortcut while already logged in replaces the current auth cookies and switches the browser session to the requested user.

Invalid users, empty values, blocked environments, and non-local hosts return machine-readable JSON errors and do not change the current logged-in user.

## Verification

Run the required checks from the repository root:

```bash
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai test
npx wp-env run cli composer --working-dir=wp-content/plugins/wp-login-for-ai lint
npm run test:smoke
npm run lint
npm test
```
