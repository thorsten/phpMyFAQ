<?php

/**
 * The main glossary index file.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-15
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\DebugExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

if (
    $user->perm->hasPermission($user->getUserId(), PermissionType::GLOSSARY_ADD->value) ||
    $user->perm->hasPermission($user->getUserId(), PermissionType::GLOSSARY_EDIT->value) ||
    $user->perm->hasPermission($user->getUserId(), PermissionType::GLOSSARY_DELETE->value)
) {
    $glossary = new Glossary($faqConfig);

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('./admin/content/glossary.twig');

    $templateVars = [
        'adminHeaderGlossary' => Translation::get('ad_menu_glossary'),
        'msgAddGlossary' => Translation::get('ad_glossary_add'),
        'msgGlossaryItem' => Translation::get('ad_glossary_item'),
        'msgGlossaryDefinition' => Translation::get('ad_glossary_definition'),
        'glossaryItems' => $glossary->getAllGlossaryItems(),
        'buttonDelete' => Translation::get('ad_entry_delete'),
        'csrfTokenDelete' => Token::getInstance()->getTokenString('delete-glossary'),
        'addGlossaryTitle' => Translation::get('ad_glossary_add'),
        'addGlossaryCsrfTokenInput' => Token::getInstance()->getTokenInput('add-glossary'),
        'closeModal' => Translation::get('ad_att_close'),
        'saveModal' => Translation::get('ad_gen_save'),
    ];

    echo $template->render($templateVars);
} else {
    require 'no-permission.php';
}

        $csrfTokenFromPost = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

        if (
            $user->perm->hasPermission($user->getUserId(), PermissionType::GLOSSARY_ADD->value) ||
            $user->perm->hasPermission($user->getUserId(), PermissionType::GLOSSARY_EDIT->value) ||
            $user->perm->hasPermission($user->getUserId(), PermissionType::GLOSSARY_DELETE->value)
        ) {
            $glossary = new Glossary($faqConfig);

            if (
                'updateglossary' == $action &&
                $user->perm->hasPermission($user->getUserId(), PermissionType::GLOSSARY_EDIT->value) &&
                Token::getInstance()->verifyToken('edit-glossary', $csrfTokenFromPost)
            ) {
                $id = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $item = Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_SPECIAL_CHARS);
                $definition = Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_SPECIAL_CHARS);
                if ($glossary->updateGlossaryItem($id, $item, $definition)) {
                    echo Alert::success('ad_glossary_update_success');
                } else {
                    echo Alert::danger('ad_glossary_update_error', $faqConfig->getDb()->error());
                }
            }

        } else {
            require 'no-permission.php';
        }

