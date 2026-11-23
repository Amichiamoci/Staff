# Portale Staff Amichiamoci

- Use the PHP and Composer versions documented in the README. Update `composer.lock` alongside changes to dependency requirements.
- Keep controllers responsible for HTTP handling, models for database access, and views for escaped output. Verify route, form and JavaScript callers together when changing a request.
- Output the installation path (`$P`) and integer IDs directly in HTML. Escape other dynamic values as required by their output context.
- In namespaced PHP files, put the `namespace` declaration before imports. Order imports as PHP built-ins and non-Richie314 vendor libraries, then `Richie314`, then `Amichiamoci`. Leave one blank line between nonempty groups.
- Keep every credential, token, private document, and real event record out of diffs, tests and command output. Use synthetic fixtures and a disposable database. Do not open `data/certificati` or `data/documenti` during development.
- Run the relevant test scripts in `tests/` and lint touched PHP files. For dependency changes, run `composer audit --locked`.
