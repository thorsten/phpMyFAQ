<?php

/**
 * Sitemap Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 *  @package   phpMyFAQ
 *  @author    Thomas Zeithaml <seo@annatom.de>
 *  @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 *  @copyright 2005-2026 phpMyFAQ Team
 *  @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *  @link      https://www.phpmyfaq.de
 *  @since     2005-08-21
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class SitemapController extends AbstractFrontController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */ #[Route(path: '/sitemap/{letter}/{language}.html', name: 'public.sitemap')]
    public function index(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('sitemap', 0);

        $letter = Filter::filterVar($request->attributes->get('letter'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (!is_null($letter) && 1 == Strings::strlen($letter)) {
            $currLetter = strtoupper(Strings::substr($letter, 0, 1));
        } else {
            $currLetter = '';
        }

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $siteMap = $this->container->get('phpmyfaq.sitemap');
        $siteMap->setUser($currentUser);
        $siteMap->setGroups($currentGroups);

        return $this->render('sitemap.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'msgSitemap'), $this->configuration->getTitle()),
            'metaDescription' => sprintf(Translation::get(key: 'msgSitemapMetaDesc'), $this->configuration->getTitle()),
            'pageHeader' =>
                $currLetter === '' || $currLetter === '0' ? Translation::get(key: 'msgSitemap') : $currLetter,
            'letters' => $siteMap->getAllFirstLetters(),
            'faqs' => $siteMap->getFaqsFromLetter($currLetter),
            'writeCurrentLetter' =>
                $currLetter === '' || $currLetter === '0' ? Translation::get(key: 'msgSitemap') : $currLetter,
        ]);
    }
}
