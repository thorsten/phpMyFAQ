# Project Overview

This project is a web application that allows users to manage FAQs. 
It is built using HTML5, CSS, TypeScript, and PHP and supports various databases for data storage.

## Folder Structure

- `/docs`: Contains documentation for the project, including API specifications and user guides.
- `/phpmyfaq/`: Contains the source code for the frontend.
- `/phpmyfaq/admin`: Contains the source code for the admin.
- `/phpmyfaq/admin/assets`: Contains the TypeScript and SCSS source files for the admin frontend.
- `/phpmyfaq/docs`: Contains the documentation for the project.
- `/phpmyfaq/assets`: Contains the TypeScript and SCSS source files for the frontend.
- `/phpmyfaq/src/phpMyFAQ`: Contains the source code for the PHP backend.
- `/tests`: Contains PHPUnit v12 based unittests.

## Development Setup

### Download Composer and install PHP dependencies

    curl -s https://getcomposer.org/installer | php
    php composer.phar install

### Install PNPM and install TypeScript dependencies

    curl -fsSL https://get.pnpm.io/install.sh | sh -
    pnpm install
    pnpm build

## Tech stack, libraries, and frameworks

- HTML 5, SCSS, TypeScript, Bootstrap, and Bootstrap Icons for the frontend. TypeScript in strict mode.
- PHP 8.4 and later with Symfony components for the backend.
- MySQL, PostgreSQL, SQLite3, and MS SQL for data storage. This option is configurable.
- Elasticsearch and OpenSearch for search functionality. This option is configurable.
- Apache, Nginx, and IIS as supported web servers. This option is configurable.
- It uses PNPM as the package manager for JavaScript/TypeScript dependencies.
- It uses Composer as the package manager for PHP dependencies.
- Twig as the templating engine.
- PHPUnit v12 for PHP-based unit testing, vitest for TypeScript-based unit testing.
- Docker for containerization.
- GitHub Actions for CI/CD.
- Mago for code quality and static analysis.

## Testing

- Always write tests for new features and bug fixes.
- Always run tests before committing code. All tests must pass. 
- PHP code: composer test
- PHP code with coverage: composer test:coverage
- PHP linting: composer lint
- TypeScript code: pnpm test
- TypeScript code with coverage: pnpm test:coverage
- TypeScript code in watch mode: pnpm test:watch
- TypeScript linting: pnpm lint
- TypeScript code formatting: pnpm lint:fix
- TypeScript errors have to be fixed before committing code.

## Building

- TypeScript and CSS build: pnpm build
- TypeScript and CSS build in watch mode: pnpm build:watch
- TypeScript and CSS production build: pnpm build:prod

## Coding Standards

- Use PER Coding Style 3.0 for PHP code.
- Use TypeScript coding standards for TypeScript code in strict mode.
- Use HTML5 and CSS3 standards for frontend code.
- Use semicolons at the end of each statement.
- Use single quotes for strings.
- Use arrow functions for callbacks.

## Routing System

The application uses Symfony Router with PHP 8+ Route attributes for modern, controller-based routing.

### Architecture

1. **Entry Points**:
   - `phpmyfaq/index.php`: Frontend entry point
   - `phpmyfaq/admin/index.php`: Admin panel entry point
   - `phpmyfaq/api/index.php`: API entry point
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
    // Parameters are automatically extracted from the URL
    $categoryId = $request->attributes->get('categoryId');
    $faqId = $request->attributes->get('faqId');
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
