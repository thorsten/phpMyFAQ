<?php

/**
 * Benchmark for the category tree builder.
 *
 * Measures the cost of building phpMyFAQ's category hierarchy from a flat
 * database result set — the same operation that runs on every page load that
 * renders a category navigation or breadcrumb trail.
 *
 * Run with:
 *   ./phpmyfaq/src/libs/bin/phpbench run tests/Benchmarks/CategoryTreeBench.php --report=default
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-04-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use phpMyFAQ\Category\Tree\TreeBuilder;

#[BeforeMethods(['setUp'])]
#[Groups(['tree'])]
class CategoryTreeBench
{
    /** Flat category list keyed by id — mirrors a real DB result set. */
    private array $smallTree;   // 10 categories, 3 levels deep
    private array $mediumTree;  // 100 categories, 4 levels deep
    private array $largeTree;   // 500 categories, 5 levels deep

    private TreeBuilder $builder;

    public function setUp(): void
    {
        $this->builder = new TreeBuilder();
        $this->smallTree = $this->generateFlatCategories(10, 3);
        $this->mediumTree = $this->generateFlatCategories(100, 4);
        $this->largeTree = $this->generateFlatCategories(500, 5);
    }

    // -------------------------------------------------------------------------
    // buildAdminCategoryTree — recursive nested-array representation
    // -------------------------------------------------------------------------

    #[Revs(500), Iterations(5), Warmup(2)]
    public function benchAdminTreeSmall(): void
    {
        $this->builder->buildAdminCategoryTree($this->smallTree);
    }

    #[Revs(100), Iterations(5), Warmup(2)]
    public function benchAdminTreeMedium(): void
    {
        $this->builder->buildAdminCategoryTree($this->mediumTree);
    }

    #[Revs(20), Iterations(5), Warmup(2)]
    public function benchAdminTreeLarge(): void
    {
        $this->builder->buildAdminCategoryTree($this->largeTree);
    }

    // -------------------------------------------------------------------------
    // buildLinearTree — flat array with indent level, used for <select> menus
    // -------------------------------------------------------------------------

    #[Revs(500), Iterations(5), Warmup(2)]
    public function benchLinearTreeSmall(): void
    {
        $this->builder->buildLinearTree($this->smallTree);
    }

    #[Revs(100), Iterations(5), Warmup(2)]
    public function benchLinearTreeMedium(): void
    {
        $this->builder->buildLinearTree($this->mediumTree);
    }

    #[Revs(20), Iterations(5), Warmup(2)]
    public function benchLinearTreeLarge(): void
    {
        $this->builder->buildLinearTree($this->largeTree);
    }

    // -------------------------------------------------------------------------
    // computeLevel — depth of a single node (walks parent chain)
    // -------------------------------------------------------------------------

    #[Revs(1000), Iterations(5), Warmup(2)]
    public function benchComputeLevelShallow(): void
    {
        // Level 1 node — one hop to root
        $this->builder->computeLevel($this->mediumTree, 2);
    }

    #[Revs(1000), Iterations(5), Warmup(2)]
    public function benchComputeLevelDeep(): void
    {
        // Deepest node in the medium tree
        $lastId = array_key_last($this->mediumTree);
        $this->builder->computeLevel($this->mediumTree, $lastId);
    }

    // -------------------------------------------------------------------------
    // Helper: generate a realistic flat category list
    // -------------------------------------------------------------------------

    /**
     * Produces a flat array keyed by category id.
     * Categories are distributed across $maxDepth levels in a wide-first manner.
     *
     * @param int $count     Total number of categories to generate
     * @param int $maxDepth  Maximum nesting depth
     * @return array<int, array<string, mixed>>
     */
    private function generateFlatCategories(int $count, int $maxDepth): array
    {
        $categories = [];
        $byLevel = [0 => [0]]; // level => [parent ids at that level]

        for ($id = 1; $id <= $count; $id++) {
            // Pick a random level (but never deeper than $maxDepth)
            $level = min((int) floor(log($id, 3)), $maxDepth - 1);
            $parentLevel = max(0, $level - 1);

            $possibleParents = $byLevel[$parentLevel] ?? [0];
            $parentId = $possibleParents[array_rand($possibleParents)];

            $categories[$id] = [
                'id'          => $id,
                'parent_id'   => $parentId,
                'name'        => 'Category ' . $id,
                'description' => 'Description for category ' . $id,
                'lang'        => 'en',
                'active'      => 1,
                'image'       => '',
                'show_home'   => 0,
            ];

            $byLevel[$level][] = $id;
        }

        return $categories;
    }
}
