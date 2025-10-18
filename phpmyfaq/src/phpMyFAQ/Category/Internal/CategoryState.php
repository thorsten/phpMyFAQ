<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Internal;

/**
 * Internal state holder for Category to reduce property count on the main class.
 */
final class CategoryState
{
    /** @var array<int, array<string, mixed>> */
    public array $catTree = [];

    /** @var array<int, array<int, array<string, mixed>>>> */
    public array $children = [];

    /** @var array<int, int> */
    public array $owner = [];

    /** @var array<int, int> */
    public array $moderators = [];
}
