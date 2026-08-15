# Project Overview

This project is a web application that allows users to manage FAQs. 
It is built using HTML5, CSS, TypeScript, and PHP and supports various databases for data storage.

## Folder Structure

- `/docs`: Contains documentation for the project, including API specifications and user guides.
- `/phpmyfaq/`: Contains the source code for the frontend.
- `/phpmyfaq/admin`: Contains the source code for the admin.
- `/phpmyfaq/admin/assets`: Contains the TypeScript and SCSS source files for the admin frontend. The tests are located in the same directory as the source files to ensure they are always updated together.
- `/phpmyfaq/assets`: Contains the TypeScript and SCSS source files for the frontend. The tests are located in the same directory as the source files to ensure they are always updated together.
- `/phpmyfaq/src/phpMyFAQ`: Contains the source code for the PHP backend.
- `/tests`: Contains PHPUnit v13 based unit and integration tests.
- `/tests/e2e`: Contains the Playwright based end-to-end tests.

## Development Setup

### Download Composer and install PHP dependencies

    curl -s https://getcomposer.org/installer | php
    php composer.phar install

### Install PNPM and install TypeScript dependencies

    curl -fsSL https://get.pnpm.io/install.sh | sh -
    pnpm install
    pnpm build

### Tool locations

Composer installs dependencies to `phpmyfaq/src/libs` (not `vendor/`).
CLI tools therefore live in `phpmyfaq/src/libs/bin/` (`phpunit`, `mago`, `rector`, etc.).

## Tech stack, libraries, and frameworks

- HTML 5, SCSS, TypeScript, Bootstrap, and Bootstrap Icons for the frontend. TypeScript in strict mode.
- PHP 8.4 and later with Symfony components for the backend.
- MySQL, MariaDB, PostgreSQL, SQLite3, and MS SQL for data storage. This option is configurable.
- Elasticsearch and OpenSearch for search functionality. This option is configurable.
- Apache, Nginx, IIS, and FrankenPHP as supported web servers. This option is configurable.
- It uses PNPM as the package manager for JavaScript/TypeScript dependencies.
- It uses Composer as the package manager for PHP dependencies.
- Twig as the templating engine.
- PHPUnit v13 for PHP-based unit testing, vitest for TypeScript-based unit testing.
- Docker for containerization.
- GitHub Actions for CI/CD.
- Mago for code quality and static analysis.

## Testing

- Always write tests for new features and bug fixes.
- Always run tests before committing code. All tests must pass. CI must stay green: do not introduce new warnings or errors.
- Linting and code formatting issues must be fixed before committing code.
- PHP code: composer test
- PHP single test file (much faster than the full suite): composer test -- tests/phpMyFAQ/Path/SomeTest.php
- PHP code with coverage: composer test:coverage
- PHP linting: composer lint
- PHP lint auto-fix: composer lint:fix
- PHP static analysis: composer analyze
- PHP formatting check: composer format:dry-run
- PHP formatting auto-fix: composer format
- PHP CI parity (format check + lint + static analysis, same as the GitHub Actions Mago step): composer check
- TypeScript code: pnpm test
- TypeScript code with coverage: pnpm test:coverage
- TypeScript code in watch mode: pnpm test:watch
- TypeScript type check: pnpm tsc
- TypeScript linting: pnpm oxlint
- TypeScript/JSON/YAML/HTML code formatting check: pnpm oxfmt
- TypeScript/JSON/YAML/HTML code formatting auto-fix: pnpm oxfmt:fix
- SCSS linting: pnpm stylelint
- SCSS lint auto-fix: pnpm stylelint:fix
- End-to-end tests (Playwright, fully automated setup via bin/e2e): pnpm e2e:local (SQLite + built-in PHP server) or pnpm e2e:docker (MariaDB container)
- TypeScript errors have to be fixed before committing code.

### Mago baselines

Mago lint and analyze run against baseline files (`mago-lint-baseline.toml`,
`mago-analyze-baseline.toml`) that suppress pre-existing findings. Editing a
baselined line invalidates its entry and resurfaces the old finding — fix the
finding rather than re-baselining it. Only error-level analyze findings fail CI;
regenerate the analyze baseline with `composer analyze:baseline` when entries go stale.

## Building

- TypeScript and CSS build: pnpm build
- TypeScript and CSS build in watch mode: pnpm build:watch
- TypeScript and CSS production build: pnpm build:prod

## Running the Application

`bin/dev` starts selectable Docker Compose development stacks (web server, database,
search engine — every service is gated behind a Compose profile):

- Start / stop: pnpm dev:up / pnpm dev:down
- Status / logs: pnpm dev:ps / pnpm dev:logs
- Presets: pnpm dev:default, pnpm dev:full

## Git Hooks and Commit Messages

- Commit messages must follow Conventional Commits (`fix:`, `feat:`, `test:`, `chore:`, `docs:`, `refactor:`, ...);
  the commit-msg hook runs commitlint and rejects non-conforming messages.
- The pre-commit hook runs the full check pipeline (Mago format/lint/analyze, composer validate,
  PHPUnit, oxfmt, oxlint, stylelint, tsc, vitest). It takes a few minutes — that is expected, not a hang.
- The pre-push hook runs composer validate and both test suites again.

## Coding Standards

- Use PER Coding Style 3.0 for PHP code.
- Use TypeScript coding standards for TypeScript code in strict mode.
- Use HTML5 and CSS3 standards for frontend code.
- Use semicolons at the end of each statement.
- Use single quotes for strings.
- Use arrow functions for callbacks.
- Always add the copyright header to new files, but not to test files.

## Coding Patterns

- Prefer guard clauses and early returns over deep conditional nesting
- Name classes and methods after domain concepts, not technical mechanics (e.g. `Subscription.renew()`, not `DataRecord.update()`)
- Isolate external dependencies behind an interface you own, so they can be swapped or mocked at the boundary
- Make illegal states unrepresentable — encode invariants in types and constructors instead of re-validating them everywhere
- Separate decisions (pure logic that computes *what* to do) from actions (side effects that carry it out)
- Keep functions small and single-purpose
- Fail loudly and specifically: errors state what went wrong, where, and what to do next — structured enough for machines to parse, readable enough for humans to act on
- Implement only what the task requires — no speculative abstractions, extra options, or "future-proofing" that wasn't asked for
- Before writing new code, check whether an existing class or helper already does the job
- Duplication is acceptable until a real third use case appears; don't build abstractions from two examples
- Never fix a failing test by deleting, skipping, or loosening it — fix the code, or state explicitly why the test's expectation is wrong
- Any change to the database schema must ship with a corresponding upgrade step in `phpMyFAQ\Setup\Update`
- Keep unrelated refactoring, reformatting, and renaming out of a change — separate commits for separate concerns
- When in doubt, mirror the patterns, naming, and structure of the surrounding code rather than introducing a new style

## Making illegal states unrepresentable in PHP

- Use `enum` for fixed sets instead of string/int constants or magic values (e.g. `enum FaqStatus { case Draft; case Published; case Archived; }`)
- Push validation into the constructor and throw on bad input, so an object cannot exist in an invalid state — never construct first and validate later
- Use named constructors (private `__construct` + static `fromString()`, `create()`) when there are several distinct, valid ways to build an object
- Make value objects `readonly` so invariants checked at construction can't be mutated away afterward (`final readonly class EmailAddress`)
- Prefer required, typed, non-nullable constructor arguments over nullable properties with setters; if a value is optional, model that explicitly rather than leaning on `null`
- Replace pervasive `null` checks with a Null Object or a typed result/option where it removes branching
- Mark classes `final` by default; open them for extension only when you mean to

## Agent Workflow

When implementing changes:

1. Read existing code before modifying it.
2. Run `composer check` / `pnpm oxlint` after PHP/TypeScript changes — `composer lint` alone does not run the static analysis that CI enforces.
3. Run `composer test` / `pnpm test` — all tests must pass before finishing.
4. Never commit with `--no-verify`.
5. Clear route cache (`rm -rf phpmyfaq/cache/routes`) after adding or modifying routes.

## Dependency Injection

Services are registered in `phpmyfaq/src/services.php`.
Add new services there when creating classes that need constructor injection.

## Templates

- Frontend templates: `phpmyfaq/assets/templates/default/`
- Admin templates: `phpmyfaq/assets/templates/admin/`
- Setup / Update templates: `phpmyfaq/assets/templates/setup/`
- Fatal error templates: `phpmyfaq/assets/templates/error/`
- All templates use Twig syntax. Never use raw PHP for HTML output.

## Do Not

- Do not add inline SQL — use the existing DB abstraction layer.
- Do not hard-code strings — use the translation system.
- Do not bypass CSRF protection on form handlers.
- Do not add new npm/composer dependencies without checking for existing alternatives.

## Routing System

The application uses Symfony Router with PHP 8+ Route attributes for modern, controller-based routing.

### Architecture

1. **Entry Points**:
   - `phpmyfaq/index.php`: Frontend entry point
   - `phpmyfaq/admin/index.php`: Admin panel entry point
   - `phpmyfaq/api/index.php`: API entry point
   - `phpmyfaq/admin/api/index.php`: Admin API entry point
2. **AttributeRouteLoader**: Automatically discovers routes from controller #[Route] attributes
3. **RouteCollectionBuilder**: Builds route collections for different contexts (public, admin, api, admin-api)
4. **RouteCacheManager**: Caches compiled routes for production performance
5. **Controllers**: Modern Controller classes extending AbstractController
6. **services.php**: Dependency injection configuration for services and classes

### Adding New Routes

All routes are defined using PHP 8+ #[Route] attributes directly on controller methods. No separate route definition files are needed.

To add a new route:

1. Create a Controller in the appropriate directory:
   - Frontend routes: `phpmyfaq/src/phpMyFAQ/Controller/Frontend/`
   - Admin routes: `phpmyfaq/src/phpMyFAQ/Controller/Administration/`
   - API routes: `phpmyfaq/src/phpMyFAQ/Controller/Api/`
   - Admin API routes: `phpmyfaq/src/phpMyFAQ/Controller/Administration/Api/`
2. Add the #[Route] attribute to your controller method
3. The Controller should extend `AbstractController` (or `AbstractAdministrationApiController` for admin API)

Example:

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MyController extends AbstractController
{
    #[Route(path: '/my-page.html', name: 'public.my-page', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->render('template.twig', ['data' => 'value']);
    }
}
```

### Route Naming Conventions

- **Frontend routes**: `public.{resource}.{action}` (e.g., `public.faq.show`, `public.user.register`)
- **Admin routes**: `admin.{resource}.{action}` (e.g., `admin.faq.edit`, `admin.category.add`)
- **API routes**: `api.{resource}.{action}` (e.g., `api.search`, `api.faqs.list`)
- **Admin API routes**: `admin.api.{resource}.{action}` (e.g., `admin.api.faq.create`)

### Route Parameters

Use curly braces `{param}` for route parameters:

```php
#[Route(path: '/faq/{categoryId}/{faqId}', name: 'public.faq.show', methods: ['GET'])]
public function show(Request $request, int $categoryId, int $faqId): Response
{
    // {categoryId} and {faqId} are injected as typed method arguments
    // ...
}
```

### Route Caching

Route caching improves performance by caching the compiled route collection, eliminating the need to scan controllers and use reflection on every request.

**Configuration via Environment Variables:**

Create a `.env` file in `phpmyfaq/` directory (copy from `.env.example`):

```env
# Enable route caching in production for ~98% performance improvement
ROUTING_CACHE_ENABLED=true

# Cache directory is automatically set to {PMF_ROOT_DIR}/cache/routes
# Only override if you need a custom location (must be an absolute path)
# ROUTING_CACHE_DIR=/custom/path/to/cache
```

**Behavior:**
- **Production**: Routes are cached to PHP files, loaded instantly on subsequent requests
- **Development/Debug Mode**: Cache is automatically disabled (DEBUG=true) for immediate route changes
- **Performance**: ~98% faster route loading (21ms → 0.45ms for 39 routes)

**Cache Management:**

The cache is automatically cleared when:
- Debug mode is enabled
- The environment variable `ROUTING_CACHE_ENABLED` is set to `false`

To manually clear the route cache, delete the cache directory:
```bash
rm -rf phpmyfaq/cache/routes
```

## UI guidelines

- Application should have a modern and clean design.
- Use Bootstrap components and utilities for layout and styling.
- Ensure the application is responsive and works well on different screen sizes.
- Follow accessibility best practices to ensure the application is usable by all users.
- Use consistent colors, fonts, and spacing throughout the application.
- Use meaningful icons and images to enhance the user experience.
- Provide clear and concise error messages and feedback to users.

## Translation and Localization

- Use the built-in translation features to support multiple languages.
- Store translation files in ./phpmyfaq/translations/ directory.
- Use UTF-8 encoding for all translation files to support special characters.
- English is the default language.
- Follow best practices for localization, such as using placeholders for dynamic content and avoiding hard-coded strings.
- Test the application in different languages to ensure proper rendering and functionality.
- Encourage community contributions for translations.
