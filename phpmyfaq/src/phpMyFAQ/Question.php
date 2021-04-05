<?php

/**
 * The main Question class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
class Question
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * Question constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Adds a new question.
     *
     * @param string[] $questionData
     * @return bool
     */
    public function addQuestion(array $questionData): bool
    {
        $query = sprintf(
            "
            INSERT INTO
                %sfaqquestions
            (id, lang, username, email, category_id, question, created, is_visible, answer_id)
                VALUES
            (%d, '%s', '%s', '%s', %d, '%s', '%s', '%s', %d)",
            Database::getTablePrefix(),
            $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqquestions', 'id'),
            $this->config->getLanguage()->getLanguage(),
            $this->config->getDb()->escape($questionData['username']),
            $this->config->getDb()->escape($questionData['email']),
            $questionData['category_id'],
            $this->config->getDb()->escape($questionData['question']),
            date('YmdHis'),
            $questionData['is_visible'],
            0
        );
        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Deletes a question for the table "faqquestions".
     *
     * @param  int $questionId
     * @return bool
     */
    public function deleteQuestion(int $questionId): bool
    {
        $delete = sprintf(
            "DELETE FROM %sfaqquestions WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $questionId,
            $this->config->getLanguage()->getLanguage()
        );

        $this->config->getDb()->query($delete);

        return true;
    }

    /**
     * Returns a new question.
     *
     * @param int $questionId
     * @return array<string, int|string>
     */
    public function getQuestion(int $questionId): array
    {
        $question = [
            'id' => 0,
            'lang' => '',
            'username' => '',
            'email' => '',
            'category_id' => '',
            'question' => '',
            'created' => '',
            'is_visible' => '',
        ];

        if (!is_int($questionId)) {
            return $question;
        }

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
            $this->config->getLanguage()->getLanguage()
        );

        if ($result = $this->config->getDb()->query($query)) {
            if ($row = $this->config->getDb()->fetchObject($result)) {
                $question = [
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
        }

        return $question;
    }

    /**
     * Returns all open questions.
     *
     * @param bool $showAll
     * @return QuestionEntity[]
     */
    public function getAllOpenQuestions(bool $showAll = true): array
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
            $this->config->getLanguage()->getLanguage(),
            ($showAll === false ? " AND is_visible = 'Y'" : '')
        );

        if ($result = $this->config->getDb()->query($query)) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $question = new QuestionEntity();
                $question
                    ->setId($row->id)
                    ->setLang($row->lang)
                    ->setUsername($row->username)
                    ->setEmail($row->email)
                    ->setCategoryId($row->category_id)
                    ->setQuestion($row->question)
                    ->setCreated($row->created)
                    ->setAnswerId($row->answer_id)
                    ->setIsVisible($row->is_visible);

                $questions[] = $question;
            }
        }

        return $questions;
    }


    /**
     * Returns the visibility of a question.
     *
     * @param  int $questionId
     * @return string
     */
    public function getVisibility(int $questionId): string
    {
        $query = sprintf(
            "SELECT is_visible FROM %sfaqquestions WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $questionId,
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result) > 0) {
            $row = $this->config->getDb()->fetchObject($result);

            return $row->is_visible;
        }

        return '';
    }

    /**
     * Sets the visibility of a question.
     *
     * @param  int    $questionId
     * @param  string $isVisible
     * @return bool
     */
    public function setVisibility(int $questionId, string $isVisible): bool
    {
        $query = sprintf(
            "UPDATE %sfaqquestions SET is_visible = '%s' WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $isVisible,
            $questionId,
            $this->config->getLanguage()->getLanguage()
        );

        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Updates field answer_id in the table "faqquestion".
     *
     * @param  int $openQuestionId
     * @param  int $faqId
     * @param  int $categoryId
     * @return bool
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

        return $this->config->getDb()->query($query);
    }
}
