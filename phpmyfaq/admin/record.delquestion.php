<?php
/**
 * Delete open questions
 * 
 * PHP Version 5.2
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
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['delquestion']) {

    $questionId     = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $delete         = PMF_Filter::filterInput(INPUT_GET, 'delete', FILTER_SANITIZE_STRING, 'no');
    $categoryData   = new PMF_Category_Tree_DataProvider_SingleQuery($LANGCODE);
    $categoryLayout = new PMF_Category_Layout(new PMF_Category_Tree_Helper(new PMF_Category_Tree($categoryData)));
    
    if ($delete == 'yes') {
        $faq->deleteQuestion($questionId);
        print $PMF_LANG['ad_entry_delsuc'];
    } else {
    	$toggle = PMF_Filter::filterInput(INPUT_GET, 'is_visible', FILTER_SANITIZE_STRING);
        if ($toggle == 'toggle') {
            $is_visible = $faq->getVisibilityOfQuestion($questionId);
            if (!is_null($is_visible)) {
                $faq->setVisibilityOfQuestion($questionId, ($is_visible == 'N' ? 'Y' : 'N'));
            }
        }

        printf("<h2>%s</h2>", $PMF_LANG['msgOpenQuestions']);

        $openquestions = $faq->getAllOpenQuestions();
        if (count($openquestions) > 0) {
?>
    <table id="tableOpenQuestions">
    <thead>
        <tr>
            <th><?php print $PMF_LANG['ad_entry_author']; ?></th>
            <th><?php print $PMF_LANG['ad_entry_theme']; ?></th>
            <th><?php print $PMF_LANG['ad_entry_visibility']; ?>?</th>
            <th><?php print $PMF_LANG['ad_gen_delete']; ?>?</th>
        </tr>
    </thead>
    <tbody>
<?php
            foreach ($openquestions as $question) {
?>
        <tr>
            <td><?php print PMF_Date::createIsoDate($question['date']); ?><br /><a href="mailto:<?php print $question['email']; ?>"><?php print $question['user']; ?></a></td>
            <td><?php print $categoryLayout->renderBreadcrumb($categoryData->getPath($question['category'])) . ":<br />".$question['question']; ?></td>
            <td><a href="?action=question&amp;id=<?php print $question['id']; ?>&amp;is_visible=toggle"><?php print (('Y' == $question['is_visible']) ? $PMF_LANG['ad_gen_no'] : $PMF_LANG['ad_gen_yes']); ?>!</a><br /></td>
            <td><a href="?action=question&amp;id=<?php print $question['id']; ?>&amp;delete=yes"><?php print $PMF_LANG['ad_gen_delete']; ?>!</a><br /><a href="?action=takequestion&amp;id=<?php print $question['id']; ?>"><?php print $PMF_LANG['ad_ques_take']; ?></a></td>
        </tr>
<?php
            }
?>
    </tbody>
    </table>
<?php
        } else {
            print $PMF_LANG['msgNoQuestionsAvailable'];
        }
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}