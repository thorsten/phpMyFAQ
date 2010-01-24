<?php
/**
 * Layout rendering class for FAQ related stuff
 *
 * PHP Version 5.2.0
 * 
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-08
 */

/**
 * PMF_Faq_Layout
 * 
 * @Faq  phpMyFAQ
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-08
 */
 class PMF_Faq_Layout
 {
    private $categoryLayout = null;
    
    public function __construct(PMF_Category_Layout $categoryLayout)
    {
        $this->categoryLayout = $categoryLayout;
    }
    
    /**
     * Renders the open questions table
     *
     * @param array $questions Array of open questions
     */
    public function renderOpenQuestions(Array $questions)
    {
        global $PMF_LANG, $sids;
        
        $html = '';
        
        if (count($questions)) {
            foreach ($questions as $question) {
                if ($question->is_visible == 'N') {
                    continue;
                }
                $html .= '<tr class="openquestions">';
                $html .= sprintf('<td valign="top" nowrap="nowrap">%s<br /><a href="mailto:%s">%s</a></td>',
                    PMF_Date::createIsoDate($question->date),
                    PMF_Mail::safeEmail($question->email),
                    $question->username);
                $html .= sprintf('<td valign="top"><strong>%s:</strong><br />%s</td>',
                    $this->categoryLayout->renderBreadcrumb(array($question->category_id)),
                    strip_tags($question->question));
                $html .= sprintf('<td valign="top"><a href="?%saction=add&amp;question=%d&amp;cat=%d">%s</a></td>',
                    $sids,
                    $question->id,
                    $question->category_id,
                    $PMF_LANG['msg2answer']);
                $html .= '</tr>';
            }
        } else {
            $output = sprintf('<tr><td colspan="3">%s</td></tr>', $PMF_LANG['msgNoQuestionsAvailable']);
        }
        
        return $html;
    }
 }