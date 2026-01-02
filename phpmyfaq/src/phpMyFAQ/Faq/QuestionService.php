<?php

/**
 * Question Service
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
 * @since     2026-01-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\Forms\FormIds;
use phpMyFAQ\Forms;
use phpMyFAQ\User\CurrentUser;

/**
 * Service class for question-related business logic.
 */
final class QuestionService
{
    private Category $category;
    private Forms $forms;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly CurrentUser $currentUser,
        private readonly array $currentGroups,
    ) {
        $this->category = new Category($this->configuration, $this->currentGroups);
        $this->forms = new Forms($this->configuration);
    }

    /**
     * Prepares data for asking a new question
     *
     * @return array<string, mixed>
     */
    public function prepareAskQuestionData(int $categoryId): array
    {
        // Build category tree
        $this->category->transform(0);
        $this->category->buildCategoryTree();

        // Get form data
        $formData = $this->forms->getFormData(FormIds::ASK_QUESTION->value);

        // Get all category IDs
        $categories = $this->category->getAllCategoryIds();

        return [
            'selectedCategory' => $categoryId,
            'categories' => $this->category->getCategoryTree(),
            'formData' => $formData,
            'noCategories' => $categories === [],
        ];
    }

    /**
     * Checks if the current user can ask questions
     */
    public function canUserAskQuestion(): bool
    {
        // Guests can ask if allowed in configuration
        if ($this->currentUser->getUserId() === -1) {
            return (bool) $this->configuration->get('records.allowQuestionsForGuests');
        }

        // Logged-in users can always ask questions
        return true;
    }

    /**
     * Gets the default user email
     */
    public function getDefaultUserEmail(): string
    {
        return $this->currentUser->getUserId() > 0 ? (string) $this->currentUser->getUserData('email') : '';
    }

    /**
     * Gets the default user name
     */
    public function getDefaultUserName(): string
    {
        return $this->currentUser->getUserId() > 0 ? (string) $this->currentUser->getUserData('display_name') : '';
    }

    /**
     * Gets the category object
     */
    public function getCategory(): Category
    {
        return $this->category;
    }
}
