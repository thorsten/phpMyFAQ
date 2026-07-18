<?php

/**
 * Category Controller
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
 * @since     2026-01-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use phpMyFAQ\Category;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Translation;
use phpMyFAQ\User\UserSession;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/* @mago-expect lint:final-controller - extended by a test double that stubs the DB-touching sub-category preparation */
class CategoryController extends AbstractFrontController
{
    private CategoryHelper $categoryHelper;

    public function __construct(
        private readonly UserSession $userSession,
        private readonly Category $category,
        private readonly Faq $faq,
    ) {
        parent::__construct();
    }

    /**
     * Displays a specific category with its FAQs
     *
     * @throws Exception
     */
    #[Route(path: '/category/{categoryId}/{slug}.html', name: 'public.category.show', methods: ['GET'])]
    public function show(Request $request): Response
    {
        $this->userSession->setCurrentUser($this->currentUser);

        $categoryId = Filter::filterVar($request->attributes->get('categoryId'), FILTER_VALIDATE_INT, 0);
        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());

        $category = $this->initializeCategory($currentGroups);
        $faq = $this->initializeFaq($currentGroups);
        $this->categoryHelper = $this->createCategoryHelper();

        $templateVars = $this->renderSpecificCategory($request, $this->userSession, $categoryId, $category, $faq);

        return $this->render('show.twig', $templateVars);
    }

    /**
     * Displays all categories
     *
     * @throws Exception
     */
    #[Route(path: '/show-categories.html', name: 'public.category.showAll', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userSession->setCurrentUser($this->currentUser);

        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());

        $category = $this->initializeCategory($currentGroups);
        $this->categoryHelper = $this->createCategoryHelper();

        $templateVars = $this->renderAllCategories($request, $this->userSession, $category);

        return $this->render('show.twig', $templateVars);
    }

    /**
     * Initializes and configures the Category object
     *
     * @param array<int> $currentGroups
     * @throws Exception
     */
    private function initializeCategory(array $currentGroups): Category
    {
        $category = $this->category;
        $category->setUser($this->currentUser->getUserId());
        $category->setGroups($currentGroups);
        $category->buildCategoryTree();

        return $category;
    }

    /**
     * Initializes and configures the FAQ object
     *
     * @param array<int> $currentGroups
     * @throws Exception
     */
    private function initializeFaq(array $currentGroups): Faq
    {
        $faq = $this->faq;
        $faq->setUser($this->currentUser->getUserId());
        $faq->setGroups($currentGroups);

        return $faq;
    }

    /**
     * Creates and configures a CategoryHelper instance
     */
    private function createCategoryHelper(): CategoryHelper
    {
        $categoryHelper = new CategoryHelper();
        $categoryHelper->setPlurals(new Plurals());

        return $categoryHelper;
    }

    /**
     * Renders a specific category with its FAQs and subcategories
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    private function renderSpecificCategory(
        Request $request,
        UserSession $userSession,
        int $selectedCategoryId,
        Category $category,
        Faq $faq,
    ): array {
        $userSession->userTracking('show_category', $selectedCategoryId);

        $categoryEntity = $category->getCategoryData($selectedCategoryId);
        $records = $this->getFaqRecords($faq, $selectedCategoryId);
        $subCategoryContent = $this->getSubCategoryContent($category, $selectedCategoryId, $records);
        $categoryLevelUp = $this->buildParentNavigationLink($category, $categoryEntity);
        $categoryImage = $this->getCategoryImageUrl($categoryEntity);
        $categoryHeader = Translation::getString(key: 'msgEntriesIn') . $categoryEntity->getName();

        return [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', $categoryHeader, $this->configuration->getTitle()),
            'metaDescription' => sprintf(
                Translation::getString(key: 'msgCategoryMetaDesc'),
                $this->configuration->getTitle(),
            ),
            'categoryHeader' => $categoryHeader,
            'isAllCategories' => false,
            'breadcrumb' => $category->getPathWithStartPage($selectedCategoryId, '/', true),
            'categoryFaqsHeader' => $categoryEntity->getName(),
            'categoryDescription' => $categoryEntity->getDescription() ?? '',
            'categorySubsHeader' => Translation::get(key: 'msgSubCategories'),
            'categoryImage' => $categoryImage,
            'categoryContent' => $records,
            'subCategoryContent' => $subCategoryContent,
            'categoryLevelUp' => $categoryLevelUp,
        ];
    }

    /**
     * Renders all categories
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    private function renderAllCategories(Request $request, UserSession $userSession, Category $category): array
    {
        $userSession->userTracking('show_all_categories', 0);

        $this->categoryHelper->setConfiguration($this->configuration)->setCategory($category);

        $categoryHeader = Translation::getString(key: 'msgShowAllCategories');

        return [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', $categoryHeader, $this->configuration->getTitle()),
            'metaDescription' => sprintf(
                Translation::getString(key: 'msgCategoryMetaDesc'),
                $this->configuration->getTitle(),
            ),
            'categoryHeader' => $categoryHeader,
            'isAllCategories' => true,
            'breadcrumb' => $category->getPathWithStartPage(0, '/', true),
            'categoryFaqsHeader' => Translation::get(key: 'msgShowAllCategories'),
            'categoryDescription' => Translation::get(key: 'msgCategoryDescription'),
            'categorySubsHeader' => Translation::get(key: 'msgSubCategories'),
            'categoryTree' => $categoryTreeData = $this->categoryHelper->getCategoryTreeData(0),
            'categoryEmptyMessage' => $categoryTreeData === [] ? $this->categoryHelper->renderCategoryTree(0) : '',
            'msgExpandAll' => Translation::get(key: 'msgExpandAll'),
            'msgCollapseAll' => Translation::get(key: 'msgCollapseAll'),
            'subCategoryContent' => Translation::get(key: 'msgSubCategoryContent'),
            'categoryLevelUp' => '',
        ];
    }

    /**
     * Gets FAQ records for a category
     */
    private function getFaqRecords(Faq $faq, int $categoryId): string
    {
        $faqListData = $faq->getFaqsDataByCategoryId(
            $categoryId,
            $this->configuration->get('records.orderby'),
            $this->configuration->get('records.sortby'),
        );

        if ($faqListData['items'] === []) {
            return '';
        }

        return $this->renderView('category-faq-list.twig', $faqListData);
    }

    /**
     * Gets subcategory content if available. When the category has no directly
     * assigned FAQ entries but has child categories, the rendered category tree
     * is promoted into $records so visitors land on a useful overview instead of
     * an empty-state message.
     */
    private function getSubCategoryContent(Category $category, int $selectedCategoryId, string &$records): ?string
    {
        $childNodes = $category->getChildNodes($selectedCategoryId);
        $hasChildren = is_countable($childNodes) && $childNodes !== [];

        if ($records !== '' && !$hasChildren) {
            return null;
        }

        $this->prepareSubCategoryHelper($selectedCategoryId);

        if ($records === '' && $hasChildren) {
            $records = $this->categoryHelper->renderCategoryTree($selectedCategoryId);

            return null;
        }

        if ($records === '') {
            $records = sprintf(
                '<div class="mb-5 alert alert-info">%s</div>',
                Translation::getString(key: 'msgErrorNoRecords'),
            );
        }

        return $hasChildren ? $this->categoryHelper->renderCategoryTree($selectedCategoryId) : null;
    }

    /**
     * Prepares the category helper with a sub-category scoped to the selected
     * category id. Extracted from getSubCategoryContent to keep the branching
     * logic free of direct database side effects.
     */
    protected function prepareSubCategoryHelper(int $selectedCategoryId): void
    {
        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());
        $subCategory = new Category($this->configuration, $currentGroups, true);
        $subCategory->setUser($this->currentUser->getUserId());
        $subCategory->transform($selectedCategoryId);
        $this->categoryHelper->setConfiguration($this->configuration)->setCategory($subCategory);
    }

    /**
     * Builds the parent category navigation link
     */
    private function buildParentNavigationLink(Category $category, CategoryEntity $categoryEntity): string
    {
        if ($categoryEntity->getId() === 0) {
            return '';
        }

        $parentId = $categoryEntity->getParentId();
        $parentName = $category->getCategoryName($parentId);

        $url = $this->configuration->getDefaultUrl() . 'show-categories.html';
        if ($parentId !== 0) {
            $slug = TitleSlugifier::slug($parentName);
            $url = sprintf('%scategory/%d/%s.html', $this->configuration->getDefaultUrl(), $parentId, $slug);
        }

        $text = $parentName === '' ? Translation::getString(key: 'msgCategoryUp') : $parentName;

        $link = new Link($url, $this->configuration);
        $link->setTitle($text);
        $link->text = $text;
        $link->tooltip = Translation::get(key: 'msgCategoryUp');

        return sprintf('<i class="bi bi-arrow-90deg-up"></i> %s', $link->toHtmlAnchor());
    }

    /**
     * Gets the category image URL if available
     */
    private function getCategoryImageUrl(CategoryEntity $categoryEntity): ?string
    {
        $image = $categoryEntity->getImage();

        if (is_null($image) || $image === '') {
            return null;
        }

        return $this->configuration->getDefaultUrl() . 'content/user/images/' . $image;
    }
}
