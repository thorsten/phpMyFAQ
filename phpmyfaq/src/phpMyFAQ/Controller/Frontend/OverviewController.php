<?php

declare(strict_types=1);

/**
 * Overview Controller
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
use phpMyFAQ\Faq;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\CategoryNameTwigExtension;
use phpMyFAQ\Twig\Extensions\CreateLinkTwigExtension;
use phpMyFAQ\Twig\Extensions\FaqTwigExtension;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserSession;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;

final class OverviewController extends AbstractFrontController
{
    public function __construct(
        private readonly UserSession $faqSession,
        private readonly FaqHelper $faqHelper,
        private readonly Faq $faq,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */ #[Route(path: '/overview.html', name: 'public.overview', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->faqSession->setCurrentUser($this->currentUser);
        $this->faqSession->userTracking('overview', 0);

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentGroups, true);
        $category->setUser($currentUser)->setGroups($currentGroups);

        $this->faq->setUser($currentUser);
        $this->faq->setGroups($currentGroups);

        $this->addExtension(new AttributeExtension(CategoryNameTwigExtension::class));
        $this->addExtension(new AttributeExtension(CreateLinkTwigExtension::class));
        $this->addExtension(new AttributeExtension(FaqTwigExtension::class));
        return $this->render('overview.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::getString(key: 'faqOverview'), $this->configuration->getTitle()),
            'metaDescription' => sprintf(
                Translation::getString(key: 'msgOverviewMetaDesc'),
                $this->configuration->getTitle(),
            ),
            'pageHeader' => Translation::get(key: 'faqOverview'),
            'faqOverview' => $this->faqHelper->createOverview(
                $category,
                $this->faq,
                $this->configuration->getLanguage()->getLanguage(),
            ),
            'msgAuthor' => Translation::get(key: 'msgAuthor'),
            'msgLastUpdateArticle' => Translation::get(key: 'msgLastUpdateArticle'),
        ]);
    }
}
