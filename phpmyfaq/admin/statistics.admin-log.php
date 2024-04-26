<?php

/**
 * Overview of actions in the admin section.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Template\UserNameTwigExtension;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Twig\Extension\DebugExtension;
use Twig\Extra\Intl\IntlExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), PermissionType::STATISTICS_ADMINLOG->value)) {
    $logging = new AdminLog($faqConfig);

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $twig->addExtension(new IntlExtension());
    $twig->addExtension(new UserNameTwigExtension());
    $twig->addExtension(new DebugExtension());
    $template = $twig->loadTemplate('./admin/statistics/admin-log.twig');

    $date = new Date($faqConfig);
    $perPage = 15;
    $pages = Filter::filterInput(INPUT_GET, 'pages', FILTER_VALIDATE_INT);
    $page = Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 1);

    if (is_null($pages)) {
        $pages = round(($logging->getNumberOfEntries() + ($perPage / 3)) / $perPage, 0);
    }

    $start = ($page - 1) * $perPage;
    $lastPage = $start + $perPage;

    $baseUrl = sprintf('%sadmin/?action=adminlog&amp;page=%d', $faqConfig->getDefaultUrl(), $page);

    // Pagination options
    $options = [
        'baseUrl' => $baseUrl,
        'total' => $logging->getNumberOfEntries(),
        'perPage' => $perPage,
        'pageParamName' => 'page',
    ];
    $pagination = new Pagination($options);

    $loggingData = $logging->getAll();

    $templateVars = [
        'headerAdminLog' => Translation::get('ad_menu_adminlog'),
        'buttonDeleteAdminLog' => Translation::get('ad_adminlog_del_older_30d'),
        'csrfDeleteAdminLogToken' => Token::getInstance()->getTokenString('delete-adminlog'),
        'currentLocale' => $faqConfig->getLanguage()->getLanguage(),
        'pagination' => $pagination->render(),
        'msgId' => Translation::get('ad_categ_id'),
        'msgDate' => Translation::get('ad_adminlog_date'),
        'msgUser' => Translation::get('ad_adminlog_user'),
        'msgIp' => Translation::get('ad_adminlog_ip'),
        'loggingData' => $loggingData,
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
