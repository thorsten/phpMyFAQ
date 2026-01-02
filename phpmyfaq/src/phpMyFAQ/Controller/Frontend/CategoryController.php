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

use phpMyFAQ\Category;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Translation;
use phpMyFAQ\User\UserSession;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractFrontController
{
    private CategoryHelper $categoryHelper;

    /**
     * Displays a specific category with its FAQs
     *
     * @throws \Exception
     */
    #[Route(path: '/category/{categoryId}/{slug}.html', name: 'public.category.show', methods: ['GET'])]
    public function show(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);

        $categoryId = Filter::filterVar($request->attributes->get('categoryId'), FILTER_VALIDATE_INT);
        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());

        $category = $this->initializeCategory($currentGroups);
        $faq = $this->initializeFaq($currentGroups);
        $this->categoryHelper = $this->createCategoryHelper();

        $templateVars = $this->renderSpecificCategory($request, $faqSession, $categoryId, $category, $faq);

        return $this->render('show.twig', $templateVars);
    }

    /**
     * Displays all categories
     *
     * @throws \Exception
     */
    #[Route(path: '/show-categories.html', name: 'public.category.showAll', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);

        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());

        $category = $this->initializeCategory($currentGroups);
        $this->categoryHelper = $this->createCategoryHelper();

        $templateVars = $this->renderAllCategories($request, $faqSession, $category);

        return $this->render('show.twig', $templateVars);
    }

    /**
     * Initializes and configures the Category object
     *
     * @param array<int> $currentGroups
     */
    private function initializeCategory(array $currentGroups): Category
    {
        $category = $this->container->get('phpmyfaq.category');
        $category->setUser($this->currentUser->getUserId());
        $category->setGroups($currentGroups);
        $category->buildCategoryTree();

        return $category;
    }

    /**
     * Initializes and configures the FAQ object
     *
     * @param array<int> $currentGroups
     */
    private function initializeFaq(array $currentGroups): Faq
    {
        $faq = $this->container->get('phpmyfaq.faq');
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
     * @throws \Exception
     */
    private function renderSpecificCategory(
        Request $request,
        UserSession $faqSession,
        int $selectedCategoryId,
        Category $category,
        Faq $faq,
    ): array {
        $faqSession->userTracking('show_category', $selectedCategoryId);

        $categoryData = $category->getCategoryData($selectedCategoryId);
        $records = $this->getFaqRecords($faq, $selectedCategoryId);
        $subCategoryContent = $this->getSubCategoryContent($category, $selectedCategoryId, $records);
        $categoryLevelUp = $this->buildParentNavigationLink($category, $categoryData);
        $categoryImage = $this->getCategoryImageUrl($categoryData);
        $categoryHeader = Translation::get(key: 'msgEntriesIn') . $categoryData->getName();

        return [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', $categoryHeader, $this->configuration->getTitle()),
            'metaDescription' => sprintf(
                Translation::get(key: 'msgCategoryMetaDesc'),
                $this->configuration->getTitle(),
            ),
            'categoryHeader' => $categoryHeader,
            'breadcrumb' => $category->getPathWithStartpage($selectedCategoryId, '/', true),
            'categoryFaqsHeader' => $categoryData->getName(),
            'categoryDescription' => $categoryData->getDescription() ?? '',
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
     * @throws \Exception
     */
    private function renderAllCategories(Request $request, UserSession $faqSession, Category $category): array
    {
        $faqSession->userTracking('show_all_categories', 0);

        $this->categoryHelper->setConfiguration($this->configuration)->setCategory($category);

        $categoryHeader = Translation::get(key: 'msgFullCategories');

        return [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', $categoryHeader, $this->configuration->getTitle()),
            'metaDescription' => sprintf(
                Translation::get(key: 'msgCategoryMetaDesc'),
                $this->configuration->getTitle(),
            ),
            'categoryHeader' => $categoryHeader,
            'breadcrumb' => $category->getPathWithStartpage(0, '/', true),
            'categoryFaqsHeader' => Translation::get(key: 'msgShowAllCategories'),
            'categoryDescription' => Translation::get(key: 'msgCategoryDescription'),
            'categorySubsHeader' => Translation::get(key: 'msgSubCategories'),
            'categoryContent' => $this->categoryHelper->renderCategoryTree(0),
            'subCategoryContent' => Translation::get(key: 'msgSubCategoryContent'),
            'categoryLevelUp' => '',
        ];
    }

    /**
     * Gets FAQ records for a category
     */
    private function getFaqRecords(Faq $faq, int $categoryId): string
    {
        return $faq->renderFaqsByCategoryId(
            $categoryId,
            $this->configuration->get('records.orderby'),
            $this->configuration->get('records.sortby'),
        );
    }

    /**
     * Gets subcategory content if available
     */
    private function getSubCategoryContent(Category $category, int $selectedCategoryId, string &$records): ?string
    {
        if ($records !== '' && !$category->getChildNodes($selectedCategoryId)) {
            return null;
        }

        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());
        $subCategory = new Category($this->configuration, $currentGroups, true);
        $subCategory->setUser($this->currentUser);
        $subCategory->transform($selectedCategoryId);
        $this->categoryHelper->setConfiguration($this->configuration)->setCategory($subCategory);

        if ($records === '') {
            $records = sprintf(
                '<div class="mb-5 alert alert-info">%s</div>',
                Translation::get(key: 'msgErrorNoRecords'),
            );
        }

        $childNodes = $category->getChildNodes($selectedCategoryId);
        $hasChildren = is_countable($childNodes) && count($childNodes) > 0;

        return $hasChildren ? $this->categoryHelper->renderCategoryTree($selectedCategoryId) : null;
    }

    /**
     * Builds the parent category navigation link
     */
    private function buildParentNavigationLink(Category $category, CategoryEntity $categoryData): string
    {
        if ($categoryData->getId() === 0) {
            return '';
        }

        $parentId = $categoryData->getParentId();
        $parentName = $category->getCategoryName($parentId);

        if ($parentId === 0) {
            $url = $this->configuration->getDefaultUrl() . 'show-categories.html';
        } else {
            $slug = $this->createSlug($parentName);
            $url = sprintf('%scategory/%d/%s.html', $this->configuration->getDefaultUrl(), $parentId, $slug);
        }

        $text = $parentName === '' ? Translation::get(key: 'msgCategoryUp') : $parentName;

        $link = new Link($url, $this->configuration);
        $link->setTitle($text);
        $link->text = $text;
        $link->tooltip = Translation::get(key: 'msgCategoryUp');

        return sprintf('<i class="bi bi-arrow-90deg-up"></i> %s', $link->toHtmlAnchor());
    }

    /**
     * Creates a URL-friendly slug from a category name
     */
    private function createSlug(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Gets the category image URL if available
     */
    private function getCategoryImageUrl(CategoryEntity $categoryData): ?string
    {
        $image = $categoryData->getImage();

        if (is_null($image) || $image === '') {
            return null;
        }

        return $this->configuration->getDefaultUrl() . 'content/user/images/' . $image;
    }
}
