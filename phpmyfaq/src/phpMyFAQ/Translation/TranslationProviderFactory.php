<?php

/**
 * Factory for creating translation provider instances.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-17
 */

namespace phpMyFAQ\Translation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation\Provider\AmazonTranslationProvider;
use phpMyFAQ\Translation\Provider\AzureTranslationProvider;
use phpMyFAQ\Translation\Provider\DeepLTranslationProvider;
use phpMyFAQ\Translation\Provider\GoogleTranslationProvider;
use phpMyFAQ\Translation\Provider\LibreTranslationProvider;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class TranslationProviderFactory
 *
 * Factory for creating translation provider instances based on configuration.
 */
class TranslationProviderFactory
{
    /**
     * Create a translation provider instance.
     *
     * @param Configuration $configuration phpMyFAQ configuration
     * @param HttpClientInterface $httpClient HTTP client for API requests
     * @return TranslationProviderInterface|null Provider instance or null if none configured
     */
    public static function create(
        Configuration $configuration,
        HttpClientInterface $httpClient,
    ): ?TranslationProviderInterface {
        $provider = $configuration->get('translation.provider');

        return match ($provider) {
            'google' => new GoogleTranslationProvider($configuration, $httpClient),
            'deepl' => new DeepLTranslationProvider($configuration, $httpClient),
            'azure' => new AzureTranslationProvider($configuration, $httpClient),
            'amazon' => new AmazonTranslationProvider($configuration, $httpClient),
            'libretranslate' => new LibreTranslationProvider($configuration, $httpClient),
            default => null,
        };
    }
}
