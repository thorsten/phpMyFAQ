<?php

/**
 * phpMyFAQ main router class.
 *
 * @note This class will be used in a future release.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-17
 */

namespace phpMyFAQ\Core;

/**
 * Class Router
 *
 * @package phpMyFAQ\Core
 */
class Router
{
    /** @var array Array with routes */
    private $routes = [];

    /** @var array Parameters of the matched route */
    private $parameters = [];

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return Router
     */
    public function setParameters(array $parameters): Router
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Adds a route with parameters to the array of routes.
     * @param string $route
     * @param array  $parameters
     */
    public function add(string $route, array $parameters = [])
    {
        // escape slashes to avoid issues in the routes
        $route = preg_replace('/\//', '\\/', $route);
        // added delimiters for matching URLs
        $route = '/^' . $route . '$/i';

        $this->routes[$route] = $parameters;
    }

    /**
     * Match the route in the URL with the known routes and adds the
     * parameter if the route matched.
     * @param string $url
     * @return bool
     */
    public function match(string $url): bool
    {
        foreach ($this->routes as $route => $parameters) {
            if (preg_match($route, $url, $matches)) {
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $parameters[$key] = $match;
                    }
                }

                $this->setParameters($parameters);
                return true;
            }
        }

        return false;
    }
}
