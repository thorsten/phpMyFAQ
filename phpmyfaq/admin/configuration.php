<?php

/**
 * The main configuration frontend.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-26
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    $userAction = Filter::filterInput(INPUT_GET, 'config_action', FILTER_SANITIZE_SPECIAL_CHARS, 'listConfig');
    $csrfToken = Filter::filterInput(INPUT_POST, 'pmf-csrf-token', FILTER_SANITIZE_SPECIAL_CHARS);

    // Save the configuration
    if ('saveConfig' === $userAction && Token::getInstance()->verifyToken('configuration', $csrfToken)) {
        $checks = [
            'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
            'flags' => FILTER_REQUIRE_ARRAY,
        ];
        $editData = Filter::filterInputArray(INPUT_POST, ['edit' => $checks]);
        $userAction = 'listConfig';
        $oldConfigValues = $faqConfig->getAll();

        // Set the new values
        $forbiddenValues = ['{', '}'];
        $newConfigValues = [];
        $escapeValues = [
            'main.contactInformation',
            'main.customPdfHeader',
            'main.customPdfFooter',
            'main.titleFAQ',
            'main.metaKeywords'
        ];

        // Special checks
        if (isset($editData['edit']['main.enableMarkdownEditor'])) {
            $editData['edit']['main.enableWysiwygEditor'] = false; // Disable WYSIWYG editor if Markdown is enabled
        }
        if (isset($editData['edit']['main.currentVersion'])) {
            unset($editData['edit']['main.currentVersion']); // don't update the version number
        }

        if (
            isset($editData['edit']['main.referenceURL']) &&
            is_null(Filter::filterVar($editData['edit']['main.referenceURL'], FILTER_VALIDATE_URL))
        ) {
            unset($editData['edit']['main.referenceURL']);
        }

        $newConfigClass = [];

        foreach ($editData['edit'] as $key => $value) {
            // Remove forbidden characters
            $newConfigValues[$key] = str_replace($forbiddenValues, '', (string) $value);
            // Escape some values
            if (isset($escapeValues[$key])) {
                $newConfigValues[$key] = Strings::htmlspecialchars($value, ENT_QUOTES);
            }
            $keyArray = array_values(explode('.', (string) $key));
            $newConfigClass = array_shift($keyArray);
        }

        foreach ($oldConfigValues as $key => $value) {
            $keyArray = array_values(explode('.', (string) $key));
            $oldConfigClass = array_shift($keyArray);
            if (isset($newConfigValues[$key])) {
                continue;
            } else {
                if ($oldConfigClass === $newConfigClass && $value === 'true') {
                    $newConfigValues[$key] = 'false';
                } else {
                    $newConfigValues[$key] = $value;
                }
            }
        }

        $faqConfig->update($newConfigValues);

        $faqConfig->getAll();
    }

    $twig = new TwigWrapper('./assets/templates');
    $template = $twig->loadTemplate('./configuration/main.twig');

    $templateVars = [
        'adminHeaderConfiguration' => Translation::get('ad_config_edit'),
        'csrfToken' => Token::getInstance()->getTokenString('configuration'),
        'adminConfigurationButtonReset' => Translation::get('ad_config_reset'),
        'adminConfigurationButtonSave' => Translation::get('ad_config_save'),
        'adminConfigurationMainTab' => Translation::get('mainControlCenter'),
        'adminConfigurationFaqsTab' => Translation::get('recordsControlCenter'),
        'adminConfigurationSearchTab' => Translation::get('searchControlCenter'),
        'adminConfigurationSecurityTab' => Translation::get('securityControlCenter'),
        'adminConfigurationSpamTab' => Translation::get('spamControlCenter'),
        'adminConfigurationSeoTab' => Translation::get('seoCenter'),
        'adminConfigurationMailTab' => Translation::get('mailControlCenter'),
        'adminConfigurationUpgradeTab' => Translation::get('upgradeControlCenter'),
    ];

    echo $template->render($templateVars);
} else {
    require 'no-permission.php';
}
