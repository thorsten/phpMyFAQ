<?php

declare(strict_types=1);

/**
 * Helper class for phpMyFAQ categories.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category\Language\CategoryLanguageService;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Link;
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
     * Get all categories in <option> tags.
     *
     * @param int[]|int $categoryId CategoryHelper ID or array of category IDs
     * @deprecated will be moved into a Twig macro
     */
    public function renderOptions(array|int $categoryId): string
    {
        $categories = '';

        if (!is_array($categoryId)) {
            $categoryId = [
                [
                    'category_id' => $categoryId,
                    'category_lang' => '',
                ],
            ];
        } elseif (isset($categoryId['category_id'])) {
            $categoryId = [$categoryId];
        }

        $i = 0;
        foreach ($this->getCategory()->getCategoryTree() as $cat) {
            $indent = str_repeat('....', $cat['indent']);

            $categories .= sprintf('<option value="%s"', $cat['id']);

            if (0 === $i && $categoryId === []) {
                $categories .= ' selected';
            } else {
                foreach ($categoryId as $categorised) {
                    if ($cat['id'] == $categorised['category_id']) {
                        $categories .= ' selected';
                    }
                }
            }

            $categories .= sprintf('>%s %s </option>', $indent, $cat['name']);
            ++$i;
        }

        return $categories;
    }

    /**
     * Renders the static tree with the number of records.
     */
    public function renderCategoryTree(int $parentId = 0): string
    {
        $categoryRelation = new Relation($this->getConfiguration(), $this->getCategory());
        $categoryRelation->setGroups($this->getCategory()->getGroups());

        $categoryTree = $this->getCategory()->getOrderedCategories();
        $categoryNumbers = $categoryRelation->getCategoryWithFaqs();
        $normalizedCategoryNumbers = $this->normalizeCategoryTree($categoryTree, $categoryNumbers);
        $aggregatedNumbers = $categoryRelation->getAggregatedFaqNumbers($normalizedCategoryNumbers);

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
            Translation::get('msgCategoryMissingButTranslationAvailable'),
            $this->buildAvailableCategoryTranslationsList($languagesAvailable),
        );
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
        global $sids;

        $html = '';
        foreach ($categoryTree as $categoryId => $node) {
            if ($node['parent_id'] === $parentId) {
                $number = 0;
                foreach ($aggregatedNumbers as $key => $numFaqs) {
                    if ($key === $node['id']) {
                        $number = $numFaqs;
                        break;
                    }
                }

                if ($categoryNumbers[$categoryId]['faqs'] > 0) {
                    $url = sprintf(
                        '%sindex.php?%saction=show&cat=%d',
                        $this->configuration->getDefaultUrl(),
                        $sids,
                        $node['id'],
                    );

                    $link = new Link($url, $this->configuration);
                    $link->itemTitle = Strings::htmlentities($node['name']);
                    $link->text = Strings::htmlentities($node['name']);
                    $link->tooltip = is_null($node['description']) ? '' : Strings::htmlentities($node['description']);
                    $name = $link->toHtmlAnchor();
                } else {
                    $name = Strings::htmlentities($node['name']);
                }

                $html .= sprintf(
                    '<li data-category-id="%d">%s <span class="badge text-bg-primary">%s</span><br><small>%s</small>',
                    $node['id'],
                    $name,
                    $this->plurals->getMsg('plmsgEntries', $number),
                    $node['description'],
                );
                $html .= sprintf('<ul>%s</ul>', $this->buildCategoryList(
                    $categoryTree,
                    $node['id'],
                    $aggregatedNumbers,
                    $categoryNumbers,
                ));
                $html .= '</li>';
            }
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
            $url = sprintf(
                '%sindex.php?action=show&lang=%s',
                $this->configuration->getDefaultUrl(),
                LanguageCodes::getKey($language),
            );
            $link = new Link($url, $this->configuration);
            $link->itemTitle = Strings::htmlentities($category);
            $link->text = Strings::htmlentities($category);
            $name = $link->toHtmlAnchor();
            $html .= sprintf('<li><strong>%s</strong>: %s</li>', $language, $name);
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
        if (!$categoryInstance instanceof \phpMyFAQ\Category) {
            return $recipients;
        }

        $user = new User($this->configuration);

        // Track already added emails to avoid duplicates
        $seen = [];

        foreach ($categories as $category) {
            $userId = $categoryInstance->getOwner($category);
            $groupId = $categoryInstance->getModeratorGroupId($category);

            $user->getUserById($userId);
            $emailCategoryOwner = $user->getUserData('email');

            if (!empty($emailCategoryOwner) && !isset($seen[$emailCategoryOwner])) {
                $recipients[] = $emailCategoryOwner;
                $seen[$emailCategoryOwner] = true;
            }

            if ($groupId > 0) {
                $moderators = $user->perm->getGroupMembers($groupId);
                foreach ($moderators as $moderator) {
                    $user->getUserById($moderator);
                    $moderatorEmail = $user->getUserData('email');
                    if (empty($moderatorEmail)) {
                        continue;
                    }

                    if (isset($seen[$moderatorEmail])) {
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

        // Use new CategoryLanguageService to fetch existing translation languages
        $languageService = new CategoryLanguageService();
        $existingTranslations = $languageService->getExistingTranslations($this->configuration, $categoryId);

        foreach ($existingTranslations as $code => $displayName) {
            $options .= sprintf('<option value="%s">%s</option>', $code, $displayName);
        }

        return $options;
    }
}
