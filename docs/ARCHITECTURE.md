# Architecture

## Request flow

1. Apache serves `public/index.php` as the application entry point.
2. `Auth` handles the local demo login and stores the authenticated identity in the PHP session.
3. `Security` creates and validates CSRF tokens for state-changing requests.
4. `Storage` exposes one interface for groups, job roles, edits, exports and audit entries.
5. When `pdo_sqlite` is available, the application creates and uses an SQLite database. Otherwise it falls back to a local JSON file.
6. Every description update is checked server-side against the record's fictional owner and approver assignments before it is persisted.

## Main components

- `public/index.php` — routing, controllers and rendered views;
- `src/Auth.php` — local demo authentication;
- `src/Security.php` — session and CSRF helpers;
- `src/Storage.php` — persistence and authorization-aware data operations;
- `database/schema.sql` — SQLite schema;
- `public/assets/` — responsive interface, filtering and theme handling;
- `tests/smoke.php` — end-to-end application smoke test.

## SQL behavior

The SQLite implementation uses PDO and prepared statements for values. Representative operations include:

- loading groups and job roles with ordered `SELECT` queries;
- creating synthetic seed records with parameterized `INSERT` statements;
- updating descriptions with parameterized `UPDATE` statements;
- recording successful edits in the audit table.

Authorization is not delegated to client-side controls. The server checks the authenticated email against the record's owner and approver lists before an update is accepted.
