<?php

/**
 * Questions helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-26
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Database;
use phpMyFAQ\Date;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Mail;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Utils;
use stdClass;

/**
 * Class QuestionHelper
 * @package phpMyFAQ\Helper
 */
class QuestionHelper extends AbstractHelper
{
    public function generateSmartAnswer(SearchResultSet $faqSearchResult): string
    {
        $plr = new Plurals();
        $smartAnswer = sprintf(
            '<h5>%s</h5>',
            $plr->getMsg('plmsgSearchAmount', $faqSearchResult->getNumberOfResults())
        );

        $smartAnswer .= '<ul>';
        foreach ($faqSearchResult->getResultSet() as $result) {
            $url = sprintf(
                '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
                $this->configuration->getDefaultUrl(),
                $result->category_id,
                $result->id,
                $result->lang
            );
            $link = new Link($url, $this->configuration);
            $link->text = Utils::chopString($result->question, 15);
            $link->itemTitle = $result->question;

            $faqHelper = new FaqHelper($this->configuration);
            $smartAnswer .= sprintf(
                '<li>%s<br><small class="pmf-search-preview">%s...</small></li>',
                $link->toHtmlAnchor(),
                $faqHelper->renderAnswerPreview($result->answer, 10)
            );
        }
        $smartAnswer .= '</ul>';

        return $smartAnswer;
    }

    public function getOpenQuestions(): stdClass
    {
        $date = new Date($this->configuration);
        $mail = new Mail($this->configuration);

        $query = sprintf(
            "SELECT COUNT(id) AS num FROM %sfaqquestions WHERE lang = '%s' AND is_visible != 'Y'",
            Database::getTablePrefix(),
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        $openQuestions = new stdClass();
        $openQuestions->numberInvisibleQuestions = $row->num;

        $query = sprintf(
            "SELECT * FROM %sfaqquestions WHERE lang = '%s' AND is_visible = 'Y' ORDER BY created ASC",
            Database::getTablePrefix(),
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);

        if ($result && $this->configuration->getDb()->numRows($result) > 0) {
            $openQuestions->numberQuestions = $this->configuration->getDb()->numRows($result);
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $question = new stdClass();
                $question->id = $row->id;
                $question->date = $date->format(Date::createIsoDate($row->created));
                $question->email = $mail->safeEmail($row->email);
                $question->userName = $row->username;
                $question->categoryId = $row->category_id;
                $question->categoryName = $this->getCategory()->categoryName[$row->category_id]['name'] ?? '';
                $question->question = $row->question;
                $question->answerId = $row->answer_id;

                $openQuestions->questions[] = $question;
            }
        }

        return $openQuestions;
    }
}
