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
 * Builds a RouteCollection from controller attributes using PHP 8+ Route attributes.
 * All routes are defined using #[Route] attributes directly on controller methods.
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
     * @param bool $attributesOnly Deprecated parameter kept for backward compatibility (always true now)
     * @return RouteCollection The route collection loaded from controller attributes
     */
    public function build(string $context, bool $attributesOnly = true): RouteCollection
    {
        // Load routes from controller attributes
        return $this->loadAttributeRoutes($context);
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
            if (!is_dir($dir)) {
                continue;
            }

            $dirRoutes = $this->attributeLoader->load($dir, $context);
            $routes->addCollection($dirRoutes);
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
}
