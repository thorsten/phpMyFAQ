<?php

/**
 * Route collection builder
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

use phpMyFAQ\Configuration;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteCollectionBuilder
 *
 * Builds a RouteCollection by merging file-based routes with attribute-based routes.
 * During migration, both sources are supported. After migration, only attributes are used.
 */
class RouteCollectionBuilder
{
    private AttributeRouteLoader $attributeLoader;

    public function __construct(
        private readonly ?Configuration $configuration = null,
    ) {
        $this->attributeLoader = new AttributeRouteLoader();
    }

    /**
     * Build a RouteCollection for the specified context.
     *
     * @param string $context The routing context ('public', 'admin', 'admin-api', 'api')
     * @param bool $attributesOnly If true, only load routes from attributes (skip file-based routes)
     * @return RouteCollection The merged route collection
     */
    public function build(string $context, bool $attributesOnly = false): RouteCollection
    {
        $collection = new RouteCollection();

        // Load file-based routes during migration (unless attributesOnly is true)
        if (!$attributesOnly) {
            $fileRoutes = $this->loadFileRoutes($context);
            if ($fileRoutes) {
                $collection->addCollection($fileRoutes);
            }
        }

        // Load attribute-based routes (these override file routes if there's a conflict)
        $attributeRoutes = $this->loadAttributeRoutes($context);
        $collection->addCollection($attributeRoutes);

        return $collection;
    }

    /**
     * Load routes from PHP files.
     *
     * @param string $context The routing context
     * @return RouteCollection|null The file-based routes or null if file doesn't exist
     */
    private function loadFileRoutes(string $context): ?RouteCollection
    {
        $routeFile = match ($context) {
            'public' => PMF_SRC_DIR . '/public-routes.php',
            'admin' => PMF_SRC_DIR . '/admin-routes.php',
            'admin-api' => PMF_SRC_DIR . '/admin-api-routes.php',
            'api' => PMF_SRC_DIR . '/api-routes.php',
            default => null,
        };

        if ($routeFile && file_exists($routeFile)) {
            return include $routeFile;
        }

        return null;
    }

    /**
     * Load routes from controller attributes.
     *
     * @param string $context The routing context
     * @return RouteCollection The attribute-based routes
     */
    private function loadAttributeRoutes(string $context): RouteCollection
    {
        $controllerDirs = $this->getControllerDirectories($context);
        $routes = new RouteCollection();

        foreach ($controllerDirs as $dir) {
            if (is_dir($dir)) {
                $dirRoutes = $this->attributeLoader->load($dir, $context);
                $routes->addCollection($dirRoutes);
            }
        }

        return $routes;
    }

    /**
     * Get controller directories for the specified context.
     *
     * @param string $context The routing context
     * @return array<string> Array of controller directory paths
     */
    private function getControllerDirectories(string $context): array
    {
        $baseDir = PMF_SRC_DIR . '/phpMyFAQ/Controller';

        return match ($context) {
            'public' => [
                $baseDir . '/Frontend',
            ],
            'admin' => [
                $baseDir . '/Administration',
            ],
            'admin-api' => [
                $baseDir . '/Administration/Api',
            ],
            'api' => [
                $baseDir . '/Api',
                $baseDir . '/Frontend/Api',
            ],
            default => [$baseDir],
        };
    }

    /**
     * Get the configuration option for attribute-only mode.
     *
     * @return bool True if only attributes should be used
     */
    private function isAttributesOnly(): bool
    {
        if ($this->configuration === null) {
            return false;
        }

        return (bool) $this->configuration->get('routing.useAttributesOnly');
    }
}
