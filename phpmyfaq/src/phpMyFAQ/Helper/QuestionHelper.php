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
 * @copyright 2019-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-26
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Date;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Mail;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\Utils;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Class QuestionHelper
 * @package phpMyFAQ\Helper
 */
readonly class QuestionHelper
{
    private Category $category;

    /**
     * QuestionHelper constructor.
     */
    public function __construct(private Configuration $configuration)
    {
    }

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

    public function renderOpenQuestions(): string
    {
        global $sids;

        $date = new Date($this->configuration);
        $mail = new Mail($this->configuration);

        $query = sprintf(
            "SELECT COUNT(id) AS num FROM %sfaqquestions WHERE lang = '%s' AND is_visible != 'Y'",
            Database::getTablePrefix(),
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);
        $numOfInvisibles = $row->num;

        if ($numOfInvisibles > 0) {
            $extraout = sprintf(
                '<tr><td colspan="3"><small>%s %s</small></td></tr>',
                Translation::get('msgQuestionsWaiting'),
                $numOfInvisibles
            );
        } else {
            $extraout = '';
        }

        $query = sprintf(
            "SELECT * FROM %sfaqquestions WHERE lang = '%s' AND is_visible = 'Y' ORDER BY created ASC",
            Database::getTablePrefix(),
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);
        $output = '';

        if ($result && $this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $output .= '<tr class="openquestions">';
                $output .= sprintf(
                    '<td><small>%s</small><br><a href="mailto:%s">%s</a></td>',
                    $date->format(Date::createIsoDate($row->created)),
                    $mail->safeEmail($row->email),
                    Strings::htmlentities($row->username)
                );
                $output .= sprintf(
                    '<td><strong>%s:</strong><br>%s</td>',
                    isset($this->category->categoryName[$row->category_id]['name']) ?
                        Strings::htmlentities($this->category->categoryName[$row->category_id]['name']) :
                        '',
                    Strings::htmlentities($row->question)
                );
                if ($this->configuration->get('records.enableCloseQuestion') && $row->answer_id) {
                    $output .= sprintf(
                        '<td><a id="PMF_openQuestionAnswered" href="?%saction=faq&amp;cat=%d&amp;id=%d">%s</a></td>',
                        $sids,
                        $row->category_id,
                        $row->answer_id,
                        Translation::get('msg2answerFAQ')
                    );
                } else {
                    $output .= sprintf(
                        '<td class="text-end">' .
                        '<a class="btn btn-primary" href="?%saction=add&amp;question=%d&amp;cat=%d">%s</a></td>',
                        $sids,
                        $row->id,
                        $row->category_id,
                        Translation::get('msg2answer')
                    );
                }

                $output .= '</tr>';
            }
        } else {
            $output = sprintf(
                '<tr><td colspan="3">%s</td></tr>',
                Translation::get('msgNoQuestionsAvailable')
            );
        }

        return $output . $extraout;
    }

    public function setCategory(Category $category): QuestionHelper
    {
        $this->category = $category;
        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
