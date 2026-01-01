<?php

/**
 * Contact Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-09-27
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\CategoryNameTwigExtension;
use phpMyFAQ\Twig\Extensions\CreateLinkTwigExtension;
use phpMyFAQ\Twig\Extensions\FaqTwigExtension;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;

class OverviewController extends AbstractFrontController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */ #[Route(path: '/overview.html', name: 'public.overview')]
    public function index(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('overview', 0);

        $faqHelper = $this->container->get('phpmyfaq.helper.faq');

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentGroups, true);
        $category->setUser($currentUser)->setGroups($currentGroups);

        $faq = $this->container->get('phpmyfaq.faq');
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $this->addExtension(new AttributeExtension(CategoryNameTwigExtension::class));
        $this->addExtension(new AttributeExtension(CreateLinkTwigExtension::class));
        $this->addExtension(new AttributeExtension(FaqTwigExtension::class));
        return $this->render('overview.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'faqOverview'), $this->configuration->getTitle()),
            'metaDescription' => sprintf(
                Translation::get(key: 'msgOverviewMetaDesc'),
                $this->configuration->getTitle(),
            ),
            'pageHeader' => Translation::get(key: 'faqOverview'),
            'faqOverview' => $faqHelper->createOverview(
                $category,
                $faq,
                $this->configuration->getLanguage()->getLanguage(),
            ),
            'msgAuthor' => Translation::get(key: 'msgAuthor'),
            'msgLastUpdateArticle' => Translation::get(key: 'msgLastUpdateArticle'),
        ]);
    }
}
