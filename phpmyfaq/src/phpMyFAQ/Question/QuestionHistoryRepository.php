<?php

/**
 * Question History Repository.
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
 * @since     2026-08-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Question;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\QuestionHistoryEntity;

readonly class QuestionHistoryRepository
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Records a question lifecycle event.
     */
    public function add(QuestionHistoryEntity $questionHistoryEntity): bool
    {
        $db = $this->configuration->getDb();
        $query = sprintf(
            "
            INSERT INTO
                %sfaqquestion_history
            (id, question_id, question_lang, event_type, user_id, username, faq_id, created)
                VALUES
            (%d, %d, '%s', '%s', %d, '%s', %d, '%s')",
            Database::getTablePrefix(),
            $db->nextId(Database::getTablePrefix() . 'faqquestion_history', column: 'id'),
            $questionHistoryEntity->getQuestionId(),
            $db->escape($questionHistoryEntity->getQuestionLanguage()),
            $questionHistoryEntity->getEventType()->value,
            $questionHistoryEntity->getUserId(),
            $db->escape($questionHistoryEntity->getUsername()),
            $questionHistoryEntity->getFaqId(),
            date(format: 'YmdHis'),
        );

        return (bool) $db->query($query);
    }

    /**
     * Returns all lifecycle events of a question, oldest first.
     *
     * @return array<int, array<string, int|string>>
     */
    public function getByQuestion(int $questionId, string $language): array
    {
        $events = [];
        $db = $this->configuration->getDb();

        $query = sprintf(
            "
            SELECT
                id, question_id, question_lang, event_type, user_id, username, faq_id, created
            FROM
                %sfaqquestion_history
            WHERE
                question_id = %d
            AND
                question_lang = '%s'
            ORDER BY
                created ASC, id ASC",
            Database::getTablePrefix(),
            $questionId,
            $db->escape($language),
        );

        $result = $db->query($query);
        if ($result !== false) {
            while (true) {
                $row = $db->fetchObject($result);
                if (!is_object($row)) {
                    break;
                }

                $events[] = [
                    'id' => (int) $row->id,
                    'question_id' => (int) $row->question_id,
                    'question_lang' => (string) $row->question_lang,
                    'event_type' => (string) $row->event_type,
                    'user_id' => (int) $row->user_id,
                    'username' => (string) $row->username,
                    'faq_id' => (int) $row->faq_id,
                    'created' => (string) $row->created,
                ];
            }
        }

        return $events;
    }
}
