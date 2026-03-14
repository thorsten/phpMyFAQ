<?php

/**
 * Language detection and translation initialization listener
 *
 * Runs on kernel.request with high priority to set up language and translation
 * before any controller is invoked.
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
 * @since     2026-02-15
 */

declare(strict_types=1);

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class LanguageListener
{
    private bool $initialized = false;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @throws Exception
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $this->initialized) {
            return;
        }

        $this->initialized = true;

        $currentLanguage = $this->detectLanguage();
        $this->initializeTranslation($currentLanguage);
    }

    private function detectLanguage(): string
    {
        if (!$this->container->has('phpmyfaq.configuration') || !$this->container->has('phpmyfaq.language')) {
            return 'en';
        }

        /** @var Configuration $configuration */
        $configuration = $this->container->get(id: 'phpmyfaq.configuration');
        /** @var Language $language */
        $language = $this->container->get(id: 'phpmyfaq.language');

        $configuration->setContainer($this->container);

        $detect = (bool) $configuration->get(item: 'main.languageDetection');
        $configLang = $configuration->get(item: 'main.language');

        $currentLanguage = $detect
            ? $language->setLanguageWithDetection($configLang)
            : $language->setLanguageFromConfiguration($configLang);

        require_once PMF_TRANSLATION_DIR . '/language_en.php';
        if (Language::isASupportedLanguage($currentLanguage)) {
            require_once PMF_TRANSLATION_DIR . '/language_' . strtolower($currentLanguage) . '.php';
        }

        $configuration->setLanguage($language);

        return $currentLanguage;
    }

    /**
     * @throws Exception
     */
    private function initializeTranslation(string $currentLanguage): void
    {
        Strings::init($currentLanguage);

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage(defaultLanguage: 'en')
            ->setCurrentLanguage($currentLanguage)
            ->setMultiByteLanguage();
    }
}
