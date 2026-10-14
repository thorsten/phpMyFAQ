<?php

/**
 * The configured color scheme ("layout mode") of the public and admin layout.
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
 * @since     2026-10-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;

final readonly class LayoutMode
{
    public const string AUTO = 'auto';

    private function __construct(
        public string $default,
        public bool $allowUserChoice,
        public string $force,
    ) {
    }

    public static function fromConfiguration(Configuration $configuration): self
    {
        $allowUserChoice = $configuration->get('layout.allowUserLayoutMode');

        return new self(
            default: self::modeOrAuto($configuration->get('layout.defaultLayoutMode')),
            allowUserChoice: $allowUserChoice === true || $allowUserChoice === 'true',
            force: self::modeOrAuto($configuration->get('layout.forceLayoutMode')),
        );
    }

    /**
     * The mode every visitor gets when the administrator mandates one (layout.forceLayoutMode
     * other than 'auto'); null otherwise. A forced mode overrides the default mode and the
     * visitor choice, is rendered server-side and needs no client-side switcher.
     */
    public function forced(): ?string
    {
        return $this->force === self::AUTO ? null : $this->force;
    }

    /**
     * The mode rendered into data-bs-theme before any script runs. In automatic mode
     * the fallback is used until the inline script resolves the visitor preference.
     */
    public function initialTheme(string $fallback = 'light'): string
    {
        $forced = $this->forced();
        if ($forced !== null) {
            return $forced;
        }

        return $this->default === self::AUTO ? $fallback : $this->default;
    }

    private static function modeOrAuto(mixed $mode): string
    {
        return is_string($mode) && $mode !== '' ? $mode : self::AUTO;
    }

    /**
     * @return array{defaultLayoutMode: string, allowUserLayoutMode: bool, forcedLayoutMode: ?string}
     */
    public function toTemplateData(): array
    {
        return [
            'defaultLayoutMode' => $this->default,
            'allowUserLayoutMode' => $this->allowUserChoice,
            'forcedLayoutMode' => $this->forced(),
        ];
    }
}
