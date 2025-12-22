<?php

/**
 * Manages all language stuff.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo scaramuccia <matteo@phpmyfaq.de>
 * @author    Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-05-14
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Language\LanguageDetector;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class Language
 *
 * @package phpMyFAQ
 */
class Language
{
    /**
     * The current language.
     */
    public static string $language = '';

    /**
     * Detector helper.
     */
    private LanguageDetector $detector;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly Configuration $configuration,
        private readonly SessionInterface $session,
    ) {
        $this->detector = new LanguageDetector($this->configuration, $this->session);
    }

    /**
     * Returns an array of country codes for a specific FAQ record ID,
     * specific category ID or all languages used by FAQ records, categories.
     *
     * @param int    $identifier ID
     * @param string $table      Specifies table
     * @return string[]
     */
    public function isLanguageAvailable(int $identifier, string $table = 'faqdata'): array
    {
        $output = [];

        // Avoid it else: default values for all records, override if identifier is set
        $distinct = 'DISTINCT ';
        $where = '';
        if ($identifier !== 0) {
            $distinct = '';
            $where = ' WHERE id = ' . $identifier;
        }

        // Correct spacing: "SELECT" then optional DISTINCT
        $query = 'SELECT ' . $distinct . 'lang FROM ' . Database::getTablePrefix() . $table . $where;
        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            while (true) {
                $row = $this->configuration->getDb()->fetchObject($result);
                if (!$row) {
                    break;
                }
                $output[] = $row->lang;
            }
        }

        return $output;
    }

    /**
     * Sets language using browser detection combined with config fallback.
     */
    public function setLanguageWithDetection(string $configLanguage): string
    {
        $detected = $this->detector->detectAllWithBrowser($configLanguage);
        self::$language = $this->detector->selectLanguage($detected);
        $this->session->set(name: 'lang', value: self::$language);
        return strtolower(self::$language);
    }

    /**
     * Sets language only from the provided configuration string.
     */
    public function setLanguageFromConfiguration(string $configLanguage): string
    {
        $detected = $this->detector->detectAllFromConfig($configLanguage);
        self::$language = $this->detector->selectLanguage($detected);
        $this->session->set(name: 'lang', value: self::$language);
        return strtolower(self::$language);
    }

    public function setLanguageByAcceptLanguage(): string
    {
        $this->detector->detectAllWithBrowser(configLanguage: '');
        return self::$language = $this->detector->getAcceptLanguage();
    }

    /**
     * True if the language is supported by the current phpMyFAQ installation.
     *
     * @param string|null $langCode Language code
     */
    public static function isASupportedLanguage(?string $langCode): bool
    {
        return $langCode !== null && LanguageCodes::getSupported($langCode) !== null;
    }

    /**
     * Returns the current language.
     */
    public function getLanguage(): string
    {
        return strtolower(self::$language);
    }
}
