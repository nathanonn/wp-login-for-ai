# PROGRESS.md - WP Login for AI

## Current Status

Status: Not started

## Summary

/goal will implement a local/development-only URL login switcher for WordPress users.

## Completed Work

- [ ] Initial scaffold present
- [ ] Acceptance criteria implemented
- [ ] Verification commands pass
- [ ] Documentation updated
- [ ] Final evidence recorded

## Remaining Work

- [ ] AC-001.1 - Username URL logs in as admin
- [ ] AC-001.2 - Email URL logs in as matching user
- [ ] AC-001.3 - Successful login redirects safely without leaving `autologwp` in the final URL
- [ ] AC-002.1 - Existing logged-in session switches to requested user
- [ ] AC-002.2 - Auth cookies and current user state reflect switched user
- [ ] AC-003.1 - Shortcut only runs in local/development environments
- [ ] AC-003.2 - Shortcut only runs on local development hosts
- [ ] AC-003.3 - Unknown user fails without changing current session
- [ ] AC-003.4 - Blocked or invalid requests return safe machine-readable errors

## Commands Run

| Command | Result | Notes |
| ------- | ------ | ----- |
|         |        |       |

## Files Changed

- /goal records each changed file here.

## Decisions Made

| Decision | Reason |
| -------- | ------ |
| Use `wp-login-for-ai` as the plugin slug | Matches the repository name and clearly describes the plugin. |
| Scaffold root wp-env and goal files now | The repository had no existing plugin or tooling files. |
| Keep autologin local/development-only | The URL shortcut is useful for AI testing but unsafe as production authentication. |

## Blockers

| Blocker | Impact | Needed From Human |
| ------- | ------ | ----------------- |
|         |        |                   |

## Acceptance Criteria Evidence

| Requirement | Evidence | Status |
| ----------- | -------- | ------ |
| AC-001.1 | | Pending |
| AC-001.2 | | Pending |
| AC-001.3 | | Pending |
| AC-002.1 | | Pending |
| AC-002.2 | | Pending |
| AC-003.1 | | Pending |
| AC-003.2 | | Pending |
| AC-003.3 | | Pending |
| AC-003.4 | | Pending |

## Final Verification Evidence

/goal fills this in only before marking complete.

### Commands Run

### PHP-Internal Check Evidence

### Browser Check Evidence

### Acceptance Criteria Evidence

### Files Changed

### Remaining Risks
