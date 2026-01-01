<?php

/**
 * The language detector class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Language;

use phpMyFAQ\Configuration;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LanguageDetector
{
    private string $acceptLanguage = '';

    public function __construct(
        private readonly Configuration $configuration,
        private readonly SessionInterface $session,
    ) {
    }

    /** @return array<string, string|null> */
    public function detectAllWithBrowser(string $configLanguage): array
    {
        $this->initUserAgentLanguage();
        return $this->aggregateWithBrowser($configLanguage);
    }

    /** @return array<string, string|null> */
    public function detectAllFromConfig(string $configLanguage): array
    {
        return $this->aggregateWithoutBrowser($configLanguage);
    }

    public function getAcceptLanguage(): string
    {
        return $this->acceptLanguage;
    }

    /**
     * Wählt die erste gültige Sprache aus der ermittelten Liste in definierter Priorität
     * und fällt auf 'en' zurück, falls keine gefunden wurde.
     *
     * Priorität: POST > GET(lang) > GET(artlang) > Session > Config > Browser-Erkennung
     *
     * @param array<string, string|null> $detected
     */
    public function selectLanguage(array $detected): string
    {
        $order = ['post', 'get', 'artget', 'session', 'config', 'detection'];
        foreach ($order as $key) {
            $lang = $detected[$key] ?? null;
            if ($lang !== null && Language::isASupportedLanguage($lang)) {
                return strtolower($lang);
            }
        }
        return 'en';
    }

    /** @return array<string, string|null> */
    private function aggregateWithBrowser(string $configLanguage): array
    {
        return [
            'post' => $this->fetchFilteredLanguage(INPUT_POST, variable: 'language'),
            'get' => $this->fetchFilteredLanguage(INPUT_GET, variable: 'lang'),
            'artget' => $this->fetchFilteredLanguage(INPUT_GET, variable: 'artlang'),
            'session' => $this->getSessionLanguage(),
            'config' => $this->getConfigLanguage($configLanguage),
            'detection' => $this->getDetectionLanguage(),
        ];
    }

    /** @return array<string, string|null> */
    private function aggregateWithoutBrowser(string $configLanguage): array
    {
        return [
            'post' => $this->fetchFilteredLanguage(INPUT_POST, variable: 'language'),
            'get' => $this->fetchFilteredLanguage(INPUT_GET, variable: 'lang'),
            'artget' => $this->fetchFilteredLanguage(INPUT_GET, variable: 'artlang'),
            'session' => $this->getSessionLanguage(),
            'config' => $this->getConfigLanguage($configLanguage),
            'detection' => null,
        ];
    }

    private function fetchFilteredLanguage(int $inputType, string $variable): ?string
    {
        $lang = Filter::filterInput($inputType, variableName: $variable, filter: FILTER_SANITIZE_SPECIAL_CHARS);
        return Language::isASupportedLanguage($lang) ? $lang : null;
    }

    private function getSessionLanguage(): ?string
    {
        $lang = $this->session->get(name: 'lang');
        return Language::isASupportedLanguage($lang) ? trim((string) $lang) : null;
    }

    private function getConfigLanguage(string $configLanguage): ?string
    {
        $lang = str_replace(['language_', '.php'], replace: '', subject: $configLanguage);
        return Language::isASupportedLanguage($lang) ? $lang : null;
    }

    private function getDetectionLanguage(): ?string
    {
        return Language::isASupportedLanguage(strtoupper($this->acceptLanguage))
            ? strtolower($this->acceptLanguage)
            : null;
    }

    private function initUserAgentLanguage(): void
    {
        $languages = Request::createFromGlobals()->getLanguages();
        foreach ($languages as $language) {
            if (!Language::isASupportedLanguage(strtoupper($language))) {
                continue;
            }

            $this->acceptLanguage = strtolower($language);
            break;
        }
        if ($this->acceptLanguage === '') {
            foreach ($languages as $language) {
                $short = substr($language, offset: 0, length: 2);
                if (Language::isASupportedLanguage(strtoupper($short))) {
                    $this->acceptLanguage = strtolower($short);
                    break;
                }
            }
        }
    }
}
