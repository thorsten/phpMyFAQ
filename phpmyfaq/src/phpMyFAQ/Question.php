<?php

/**
 * The main Question class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Entity\QuestionEntity;
use phpMyFAQ\Question\QuestionRepository;

/**
 * Class Question
 *
 * @package phpMyFAQ
 */
readonly class Question
{
    private QuestionRepository $repository;

    /**
     * Question constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
        $this->repository = new QuestionRepository($configuration);
    }

    /**
     * Adds a new question.
     */
    public function add(QuestionEntity $questionEntity): bool
    {
        return $this->repository->add($questionEntity);
    }

    /**
     * Deletes a question for the table "faqquestions".
     */
    public function delete(int $questionId): bool
    {
        return $this->repository->delete($questionId, $this->configuration->getLanguage()->getLanguage());
    }

    /**
     * Returns a new question.
     *
     * @return array<string, int|string>
     */
    public function get(int $questionId): array
    {
        return $this->repository->getById($questionId, $this->configuration->getLanguage()->getLanguage());
    }

    /**
     * Returns all open questions.
     *
     * @return QuestionEntity[]
     */
    public function getAll(bool $showAll = true): array
    {
        $questions = [];
        $rows = $this->repository->getAll($this->configuration->getLanguage()->getLanguage(), $showAll);

        foreach ($rows as $row) {
            $question = new QuestionEntity();
            $question
                ->setId($row['id'])
                ->setLanguage($row['lang'])
                ->setUsername($row['username'])
                ->setEmail($row['email'])
                ->setCategoryId($row['category_id'])
                ->setQuestion($row['question'])
                ->setCreated(Date::createIsoDate($row['created']))
                ->setAnswerId($row['answer_id'])
                ->setIsVisible($row['is_visible'] === 'Y');

            $questions[] = $question;
        }

        return $questions;
    }

    /**
     * Returns the visibility of a question.
     */
    public function getVisibility(int $questionId): string
    {
        return $this->repository->getVisibility($questionId, $this->configuration->getLanguage()->getLanguage());
    }

    /**
     * Sets the visibility of a question.
     */
    public function setVisibility(int $questionId, string $isVisible): bool
    {
        return $this->repository->setVisibility(
            $questionId,
            $isVisible,
            $this->configuration->getLanguage()->getLanguage(),
        );
    }

    /**
     * Updates field answer_id in the table "faqquestion".
     */
    public function updateQuestionAnswer(int $openQuestionId, int $faqId, int $categoryId): bool
    {
        return $this->repository->updateQuestionAnswer($openQuestionId, $faqId, $categoryId);
    }
}
