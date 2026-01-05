<?php

/**
 * Builds visual representations of category trees.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-10-19
 */

declare(strict_types=1);

namespace phpMyFAQ\Category\Tree;

final readonly class TreeVisualizer
{
    public function __construct(
        private TreePathResolver $treePathResolver,
    ) {
    }

    /**
     * Builds branch visuals (vertical/space) for a category path.
     *
     * @param array<int, array<string, mixed>> $categoryName
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int, string>
     */
    public function buildTree(array $categoryName, array $childrenMap, int $categoryId): array
    {
        $ascendants = $this->treePathResolver->getNodes($categoryName, $categoryId);
        $tree = [];
        foreach ($ascendants as $i => $ascendantId) {
            if ($ascendantId === 0) {
                break;
            }

            $brothers = $this->treePathResolver->getBrothers($categoryName, $childrenMap, (int) $ascendantId);
            $last = end($brothers);
            $tree[$i] = $ascendantId === $last ? 'space' : 'vertical';
        }

        return $tree;
    }
}
