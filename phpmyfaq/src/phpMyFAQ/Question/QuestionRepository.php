<?php

/**
 * Question Repository.
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
 * @since     2025-11-10
 */

declare(strict_types=1);

namespace phpMyFAQ\Question;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\QuestionEntity;

readonly class QuestionRepository
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Adds a new question to the database.
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
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqquestions', column: 'id'),
            $this->configuration->getDb()->escape($questionEntity->getLanguage()),
            $this->configuration->getDb()->escape($questionEntity->getUsername()),
            $this->configuration->getDb()->escape($questionEntity->getEmail()),
            $questionEntity->getCategoryId(),
            $this->configuration->getDb()->escape($questionEntity->getQuestion()),
            date(format: 'YmdHis'),
            $questionEntity->isVisible() ? 'Y' : 'N',
            0,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes a question from the database.
     *
     * @param int $questionId Question ID
     * @param string $language Language code
     */
    public function delete(int $questionId, string $language): bool
    {
        $delete = sprintf(
            "DELETE FROM %sfaqquestions WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $questionId,
            $language,
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Returns a question by ID and language.
     *
     * @param int $questionId Question ID
     * @param string $language Language code
     * @return array<string, int|string>
     */
    public function getById(int $questionId, string $language): array
    {
        $question = [];

        $query = sprintf("
            SELECT
                 id, lang, username, email, category_id, question, created, is_visible
            FROM
                %sfaqquestions
            WHERE
                id = %d
            AND
                lang = '%s'", Database::getTablePrefix(), $questionId, $language);

        if (
            ($result = $this->configuration->getDb()->query($query)) && ($row =
                $this->configuration->getDb()->fetchObject($result))
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
     * Returns all questions for a given language.
     *
     * @param string $language Language code
     * @param bool $showAll Whether to show all questions or only visible ones
     * @return array<int, array<string, int|string>>
     */
    public function getAll(string $language, bool $showAll = true): array
    {
        $questions = [];
        $langFilter = $language !== ''
            ? sprintf("AND lang = '%s'", $this->configuration->getDb()->escape($language))
            : '';

        $query = sprintf(
            '
            SELECT
                id, lang, username, email, category_id, question, created, answer_id, is_visible
            FROM
                %sfaqquestions
            WHERE
                1 = 1 %s %s
            ORDER BY
                created ASC',
            Database::getTablePrefix(),
            $langFilter,
            $showAll === false ? " AND is_visible = 'Y'" : '',
        );

        if ($result = $this->configuration->getDb()->query($query)) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $questions[] = [
                    'id' => (int) $row->id,
                    'lang' => $row->lang,
                    'username' => $row->username,
                    'email' => $row->email,
                    'category_id' => (int) $row->category_id,
                    'question' => $row->question,
                    'created' => $row->created,
                    'answer_id' => (int) $row->answer_id,
                    'is_visible' => $row->is_visible,
                ];
            }
        }

        return $questions;
    }

    /**
     * Returns the visibility of a question.
     *
     * @param int $questionId Question ID
     * @param string $language Language code
     */
    public function getVisibility(int $questionId, string $language): string
    {
        $query = sprintf(
            "SELECT is_visible FROM %sfaqquestions WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $questionId,
            $language,
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
     *
     * @param int $questionId Question ID
     * @param string $isVisible Visibility status
     * @param string $language Language code
     */
    public function setVisibility(int $questionId, string $isVisible, string $language): bool
    {
        $query = sprintf(
            "UPDATE %sfaqquestions SET is_visible = '%s' WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($isVisible),
            $questionId,
            $language,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Updates the answer_id field for a question.
     *
     * @param int $openQuestionId Question ID
     * @param int $faqId FAQ ID
     * @param int $categoryId Category ID
     */
    public function updateQuestionAnswer(int $openQuestionId, int $faqId, int $categoryId): bool
    {
        $query = sprintf(
            'UPDATE %sfaqquestions SET answer_id = %d, category_id= %d WHERE id= %d',
            Database::getTablePrefix(),
            $faqId,
            $categoryId,
            $openQuestionId,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }
}
