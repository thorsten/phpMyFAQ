<?php

/**
 * Main update script.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Melchinger <t.melchinger@uni.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-01-10
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Filter;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use Symfony\Component\HttpFoundation\RedirectResponse;

const COPYRIGHT = '&copy; 2001-2024 <a target="_blank" href="//www.phpmyfaq.de/">phpMyFAQ Team</a>';
const IS_VALID_PHPMYFAQ = null;

define('PMF_ROOT_DIR', dirname(__FILE__, 2));

if (version_compare(PHP_VERSION, '8.2.0') < 0) {
    die('Sorry, but you need PHP 8.2.0 or later!');
}

set_time_limit(0);

require PMF_ROOT_DIR . '/src/Bootstrap.php';

Strings::init();

$system = new System();
$faqConfig = Configuration::getConfigurationInstance();

$step = Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version = Filter::filterInput(INPUT_POST, 'installed-version', FILTER_SANITIZE_SPECIAL_CHARS);

$installedVersion = $faqConfig->getVersion();

$update = new Update($system, $faqConfig);
$update->setVersion($installedVersion);

if (!$update->checkDatabaseFile()) {
    $redirect = new RedirectResponse('./index.php');
    $redirect->send();
}

try {
    $dbConfig = new DatabaseConfiguration(PMF_ROOT_DIR . '/config/database.php');
} catch (ErrorException $e) {
    $dbConfig = new DatabaseConfiguration(PMF_ROOT_DIR . '/content/core/config/database.php');
}

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('./setup/update.twig');

$templateVars = [
    'newVersion' => System::getVersion(),
    'installedVersion' => $installedVersion,
    'currentYear' => date('Y'),
    'currentStep' => $step,
    'documentationUrl' => System::getDocumentationUrl(),
    'configTableNotAvailable' => $update->isConfigTableAvailable($faqConfig->getDb()),
];

echo $template->render($templateVars);
