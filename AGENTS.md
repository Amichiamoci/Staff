# Portale Staff Amichiamoci

- Use the PHP and Composer versions documented in the README.
  Update `composer.lock` alongside changes to dependency requirements.
- Keep controllers responsible for HTTP handling, models for database access, and views for escaped output.
  Verify route, form and JavaScript callers together when changing a request.
- Output the installation path (`$P`) and integer IDs directly in HTML.
  Escape other dynamic values as required by their output context.
- In namespaced PHP files, put the `namespace` declaration before imports.
  Order imports as PHP built-ins and non-Richie314 vendor libraries, then `Richie314`, then `Amichiamoci`.
  Leave one blank line between nonempty groups.
- Keep every credential, token, private document, and real event record out of diffs, tests and command output.
  Use synthetic fixtures and a disposable database.
  Do not open `data/certificati` or `data/documenti` during development.
- Run the relevant test scripts in `tests/` and lint touched PHP files.
  For dependency changes, run `composer audit --locked`.

## Prose and comments

- Keep each sentence on a single source line in Markdown prose and code comments.
- Start a new source line after each sentence.
  Two very short consecutive sentences may share a line.
- Keep periods in URLs, version numbers, and abbreviations within their sentence.
  Use a single sentence or a short phrase in Markdown table cells.

## PHP documentation

- Document model methods with a concise PHPDoc summary that explains their purpose and caller-visible behavior.
  Describe non-obvious parameters, return values, side effects, or failure cases where they matter.
  Use inline comments to explain decisions and invariants that the code alone cannot convey.
- Start PHP views with assertions that check the presence and type of inputs used in the default rendering path.
  Keep optional inputs optional and check branch-specific inputs only in their branch.
  Use assertions for template contracts and keep runtime validation in the controller or model.

## SQL

- Keep database definitions in the `Database` folder.
- Define reusable read queries with complex joins, subqueries, or aggregation as SQL views in `Database/views`.
  Query those views from PHP and keep request-specific filters and parameters in PHP.
- Configure collations in database and table definitions and at connection setup.
  Keep collation overrides out of queries, views, functions, and procedures.
- Write SQL compatible with MariaDB 12+ and MySQL 8+.
- Start each major clause on its own line, including `SELECT`, `FROM`, each `JOIN`, `WHERE`, `GROUP BY`, `HAVING`, `ORDER BY`, and `LIMIT`.
  Indent each `JOIN` one tab deeper than its corresponding `FROM`.
  Align the `ON` keywords for joins belonging to the same `FROM` in the same visual column.
  A short query of the form `` SELECT * FROM `table` `` may stay on one line.
- Always specify the join type explicitly using `INNER JOIN`, `CROSS JOIN`, `LEFT OUTER JOIN`, or `RIGHT OUTER JOIN`.
- Enclose table and column names in backticks, quoting each part of a qualified name separately, as in `` `table`.`id` ``.
  Table aliases may be unquoted in both their declaration and column qualifiers, as in `` SELECT t.`id` FROM `table` t ``.
- In long or complex queries that select multiple columns, put each select-list item on its own line.
  Align all select-list `AS` keywords in the same visual column.
- Enclose every column alias in double quotes, as in `` t.`id` AS "identifier" ``.
- Within each table definition, align the start of every column data type in the same visual column.
  Align the `REFERENCES` keywords and `ON DELETE` clauses across that table’s foreign key declarations.
- Use `CREATE TABLE` when the preceding statement drops the same table with `DROP TABLE IF EXISTS`.

## HTML

- Give every page a sharp, modern look consistent with the portal.
  Use clear visual hierarchy, consistent spacing, and responsive layouts.
- Use semantic elements for landmarks, headings, lists, tables, navigation, and controls.
  Use a button for an action and a link for navigation.
- Use Bootstrap floating labels (`form-floating`) for supported form controls.
  Keep each visible label associated with its control.
- When an opening tag has multiple attributes, put each attribute on its own line one indentation level deeper than the tag.
  For one- or two-character tag names such as `a`, `li`, `i` or `p`, the first attribute can stay on the opening line.
  Use spaces to align it with any attributes on the following lines, as in `<a  href="…"` or `<li class="…"`.
  Align the closing angle bracket with the start of the tag.
- Put nested elements on separate lines and indent them one level inside their parent.
  Preserve significant whitespace in elements such as `pre` and `textarea` and in inline text where a break would change its meaning or rendering.
- Give every button and link a descriptive `title` attribute.
  Give each control an accessible name through visible text or an appropriate label.

## CSS and JavaScript

- Use Bootstrap component and utility classes whenever they cover the required styling or behavior.
- Format project-owned CSS with a newline after every opening brace and before every closing brace.
  Indent declarations and nested blocks one level deeper than their enclosing block.
- Use jQuery Validation for client-side validation in every form.
  Keep server-side validation for submitted data.
- Use jQuery whenever it provides the needed functionality in project-owned frontend code.
  Use native browser APIs only for capabilities jQuery does not provide or for initialization before jQuery is available.
- Keep bundled third-party source and license files intact.
