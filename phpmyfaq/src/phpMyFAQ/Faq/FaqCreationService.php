<?php

/**
 * FAQ Creation Service
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
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Forms;
use phpMyFAQ\Question;
use phpMyFAQ\Strings;
use phpMyFAQ\User\CurrentUser;

/**
 * Service class for FAQ creation business logic.
 */
final class FaqCreationService
{
    private Category $category;
    private Question $questionObject;
    private Forms $forms;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly CurrentUser $currentUser,
        private readonly array $currentGroups,
    ) {
        $this->category = new Category($this->configuration, $this->currentGroups);
        $this->questionObject = new Question($this->configuration);
        $this->forms = new Forms($this->configuration);
    }

    /**
     * Prepares data for adding a new FAQ
     *
     * @return array<string, mixed>
     */
    public function prepareAddFaqData(?int $selectedQuestion, int $selectedCategory): array
    {
        $question = '';
        $readonly = '';
        $displayFullForm = false;

        // Load question data if a question ID is provided
        if ($selectedQuestion !== null) {
            $questionData = $this->questionObject->get($selectedQuestion);
            $question = $questionData['question'];
            if (Strings::strlen($question) !== 0) {
                $readonly = ' readonly';
            }

            // Display full form even if the user switched off single fields because of use together with answering
            // open questions
            $displayFullForm = true;
        }

        // Build category tree
        $this->category->transform(0);
        $this->category->buildCategoryTree();

        // Get form data
        $formData = $this->forms->getFormData(FormIds::ADD_NEW_FAQ->value);

        // Get all category IDs
        $categories = $this->category->getAllCategoryIds();

        return [
            'question' => $question,
            'readonly' => $readonly,
            'displayFullForm' => $displayFullForm,
            'selectedQuestion' => $selectedQuestion,
            'selectedCategory' => $selectedCategory,
            'categories' => $this->category->getCategoryTree(),
            'formData' => $formData,
            'noCategories' => $categories === [],
        ];
    }

    /**
     * Checks if the current user can add FAQs
     */
    public function canUserAddFaq(): bool
    {
        // Guests can add if allowed in configuration
        if ($this->currentUser->getUserId() === -1) {
            return (bool) $this->configuration->get('records.allowNewFaqsForGuests');
        }

        // Logged-in users need the FAQ_ADD permission
        return $this->currentUser->perm->hasPermission($this->currentUser->getUserId(), PermissionType::FAQ_ADD->value);
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
