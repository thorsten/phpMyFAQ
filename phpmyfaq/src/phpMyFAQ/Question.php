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

namespace phpMyFAQ;

use phpMyFAQ\Entity\QuestionEntity;

/**
 * Class Question
 *
 * @package phpMyFAQ
 */
readonly class Question
{
    /**
     * Question constructor.
     */
    public function __construct(private Configuration $configuration)
    {
    }

    /**
     * Adds a new question.
     */
    public function add(QuestionEntity $questionEntity): bool
    {
        $query = sprintf(
            "
            INSERT INTO
                %sfaqquestions
            (id, lang, username, email, category_id, question, created, is_visible, answer_id)
                VALUES
            (%d, '%s', '%s', '%s', %d, '%s', '%s', '%s', %d)",
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqquestions', 'id'),
            $this->configuration->getDb()->escape($questionEntity->getLanguage()),
            $this->configuration->getDb()->escape($questionEntity->getUsername()),
            $this->configuration->getDb()->escape($questionEntity->getEmail()),
            $questionEntity->getCategoryId(),
            $this->configuration->getDb()->escape($questionEntity->getQuestion()),
            date('YmdHis'),
            $questionEntity->isVisible() ? 'Y' : 'N',
            0
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes a question for the table "faqquestions".
     */
    public function delete(int $questionId): bool
    {
        $delete = sprintf(
            "DELETE FROM %sfaqquestions WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $questionId,
            $this->configuration->getLanguage()->getLanguage()
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Returns a new question.
     *
     * @return array<string, int|string>
     */
    public function get(int $questionId): array
    {
        $question = [];

        $query = sprintf(
            "
            SELECT
                 id, lang, username, email, category_id, question, created, is_visible
            FROM
                %sfaqquestions
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $questionId,
            $this->configuration->getLanguage()->getLanguage()
        );

        if (
            ($result = $this->configuration->getDb()->query($query)) &&
            ($row = $this->configuration->getDb()->fetchObject($result))
        ) {
            return [
                'id' => $row->id,
                'lang' => $row->lang,
                'username' => $row->username,
                'email' => $row->email,
                'category_id' => $row->category_id,
                'question' => $row->question,
                'created' => $row->created,
                'is_visible' => $row->is_visible,
            ];
        }

        return $question;
    }

    /**
     * Returns all open questions.
     *
     * @return QuestionEntity[]
     */
    public function getAll(bool $showAll = true): array
    {
        $questions = [];

        $query = sprintf(
            "
            SELECT
                id, lang, username, email, category_id, question, created, answer_id, is_visible
            FROM
                %sfaqquestions
            WHERE
                lang = '%s'
                %s
            ORDER BY 
                created ASC",
            Database::getTablePrefix(),
            $this->configuration->getLanguage()->getLanguage(),
            ($showAll === false ? " AND is_visible = 'Y'" : '')
        );

        if ($result = $this->configuration->getDb()->query($query)) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $question = new QuestionEntity();
                $question
                    ->setId($row->id)
                    ->setLanguage($row->lang)
                    ->setUsername($row->username)
                    ->setEmail($row->email)
                    ->setCategoryId($row->category_id)
                    ->setQuestion($row->question)
                    ->setCreated(Date::createIsoDate($row->created))
                    ->setAnswerId($row->answer_id)
                    ->setIsVisible($row->is_visible === 'Y');

                $questions[] = $question;
            }
        }

        return $questions;
    }


    /**
     * Returns the visibility of a question.
     */
    public function getVisibility(int $questionId): string
    {
        $query = sprintf(
            "SELECT is_visible FROM %sfaqquestions WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $questionId,
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) > 0) {
            $row = $this->configuration->getDb()->fetchObject($result);

            return $row->is_visible;
        }

        return '';
    }

    /**
     * Sets the visibility of a question.
     */
    public function setVisibility(int $questionId, string $isVisible): bool
    {
        $query = sprintf(
            "UPDATE %sfaqquestions SET is_visible = '%s' WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($isVisible),
            $questionId,
            $this->configuration->getLanguage()->getLanguage()
        );

        $this->configuration->getDb()->query($query);

        return true;
    }

    /**
     * Updates field answer_id in the table "faqquestion".
     */
    public function updateQuestionAnswer(int $openQuestionId, int $faqId, int $categoryId): bool
    {
        $query = sprintf(
            'UPDATE %sfaqquestions SET answer_id = %d, category_id= %d WHERE id= %d',
            Database::getTablePrefix(),
            $faqId,
            $categoryId,
            $openQuestionId
        );

        return $this->configuration->getDb()->query($query);
    }
}
