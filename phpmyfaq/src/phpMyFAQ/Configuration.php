<?php

/**
 * The main class for fetching the configuration, update and delete items. This
 * class is also a small Dependency Injection Container for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-01-04
 */

declare(strict_types=1);

namespace phpMyFAQ;

use Monolog\Logger;
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Configuration\SecuritySettings;
use phpMyFAQ\Configuration\UrlSettings;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Plugin\PluginException;
use phpMyFAQ\Plugin\PluginManager;

/**
 * Class Configuration
 *
 * @package phpMyFAQ
 */
class Configuration
{
    use ConfigurationMethodsTrait;

    private array $config = [];

    private Logger $logger;

    private static ?Configuration $configuration = null;

    protected string $tableName = 'faqconfig';

    private PluginManager $pluginManager;

    private ConfigurationRepository $configurationRepository;

    private LdapSettings $ldapSettings;

    private MailSettings $mailSettings;

    private SearchSettings $searchSettings;

    private SecuritySettings $securitySettings;

    private LayoutSettings $layoutSettings;

    private UrlSettings $urlSettings;

    public function __construct(DatabaseDriver $databaseDriver)
    {
        $this->setDatabase($databaseDriver);
        $this->setLogger();
        try {
            $this->setPluginManager();
        } catch (PluginException $pluginException) {
            $this->getLogger()->error($pluginException->getMessage());
        }

        $this->configurationRepository = new ConfigurationRepository($this);
        $this->ldapSettings = new LdapSettings($this);
        $this->mailSettings = new MailSettings($this);
        $this->searchSettings = new SearchSettings($this);
        $this->securitySettings = new SecuritySettings($this);
        $this->layoutSettings = new LayoutSettings($this);
        $this->urlSettings = new UrlSettings($this);

        if (is_null(self::$configuration)) {
            self::$configuration = $this;
        }
    }

    public static function getConfigurationInstance(): Configuration
    {
        return self::$configuration;
    }
}
