<?php

/**
 * Attribute-based route loader
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Routing;

use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RegexIterator;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class AttributeRouteLoader
 *
 * Scans controller directories for classes with #[Route] attributes
 * and builds a RouteCollection from discovered routes.
 */
class AttributeRouteLoader
{
    /**
     * Load routes from controller attributes in the specified directory.
     *
     * @param string $controllerDir The directory to scan for controllers
     * @param string $context The routing context ('public', 'admin', 'api')
     * @return RouteCollection Collection of discovered routes
     */
    public function load(string $controllerDir, string $context = 'public'): RouteCollection
    {
        $routes = new RouteCollection();

        if (!is_dir($controllerDir)) {
            return $routes;
        }

        $files = $this->findControllerFiles($controllerDir);

        foreach ($files as $file) {
            $class = $this->getClassFromFile($file);
            if (!$class) {
                continue;
            }

            try {
                $reflectionClass = new ReflectionClass($class);
                foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    $attributes = $method->getAttributes(Route::class);
                    foreach ($attributes as $attribute) {
                        $route = $this->createRouteFromAttribute($attribute, $class, $method->getName());
                        if ($route && $this->matchesContext($route, $context)) {
                            $name = $route->getDefault('_route') ?: 'route_' . md5($class . $method->getName());
                            $routes->add($name, $route);
                        }
                    }
                }
            } catch (ReflectionException) {
                // Skip classes that can't be reflected
                continue;
            }
        }

        return $routes;
    }

    /**
     * Find all PHP controller files in the directory.
     *
     * @param string $directory The directory to scan
     * @return array<string> Array of file paths
     */
    private function findControllerFiles(string $directory): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        $regexIterator = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($regexIterator as $file) {
            $files[] = $file[0];
        }

        return $files;
    }

    /**
     * Extract the fully qualified class name from a PHP file.
     *
     * @param string $file The file path
     * @return string|null The fully qualified class name or null if not found
     */
    private function getClassFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        // Extract namespace
        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        $className = null;
        if (preg_match('/(?:class|interface|trait)\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
        }

        if ($namespace && $className) {
            return $namespace . '\\' . $className;
        }

        return null;
    }

    /**
     * Create a Symfony Route from a Route attribute.
     *
     * @param ReflectionAttribute $attribute The Route attribute
     * @param string $class The controller class name
     * @param string $method The controller method name
     * @return SymfonyRoute|null The created route or null if invalid
     */
    private function createRouteFromAttribute(
        ReflectionAttribute $attribute,
        string $class,
        string $method,
    ): ?SymfonyRoute {
        try {
            /** @var Route $routeAttribute */
            $routeAttribute = $attribute->newInstance();

            // Extract route properties
            $path = $routeAttribute->path;
            $name = $routeAttribute->name ?? '';
            $methods = $routeAttribute->methods;
            $defaults = $routeAttribute->defaults;
            $requirements = $routeAttribute->requirements;
            $options = $routeAttribute->options;
            $host = $routeAttribute->host;
            $schemes = $routeAttribute->schemes;
            $condition = $routeAttribute->condition;

            // Set controller in defaults
            $defaults['_controller'] = $class . '::' . $method;

            // Create Symfony Route
            $route = new SymfonyRoute(
                path: $path,
                defaults: $defaults,
                requirements: $requirements,
                options: $options,
                host: $host,
                schemes: $schemes,
                methods: $methods,
                condition: $condition,
            );

            // Store the name in the route for later retrieval
            $route->setDefault('_route', $name);

            return $route;
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Check if a route matches the specified context.
     *
     * @param SymfonyRoute $route The route to check
     * @param string $context The context to match against
     * @return bool True if the route matches the context
     */
    private function matchesContext(SymfonyRoute $route, string $context): bool
    {
        $routeName = $route->getDefault('_route');

        if (!$routeName) {
            return true; // Allow routes without names
        }

        return match ($context) {
            'admin' => str_starts_with($routeName, 'admin.') && !str_starts_with($routeName, 'admin.api.'),
            'admin-api' => str_starts_with($routeName, 'admin.api.'),
            'api' => str_starts_with($routeName, 'api.') && !str_starts_with($routeName, 'admin.api.'),
            'public' => str_starts_with($routeName, 'public.'),
            default => true,
        };
    }
}
