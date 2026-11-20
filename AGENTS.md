# Portale Staff Amichiamoci

- Use the PHP and Composer versions documented in the README. Update `composer.lock` alongside changes to dependency requirements.
- Keep controllers responsible for HTTP handling, models for database access, and views for escaped output. Verify route, form and JavaScript callers together when changing a request.
- Keep every credential, token, private document, and real event record out of diffs, tests and command output. Use synthetic fixtures and a disposable database. Do not open `data/certificati` or `data/documenti` during development.
- Run the relevant test scripts in `tests/` and lint touched PHP files. For dependency changes, run `composer audit --locked`.
