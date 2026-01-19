<?php

/**
 * Start page Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category\Startpage;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Faq\Statistics;
use phpMyFAQ\Filter;
use phpMyFAQ\News;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\TagNameTwigExtension;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Extension\AttributeExtension;

final class StartpageController extends AbstractFrontController
{
    /**
     * Displays the start page with categories, Top 10, and latest messages.
     *
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/', name: 'public.index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $news = new News($this->configuration);
        $plurals = $this->container->get('phpmyfaq.language.plurals');
        $faqStatistics = new Statistics($this->configuration);

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $startpage = new Startpage($this->configuration);
        $startpage
            ->setLanguage($this->configuration->getLanguage()->getLanguage())
            ->setUser($currentUser)
            ->setGroups($currentGroups);

        $startPageCategories = $startpage->getCategories();

        // Get top ten parameter
        $param = $this->configuration->get('records.orderingPopularFaqs') === 'visits' ? 'visits' : 'voted';

        $faq = $this->container->get('phpmyfaq.faq');
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $tags = $this->container->get('phpmyfaq.tags');
        $tags->setUser($currentUser)->setGroups($currentGroups);

        $faqSystem = $this->container->get('phpmyfaq.system');
        Filter::filterVar($request->query->get('cat'), FILTER_VALIDATE_INT, 0);

        $this->addExtension(new AttributeExtension(TagNameTwigExtension::class));
        return $this->render('startpage.twig', [
            ...$this->getHeader($request),
            'baseHref' => $faqSystem->getSystemUri($this->configuration),
            'title' => $this->configuration->getTitle(),
            'pageHeader' => $this->configuration->getTitle(),
            'startPageCategories' => (is_countable($startPageCategories) ? count($startPageCategories) : 0) > 0,
            'startPageCategoryDecks' => $startPageCategories,
            'stickyRecordsHeader' => Translation::get(key: 'stickyRecordsHeader'),
            'stickyRecordsList' => $faq->getStickyFaqsData(),
            'writeTopTenHeader' => Translation::get(key: 'msgTopTen'),
            'topRecordsList' => $faqStatistics->getTopTen($param),
            'errorMsgTopTen' => Translation::get(key: 'err_noTopTen'),
            'writeNewestHeader' => Translation::get(key: 'msgLatestArticles'),
            'latestRecordsList' => $faqStatistics->getLatest(),
            'errorMsgLatest' => Translation::get(key: 'msgErrorNoRecords'),
            'msgTrendingFAQs' => Translation::get(key: 'msgTrendingFAQs'),
            'trendingRecordsList' => $faqStatistics->getTrending(),
            'errorMsgTrendingFaqs' => Translation::get(key: 'msgErrorNoRecords'),
            'msgNewsHeader' => Translation::get(key: 'newsArchive'),
            'newsList' => $news->getAll(),
            'writeNumberOfArticles' => $plurals->get(
                'plmsgHomeArticlesOnline',
                $faqStatistics->totalFaqs($this->configuration->getLanguage()->getLanguage()),
            ),
            'msgTags' => Translation::get(key: 'msgPopularTags'),
            'tagsList' => $tags->getPopularTags(12),
        ]);
    }
}
