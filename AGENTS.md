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

## HTML

- Use semantic elements for landmarks, headings, lists, tables, navigation, and controls.
  Use a button for an action and a link for navigation.
- When an opening tag has multiple attributes, put each attribute on its own line one indentation level deeper than the tag.
  For one- or two-character tag names such as `a` and `li`, the first attribute can stay on the opening line.
  Use spaces to align it with any attributes on the following lines, as in `<a  href="…"` or `<li class="…"`.
  Align the closing angle bracket with the start of the tag.
- Put nested elements on separate lines and indent them one level inside their parent.
  Preserve significant whitespace in elements such as `pre` and `textarea` and in inline text where a break would change its meaning or rendering.
- Give every button and link a descriptive `title` attribute.
  Give each control an accessible name through visible text or an appropriate label.

## CSS and JavaScript

- Format project-owned CSS with a newline after every opening brace and before every closing brace.
  Indent declarations and nested blocks one level deeper than their enclosing block.
- Prefer maintained libraries already used by the project, such as jQuery for DOM selection and event handling.
  Use native APIs for platform features and where a library is unavailable during initialization.
- Keep bundled third-party source and license files intact.
