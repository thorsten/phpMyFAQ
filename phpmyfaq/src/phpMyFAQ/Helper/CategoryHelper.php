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
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Helper;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;

/**
 * Class CategoryHelper
 *
 * @package phpMyFAQ\Helper
 */
class CategoryHelper extends Helper
{
    /**
     * Get all categories in <option> tags.
     *
     * @param int[]|int $categoryId CategoryHelper ID or array of category IDs
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
        foreach ($this->Category->getCategoryTree() as $cat) {
            $indent = '';
            for ($j = 0; $j < $cat['indent']; ++$j) {
                $indent .= '....';
            }
            $categories .= sprintf('<option value="%s"', $cat['id']);

            if (0 === $i && count($categoryId) === 0) {
                $categories .= ' selected';
            } else {
                foreach ($categoryId as $categorised) {
                    if ($cat['id'] == $categorised['category_id']) {
                        $categories .= ' selected';
                    }
                }
            }

            $categories .= sprintf('>%s %s </option>', $indent, Strings::htmlentities($cat['name']));
            ++$i;
        }

        return $categories;
    }

    /**
     * Renders the start page category card decks
     */
    public function renderStartPageCategories(array $categories): string
    {
        if (count($categories) === 0) {
            return '';
        }

        $decks = '';
        foreach ($categories as $category) {
            $decks .= '<div class="col">';
            $decks .= '  <div class="card card-cover h-100 overflow-hidden text-bg-dark rounded-3 shadow-lg p-1" ';
            if ('' !== $category['image']) {
                $decks .= sprintf(
                    'style="%s background-image: url(\'%s\')"',
                    'background-size: cover; background-repeat: no-repeat; background-position: center center;',
                    $category['image']
                );
            }
            $decks .= '>';
            $decks .= sprintf(
                '<h3 class="pt-5 mt-5 mb-4 display-6 lh-1 fw-bold">%s</h3>',
                Strings::htmlentities($category['name'])
            );
            $decks .= '   <a class="btn btn-primary" href="' .
                Strings::htmlentities($category['url']) . '">' . Translation::get('msgGoToCategory') . '</a>';
            $decks .= '  </div>';
            $decks .= '</div>';
        }

        return $decks;
    }

    /**
     * Renders the static tree with the number of records.
     */
    public function renderCategoryTree(int $parentId = 0): string
    {
        $categoryRelation = new CategoryRelation($this->config, $this->Category);
        $categoryRelation->setGroups($this->Category->getGroups());

        $categoryTree = $this->Category->getOrderedCategories();
        $categoryNumbers = $categoryRelation->getCategoryWithFaqs();
        $normalizedCategoryNumbers = $this->normalizeCategoryTree($categoryTree, $categoryNumbers);
        $aggregatedNumbers = $categoryRelation->getAggregatedFaqNumbers($normalizedCategoryNumbers);

        if ((is_countable($categoryTree) ? count($categoryTree) : 0) > 0) {
            return sprintf(
                '<ul class="pmf-category-overview">%s</ul>',
                $this->buildCategoryList($categoryTree, $parentId, $aggregatedNumbers, $normalizedCategoryNumbers)
            );
        } else {
            $languagesAvailable = $this->Category->getCategoryLanguagesTranslated($parentId);
            return sprintf(
                '<p>%s</p><ul class="pmf-category-overview">%s</ul>',
                Translation::get('msgCategoryMissingButTranslationAvailable'),
                $this->buildAvailableCategoryTranslationsList($languagesAvailable)
            );
        }
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
        array $categoryNumbers = []
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
                        '%sindex.php?%saction=show&amp;cat=%d',
                        $this->config->getDefaultUrl(),
                        $sids,
                        $node['id']
                    );

                    $link = new Link($url, $this->config);
                    $link->itemTitle = Strings::htmlentities($node['name']);
                    $link->text = Strings::htmlentities($node['name']);
                    $link->tooltip = !is_null($node['description']) ? Strings::htmlentities($node['description']) : '';
                    $name = $link->toHtmlAnchor();
                } else {
                    $name = Strings::htmlentities($node['name']);
                }

                $html .= sprintf(
                    '<li data-category-id="%d">%s <span class="badge bg-primary">%s</span><br><small>%s</small>',
                    $node['id'],
                    $name,
                    $this->plurals->getMsg('plmsgEntries', $number),
                    $node['description']
                );
                $html .= sprintf(
                    '<ul>%s</ul>',
                    $this->buildCategoryList($categoryTree, $node['id'], $aggregatedNumbers, $categoryNumbers)
                );
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
                '%sindex.php?action=show&amp;lang=%s',
                $this->config->getDefaultUrl(),
                LanguageCodes::getKey($language)
            );
            $link = new Link($url, $this->config);
            $link->itemTitle = Strings::htmlentities($category);
            $link->text = Strings::htmlentities($category);
            $name = $link->toHtmlAnchor();
            $html .= sprintf(
                '<li><strong>%s</strong>: %s</li>',
                $language,
                $name
            );
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
                'faqs' => $categoryNumbers[$categoryId]['faqs'] ?? 0
            ];
        }

        return $normalizedCategoryTree;
    }

    /**
     * Returns an array with all moderators for the given categories.
     *
     * @param int[] $categories
     * @return string[]
     */
    public function getModerators(array $categories): array
    {
        $recipients = [];

        $user = new User($this->config);

        foreach ($categories as $_category) {
            $userId = $this->Category->getOwner($_category);
            $groupId = $this->Category->getModeratorGroupId($_category);

            $user->getUserById($userId);
            $catOwnerEmail = $user->getUserData('email');

            // Avoid to send multiple emails to the same owner
            if (!empty($catOwnerEmail) && !isset($send[$catOwnerEmail])) {
                $recipients[] = $catOwnerEmail;
            }

            if ($groupId > 0) {
                $moderators = $user->perm->getGroupMembers($groupId);
                foreach ($moderators as $moderator) {
                    $user->getUserById($moderator);
                    $moderatorEmail = $user->getUserData('email');

                    // Avoid to send multiple emails to the same moderator
                    if (!empty($moderatorEmail) && !isset($send[$moderatorEmail])) {
                        $recipients[] = $moderatorEmail;
                    }
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
        $availableTranslations = $this->config->getLanguage()->isLanguageAvailable($categoryId, 'faqcategories');
        $availableLanguages = LanguageHelper::getAvailableLanguages();

        foreach ($availableTranslations as $language) {
            $options .= sprintf('<option value="%s">%s</option>', $language, $availableLanguages[$language]);
        }

        return $options;
    }
}
