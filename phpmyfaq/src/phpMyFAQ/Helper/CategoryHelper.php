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
     *
     * @param array $categories
     * @return string
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
        $categoriesWithNumbers = $categoryRelation->getCategoryTree();
        $aggregatedNumbers = $categoryRelation->getAggregatedFaqNumbers($categoriesWithNumbers);

        return sprintf(
            '<ul class="pmf-category-overview">%s</ul>',
            $this->buildCategoryList($this->Category->getAllCategories(), $parentId, $aggregatedNumbers)
        );
    }

    /**
     * Builds a category list
     *
     * @param array             $tree
     * @param int               $parentId
     * @param array<int, array> $aggregatedNumbers
     * @return string
     */
    public function buildCategoryList(array $tree, int $parentId = 0, array $aggregatedNumbers = []): string
    {
        global $sids, $plr;

        $html = '';
        foreach ($tree as $node) {
            if ($node['parent_id'] === $parentId) {
                $number = 0;
                foreach ($aggregatedNumbers as $aggregated) {
                    if ($aggregated['id'] === $node['id']) {
                        $number = $aggregated['faqs'];
                        break;
                    }
                }

                if ($number > 0) {
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
                    $plr->getMsg('plmsgEntries', $number),
                    $node['description']
                );
                $html .= '<ul>' . $this->buildCategoryList($tree, $node['id'], $aggregatedNumbers) . '</ul>';
                $html .= '</li>';
            }
        }
        return $html;
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
        $availableTranslations = $this->config->getLanguage()->languageAvailable($categoryId, 'faqcategories');
        $availableLanguages = LanguageHelper::getAvailableLanguages();

        foreach ($availableTranslations as $language) {
            $options .= sprintf('<option value="%s">%s</option>', $language, $availableLanguages[$language]);
        }

        return $options;
    }
}
