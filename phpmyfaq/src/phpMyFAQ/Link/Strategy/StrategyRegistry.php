<?php

/**
 * Registry for action strategies; allows dynamic registration (plugin extension).
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Link\Strategy;

/**
 * Registry für Action-Strategien; erlaubt dynamisches Registrieren (Plugin-Erweiterung).
 */
final class StrategyRegistry
{
    /** @var array<string, StrategyInterface> */
    private array $strategies = [];

    public function __construct(?array $initial = null)
    {
        if ($initial) {
            foreach ($initial as $action => $strategy) {
                $this->register($action, $strategy);
            }
        }
    }

    public function register(string $action, StrategyInterface $strategy): void
    {
        $this->strategies[$action] = $strategy;
    }

    public function has(string $action): bool
    {
        return isset($this->strategies[$action]);
    }

    /**
     * @param string $action
     * @return StrategyInterface|null
     */
    public function get(string $action): ?StrategyInterface
    {
        return $this->strategies[$action] ?? null;
    }

    /**
     * Liefert alle registrierten Actions.
     * @return string[]
     */
    public function list(): array
    {
        return array_keys($this->strategies);
    }
}
