<?php

/**
 * Sitemap frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thomas Zeithaml <seo@annatom.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-08-21
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

$faqSession = $container->get('phpmyfaq.user.session');
$faqSession->setCurrentUser($user);
$faqSession->userTracking('sitemap', 0);

$request = Request::createFromGlobals();
$letter = Filter::filterVar($request->query->get('letter'), FILTER_SANITIZE_SPECIAL_CHARS);
if (!is_null($letter) && 1 == Strings::strlen($letter)) {
    $currLetter = strtoupper(Strings::substr($letter, 0, 1));
} else {
    $currLetter = '';
}

$siteMap = $container->get('phpmyfaq.sitemap');
$siteMap->setUser($currentUser);
$siteMap->setGroups($currentGroups);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twigTemplate = $twig->loadTemplate('./sitemap.twig');

// Twig template variables
$templateVars = [
    ...$templateVars,
    'title' => sprintf('%s - %s', Translation::get(key: 'msgSitemap'), $faqConfig->getTitle()),
    'metaDescription' => sprintf(Translation::get(key: 'msgSitemapMetaDesc'), $faqConfig->getTitle()),
    'pageHeader' => $currLetter === '' || $currLetter === '0' ? Translation::get(key: 'msgSitemap') : $currLetter,
    'letters' => $siteMap->getAllFirstLetters(),
    'faqs' => $siteMap->getFaqsFromLetter($currLetter),
    'writeCurrentLetter' =>
        $currLetter === '' || $currLetter === '0' ? Translation::get(key: 'msgSitemap') : $currLetter,
];

return $templateVars;
