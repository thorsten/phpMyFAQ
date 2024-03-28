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
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Mail;
use phpMyFAQ\Search\SearchResultSet;
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
}
