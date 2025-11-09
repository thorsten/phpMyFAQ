<?php

declare(strict_types=1);

/**
 * The main Sitemap class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-30
 */

namespace phpMyFAQ;

use Exception;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Database\Sqlite3;
use stdClass;

/**
 * Class Sitemap
 *
 * @package phpMyFAQ
 */
class Sitemap
{
    /**
     * User
     */
    private int $user = -1;

    /**
     * Groups.
     *
     * @var int[]
     */
    private array $groups = [];

    /**
     * Flag for Group support.
     */
    private bool $groupSupport = false;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly Configuration $configuration,
    ) {
        if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
            $this->groupSupport = true;
        }
    }

    public function setUser(int $userId = -1): void
    {
        $this->user = $userId;
    }

    /**
     * @param int[] $groups
     */
    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    /**
     * Returns all available first letters.
     * @return stdClass[]
     */
    public function getAllFirstLetters(): array
    {
        if ($this->groupSupport) {
            $permPart = sprintf(
                '( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))',
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups),
            );
        } else {
            $permPart = sprintf('( fdu.user_id = %d OR fdu.user_id = -1 )', $this->user);
        }

        $letters = [];

        $query = sprintf(
            "
            SELECT
                DISTINCT UPPER(%s(fd.thema, 1, 1)) AS letters
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.lang = '%s'
            AND
                fd.active = 'yes'
            AND
                %s
            ORDER BY
                letters",
            $this->configuration->getDb() instanceof Sqlite3 ? 'SUBSTR' : 'SUBSTRING',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage()),
            $permPart,
        );

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $letter = new stdClass();
            $letter->letter = Strings::strtoupper($row->letters);

            if (Strings::preg_match("/^\w+/iu", $letter->letter) !== 0) {
                $url = sprintf(
                    '%sindex.php?action=sitemap&letter=%s&lang=%s',
                    $this->configuration->getDefaultUrl(),
                    $letter->letter,
                    $this->configuration->getLanguage()->getLanguage(),
                );
                $link = new Link($url, $this->configuration);
                $letter->url = $link->toString();
            }

            $letters[] = $letter;
        }

        return $letters;
    }

    /**
     * Returns all records from the current first letter.
     *
     * @param string $letter Letter
     * @return stdClass[]
     * @throws Exception
     * @throws CommonMarkException
     */
    public function getFaqsFromLetter(string $letter = 'A'): array
    {
        if ($this->groupSupport) {
            $permPart = sprintf(
                '( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))',
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups),
            );
        } else {
            $permPart = sprintf('( fdu.user_id = %d OR fdu.user_id = -1 )', $this->user);
        }

        $letter = Strings::strtoupper($this->configuration->getDb()->escape(Strings::substr($letter, 0, 1)));

        $faqs = [];

        $query = sprintf(
            "
                    SELECT
                        fd.thema AS thema,
                        fd.id AS id,
                        fd.lang AS lang,
                        fcr.category_id AS category_id,
                        fd.content AS snap
                    FROM
                        %sfaqcategoryrelations fcr,
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqdata_group AS fdg
                    ON
                        fd.id = fdg.record_id
                    LEFT JOIN
                        %sfaqdata_user AS fdu
                    ON
                        fd.id = fdu.record_id
                    WHERE
                        fd.id = fcr.record_id
                    AND
                        %s(fd.thema, 1, 1) = '%s'
                    AND
                        fd.lang = '%s'
                    AND
                        fd.active = 'yes'
                    AND
                        %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $this->configuration->getDb() instanceof Sqlite3 ? 'SUBSTR' : 'SUBSTRING',
            $this->configuration->getDb()->escape($letter),
            $this->configuration->getLanguage()->getLanguage(),
            $permPart,
        );

        $result = $this->configuration->getDb()->query($query);
        $oldId = 0;
        $commonMarkConverter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            if ($oldId !== $row->id) {
                $faq = new stdClass();
                $faq->question = $row->thema;
                $url = sprintf(
                    '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $row->category_id,
                    $row->id,
                    $row->lang,
                );

                $link = new Link($url, $this->configuration);
                $link->itemTitle = $row->thema;
                $faq->url = $link->toString();

                if ($this->configuration->get(item: 'main.enableMarkdownEditor')) {
                    $answer = strip_tags($commonMarkConverter->convert($row->snap)->getContent());
                } else {
                    $answer = strip_tags((string) $row->snap);
                }

                $faq->answer = Utils::chopString($answer, 25);

                $faqs[] = $faq;
            }

            $oldId = $row->id;
        }

        return $faqs;
    }
}
