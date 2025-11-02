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
- PHP 8.3 and later with Symfony components for the backend.
- MySQL, PostgreSQL, SQLite3, and MS SQL for data storage. This option is configurable.
- Elasticsearch and OpenSearch for search functionality. This option is configurable.
- Apache, Nginx, and IIS as supported web servers. This option is configurable.
- It uses PNPM as the package manager for JavaScript/TypeScript dependencies.
- It used Composer as the package manager for PHP dependencies.
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

## Building

- TypeScript build: pnpm build
- TypeScript build in watch mode: pnpm build:watch
- TypeScript production build: pnpm build:prod

## Coding Standards

- Use PSR-12 coding standards for PHP code.
- Use TypeScript coding standards for TypeScript code.
- Use HTML5 and CSS3 standards for frontend code.
- Use semicolons at the end of each statement.
- Use single quotes for strings.
- Use arrow functions for callbacks.

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
