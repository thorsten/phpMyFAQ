<?php

/**
 * Helper class for phpMyFAQ categories.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Language\CategoryLanguageService;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Link;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;

/**
 * Class CategoryHelper
 *
 * @package phpMyFAQ\Helper
 */
class CategoryHelper extends AbstractHelper
{
    /**
     * Renders the static tree with the number of records.
     */
    public function renderCategoryTree(int $parentId = 0): string
    {
        [$categoryTree, $normalizedCategoryNumbers, $aggregatedNumbers] = $this->gatherCategoryData();

        if ((is_countable($categoryTree) ? count($categoryTree) : 0) > 0) {
            return sprintf('<ul class="pmf-category-overview">%s</ul>', $this->buildCategoryList(
                $categoryTree,
                $parentId,
                $aggregatedNumbers,
                $normalizedCategoryNumbers,
            ));
        }

        $languagesAvailable = $this->getCategory()->getCategoryLanguagesTranslated($parentId);
        return sprintf(
            '<p>%s</p><ul class="pmf-category-overview">%s</ul>',
            Translation::get(key: 'msgCategoryMissingButTranslationAvailable'),
            $this->buildAvailableCategoryTranslationsList($languagesAvailable),
        );
    }

    /**
     * Gathers the raw category tree and FAQ-count arrays shared by the HTML
     * renderer and the structured-data builder.
     *
     * @return array{0: array<int, array>, 1: array<int, array>, 2: array<int, int>}
     */
    private function gatherCategoryData(): array
    {
        $categoryRelation = new Relation($this->getConfiguration(), $this->getCategory());
        $categoryRelation->setGroups($this->getCategory()->getGroups());

        $categoryTree = $this->getCategory()->getOrderedCategories();
        $categoryNumbers = $categoryRelation->getCategoryWithFaqs();
        $normalizedCategoryNumbers = $this->normalizeCategoryTree($categoryTree, $categoryNumbers);
        $aggregatedNumbers = $categoryRelation->getAggregatedFaqNumbers($normalizedCategoryNumbers);

        return [$categoryTree, $normalizedCategoryNumbers, $aggregatedNumbers];
    }

    /**
     * Returns the category tree as a nested data structure for Twig rendering.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCategoryTreeData(int $parentId = 0): array
    {
        [$categoryTree, $normalizedCategoryNumbers, $aggregatedNumbers] = $this->gatherCategoryData();

        if ((is_countable($categoryTree) ? count($categoryTree) : 0) === 0) {
            return [];
        }

        return $this->buildCategoryNodes($categoryTree, $parentId, $aggregatedNumbers, $normalizedCategoryNumbers);
    }

    /**
     * Recursively builds the nested category node array.
     *
     * @param array<int, array> $categoryTree
     * @param array<int, int>   $aggregatedNumbers
     * @param array<int, array> $categoryNumbers
     * @return array<int, array<string, mixed>>
     */
    public function buildCategoryNodes(
        array $categoryTree,
        int $parentId = 0,
        array $aggregatedNumbers = [],
        array $categoryNumbers = [],
    ): array {
        $nodes = [];

        foreach ($categoryTree as $categoryId => $node) {
            if ((int) $node['parent_id'] !== $parentId) {
                continue;
            }

            $faqCount = (int) ($aggregatedNumbers[$node['id']] ?? 0);
            $hasFaqs = (int) ($categoryNumbers[$categoryId]['faqs'] ?? 0) > 0;

            $description = trim((string) ($node['description'] ?? ''));
            $imageFile = trim((string) ($node['image'] ?? ''));
            $image = $imageFile !== ''
                ? sprintf('%scontent/user/images/%s', $this->configuration->getDefaultUrl(), $imageFile)
                : null;

            $nodes[] = [
                'id' => (int) $node['id'],
                'name' => $node['name'],
                'description' => $description === '' ? null : $description,
                'url' => sprintf(
                    '%scategory/%d/%s.html',
                    $this->configuration->getDefaultUrl(),
                    $node['id'],
                    TitleSlugifier::slug($node['name']),
                ),
                'image' => $image,
                'faqCount' => $faqCount,
                'faqCountLabel' => $this->plurals->get(key: 'plmsgEntries', number: $faqCount),
                'hasFaqs' => $hasFaqs,
                'avatarColor' => sprintf('hsl(%d, 55%%, 45%%)', abs(crc32((string) $node['name'])) % 360),
                'children' => $this->buildCategoryNodes(
                    $categoryTree,
                    (int) $node['id'],
                    $aggregatedNumbers,
                    $categoryNumbers,
                ),
            ];
        }

        return $nodes;
    }

    /**
     * Builds a category list
     *
     * @param array<int, array> $categoryTree
     * @param array<int, array> $aggregatedNumbers
     * @param array<int, array> $categoryNumbers
     */
    public function buildCategoryList(
        array $categoryTree,
        int $parentId = 0,
        array $aggregatedNumbers = [],
        array $categoryNumbers = [],
    ): string {
        $html = '';
        foreach ($categoryTree as $categoryId => $node) {
            if ($node['parent_id'] !== $parentId) {
                continue;
            }

            $number = 0;
            foreach ($aggregatedNumbers as $key => $numFaqs) {
                if ($key !== $node['id']) {
                    continue;
                }

                $number = $numFaqs;
                break;
            }

            $name = $node['name'];
            if ($categoryNumbers[$categoryId]['faqs'] > 0) {
                $url = sprintf(
                    '%scategory/%d/%s.html',
                    $this->configuration->getDefaultUrl(),
                    $node['id'],
                    TitleSlugifier::slug($name),
                );

                $link = new Link($url, $this->configuration);
                $link->setTitle($node['name']);
                $link->text = $node['name'];
                $link->tooltip = is_null($node['description']) ? '' : $node['description'];
                $name = $link->toHtmlAnchor();
            }

            $description = trim((string) ($node['description'] ?? ''));
            $descriptionHtml = '' === $description ? '' : sprintf('<br><small>%s</small>', $description);

            $html .= sprintf(
                '<li data-category-id="%d">%s <span class="badge text-bg-primary">%s</span>%s',
                $node['id'],
                $name,
                $this->plurals->get(key: 'plmsgEntries', number: $number),
                $descriptionHtml,
            );
            $html .= sprintf('<ul>%s</ul>', $this->buildCategoryList(
                $categoryTree,
                $node['id'],
                $aggregatedNumbers,
                $categoryNumbers,
            ));
            $html .= '</li>';
        }

        return $html;
    }

    /**
     * Returns a list of items with linked translated categories
     *
     * @param array<string, string> $availableCategoryTranslations
     */
    public function buildAvailableCategoryTranslationsList(array $availableCategoryTranslations): string
    {
        $html = '';

        foreach ($availableCategoryTranslations as $language => $category) {
            $url = sprintf('%sshow-categories.html?lang=%s', $this->configuration->getDefaultUrl(), $language);
            $link = new Link($url, $this->configuration);
            $link->setTitle(Strings::htmlentities($category));
            $link->text = Strings::htmlentities($category);
            $name = $link->toHtmlAnchor();
            $html .= sprintf('<li><strong>%s</strong>: %s</li>', LanguageCodes::get($language), $name);
        }

        return $html;
    }

    /**
     * Normalizes the category tree with the number of FAQs per category
     *
     * @param array<int, array> $categoryTree
     * @param array<int, array> $categoryNumbers
     * @return array<int, array>
     */
    public function normalizeCategoryTree(array $categoryTree, array $categoryNumbers): array
    {
        $normalizedCategoryTree = [];

        foreach ($categoryTree as $categoryId => $category) {
            $normalizedCategoryTree[$category['id']] = [
                'category_id' => $categoryId,
                'parent_id' => (int) $category['parent_id'],
                'name' => $category['name'],
                'description' => $category['description'],
                'faqs' => $categoryNumbers[$categoryId]['faqs'] ?? 0,
            ];
        }

        return $normalizedCategoryTree;
    }

    /**
     * Returns an array with all moderators for the given categories.
     *
     * @param int[] $categories
     * @return string[]
     * @throws Exception
     */
    public function getModerators(array $categories): array
    {
        $recipients = [];

        // Ensure we have a valid Category instance before proceeding
        $categoryInstance = $this->Category;
        if (!$categoryInstance instanceof Category) {
            return $recipients;
        }

        $user = new User($this->configuration);

        // Track already added emails to avoid duplicates
        $seen = [];

        foreach ($categories as $category) {
            $userId = $categoryInstance->getOwner((int) $category);
            $groupId = $categoryInstance->getModeratorGroupId((int) $category);

            $user->getUserById($userId);
            $emailCategoryOwner = $user->getUserData('email');

            if (
                is_string($emailCategoryOwner)
                && $emailCategoryOwner !== ''
                && !array_key_exists($emailCategoryOwner, $seen)
            ) {
                $recipients[] = $emailCategoryOwner;
                $seen[$emailCategoryOwner] = true;
            }

            if ($groupId > 0) {
                $moderators = $user->perm->getGroupMembers($groupId);
                foreach ($moderators as $moderator) {
                    $user->getUserById($moderator);
                    $moderatorEmail = $user->getUserData('email');
                    if (!is_string($moderatorEmail) || $moderatorEmail === '') {
                        continue;
                    }

                    if (array_key_exists($moderatorEmail, $seen)) {
                        continue;
                    }

                    $recipients[] = $moderatorEmail;
                    $seen[$moderatorEmail] = true;
                }
            }
        }

        return array_unique($recipients);
    }

    /**
     * Renders the <option> tags for the available translations for a given category.
     */
    public function renderAvailableTranslationsOptions(int $categoryId): string
    {
        $options = '';

        $categoryLanguageService = new CategoryLanguageService();
        $existingTranslations = $categoryLanguageService->getExistingTranslations($this->configuration, $categoryId);

        foreach ($existingTranslations as $code => $displayName) {
            $options .= sprintf('<option value="%s">%s</option>', $code, $displayName);
        }

        return $options;
    }
}
