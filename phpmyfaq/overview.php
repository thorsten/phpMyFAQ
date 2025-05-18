<?php

/**
 * FAQ overview page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-09-27
 */

use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Twig\Extensions\CategoryNameTwigExtension;
use phpMyFAQ\Twig\Extensions\CreateLinkTwigExtension;
use phpMyFAQ\Twig\Extensions\FaqTwigExtension;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\Translation;
use Twig\Extension\AttributeExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

$faqSession = $container->get('phpmyfaq.user.session');
$faqSession->setCurrentUser($user);
$faqSession->userTracking('overview', 0);

$faqHelper = new FaqHelper($faqConfig);

$faq->setUser($user->getUserId());
$faq->setGroups($currentGroups);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twig->addExtension(new AttributeExtension(CategoryNameTwigExtension::class));
$twig->addExtension(new AttributeExtension(CreateLinkTwigExtension::class));
$twig->addExtension(new AttributeExtension(FaqTwigExtension::class));
$twigTemplate = $twig->loadTemplate('./overview.twig');

$templateVars = [
    ... $templateVars,
    'title' => sprintf('%s - %s', Translation::get('faqOverview'), $faqConfig->getTitle()),
    'metaDescription' => sprintf(Translation::get('msgOverviewMetaDesc'), $faqConfig->getTitle()),
    'pageHeader' => Translation::get('faqOverview'),
    'faqOverview' => $faqHelper->createOverview($category, $faq, $faqLangCode),
    'msgAuthor' => Translation::get('msgAuthor'),
    'msgLastUpdateArticle' => Translation::get('msgLastUpdateArticle')
];

return $templateVars;
