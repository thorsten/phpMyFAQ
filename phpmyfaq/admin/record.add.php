<?php
/**
 * Adds a record in the database, handles the preview and checks for missing
 * category entries.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use Abraham\TwitterOAuth\TwitterOAuth;
use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Helper\LinkverifierHelper;
use phpMyFAQ\Link;
use phpMyFAQ\Logging;
use phpMyFAQ\Notification;
use phpMyFAQ\Services\Twitter;
use phpMyFAQ\Tags;
use phpMyFAQ\Visits;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'editbt') || $user->perm->checkRight($user->getUserId(), 'addbt')) {

    // FAQ data
    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question = Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
    $categories = Filter::filterInputArray(
        INPUT_POST,
        array(
            'rubrik' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
            ),
        )
    );
    $recordLang = Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
    $tags = Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $active = Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $sticky = Filter::filterInput(INPUT_POST, 'sticky', FILTER_SANITIZE_STRING);
    if ($faqConfig->get('main.enableMarkdownEditor')) {
        $content = Filter::filterInput(INPUT_POST, 'answer', FILTER_UNSAFE_RAW);
    } else {
        $content = Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_SPECIAL_CHARS);
    }
    $keywords = Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
    $author = Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
    $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $recordId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $solutionId = Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
    $revisionId = Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT);
    $changed = Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_STRING);
    $notes = Filter::filterInput(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    // Permissions

    $permissions = [];
    if ('all' === Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING)) {
        $permissions += array(
            'restricted_user' => array(
                -1,
            ),
        );
    } else {
        $permissions += array(
            'restricted_user' => array(
                Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT),
            ),
        );
    }

    if ('all' === Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING)) {
        $permissions += array(
            'restricted_groups' => array(
                -1,
            ),
        );
    } else {
        $permissions += Filter::filterInputArray(
            INPUT_POST,
            array(
                'restricted_groups' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_REQUIRE_ARRAY,
                ),
            )
        );
    }

    if (!isset($categories['rubrik'])) {
        $categories['rubrik'] = [];
    }

    if (!is_null($question) && !is_null($categories['rubrik'])) {
        // new entry
        $logging = new Logging($faqConfig);
        $logging->logAdmin($user, 'Beitragcreatesave');
        printf(
            '<header class="row"><div class="col-lg-12"><h2 class="page-header">%s</h2></div></header>',
            $PMF_LANG['ad_entry_aor']
        );

        $category = new Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $tagging = new Tags($faqConfig);

        $recordData = array(
            'lang' => $recordLang,
            'active' => $active,
            'sticky' => (!is_null($sticky) ? 1 : 0),
            'thema' => Filter::removeAttributes(html_entity_decode($question)),
            'content' => Filter::removeAttributes(html_entity_decode($content)),
            'keywords' => $keywords,
            'author' => $author,
            'email' => $email,
            'comment' => (!is_null($comment) ? 'y' : 'n'),
            'date' => date('YmdHis'),
            'dateStart' => (empty($dateStart) ? '00000000000000' : str_replace('-', '', $dateStart).'000000'),
            'dateEnd' => (empty($dateEnd) ? '99991231235959' : str_replace('-', '', $dateEnd).'235959'),
            'linkState' => '',
            'linkDateCheck' => 0,
            'notes' => Filter::removeAttributes($notes)
        );

        // Add new record and get that ID
        $recordId = $faq->addRecord($recordData);


        if ($recordId) {
            // Create ChangeLog entry
            $faq->createChangeEntry($recordId, $user->getUserId(), nl2br($changed), $recordData['lang']);
            // Create the visit entry

            $visits = new Visits($faqConfig);
            $visits->logViews($recordId);

            // Insert the new category relations
            $faq->addCategoryRelations($categories['rubrik'], $recordId, $recordData['lang']);
            // Insert the tags
            if ($tags != '') {
                $tagging->saveTags($recordId, explode(',', trim($tags)));
            }

            // Add user permissions
            $faq->addPermission('user', $recordId, $permissions['restricted_user']);
            $category->addPermission('user', $categories['rubrik'], $permissions['restricted_user']);
            // Add group permission
            if ($faqConfig->get('security.permLevel') !== 'basic') {
                $faq->addPermission('group', $recordId, $permissions['restricted_groups']);
                $category->addPermission('group', $categories['rubrik'], $permissions['restricted_groups']);
            }

            // Open question answered
            $openQuestionId = Filter::filterInput(INPUT_POST, 'openQuestionId', FILTER_VALIDATE_INT);
            if (0 !== $openQuestionId) {
                if ($faqConfig->get('records.enableDeleteQuestion')) { // deletes question
                    $faq->deleteQuestion($openQuestionId);
                } else { // adds this faq record id to the related open question
                    $faq->updateQuestionAnswer($openQuestionId, $recordId, $categories['rubrik'][0]);
                }

                $url = sprintf(
                    '%s?action=faq&cat=%d&id=%d&artlang=%s',
                    $faqConfig->getDefaultUrl(),
                    $categories['rubrik'][0],
                    $recordId,
                    $recordLang
                );
                $oLink = new Link($url, $faqConfig);

                // notify the user who added the question
                $notifyEmail = Filter::filterInput(INPUT_POST, 'notifyEmail', FILTER_SANITIZE_EMAIL);
                $notifyUser = Filter::filterInput(INPUT_POST, 'notifyUser', FILTER_SANITIZE_STRING);

                $notification = new Notification($faqConfig);
                $notification->sendOpenQuestionAnswered($notifyEmail, $notifyUser, $oLink->toString());
            }

            // Call Link Verification
            LinkverifierHelper::linkOndemandJavascript($recordId, $recordData['lang']);

            // If Elasticsearch is enabled, index new FAQ document
            if ($faqConfig->get('search.enableElasticsearch')) {
                $esInstance = new Elasticsearch($faqConfig);
                $esInstance->index(
                    [
                        'id' => $recordId,
                        'lang' => $recordLang,
                        'solution_id' => $solutionId,
                        'question' => $recordData['thema'],
                        'answer' => $recordData['content'],
                        'keywords' => $keywords,
                        'category_id' => $categories['rubrik'][0]
                    ]
                );
            }

            // Callback to Twitter if enabled
            if ($faqConfig->get('socialnetworks.enableTwitterSupport')) {

                $connection = new TwitterOAuth(
                    $faqConfig->get('socialnetworks.twitterConsumerKey'),
                    $faqConfig->get('socialnetworks.twitterConsumerSecret'),
                    $faqConfig->get('socialnetworks.twitterAccessTokenKey'),
                    $faqConfig->get('socialnetworks.twitterAccessTokenSecret')
                );

                $link = sprintf(
                    'index.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $category,
                    $recordId,
                    $recordLang
                );
                $oLink = new Link($faqConfig->getDefaultUrl().$link, $faqConfig);
                $oLink->itemTitle = $question;
                $link = $oLink->toString();

                if ($connection) {
                    $twitter = new Twitter($connection);
                    $twitter->addPost($question, $tags, $link);
                }
            }

            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_entry_savedsuc']);
            ?>
    <script>
        (function() {
            setTimeout(function() {
                window.location = "index.php?action=editentry&id=<?= $recordId;
            ?>&lang=<?= $recordData['lang'] ?>";
            }, 5000);
        })();
    </script>

<?php

        } else {
            printf(
                '<p class="alert alert-danger">%s</p>',
                $PMF_LANG['ad_entry_savedfail'].$faqConfig->getDb()->error()
            );
        }
    } else {
        printf(
            '<header class="row"><div class="col-lg-12"><h2 class="page-header"><i aria-hidden="true" class="fa fa-pencil"></i> %s</h2></div></header>',
            $PMF_LANG['ad_entry_aor']
        );
        printf(
            '<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_entryins_fail']
        );
        ?>
    <form action="?action=editpreview" method="post">
    <input type="hidden" name="question"            value="<?= Strings::htmlspecialchars($question) ?>">
    <input type="hidden" name="content" class="mceNoEditor" value="<?= Strings::htmlspecialchars($content) ?>">
    <input type="hidden" name="lang"                value="<?= $recordLang ?>">
    <input type="hidden" name="keywords"            value="<?= $keywords ?>">
    <input type="hidden" name="tags"                value="<?= $tags ?>">
    <input type="hidden" name="author"              value="<?= $author ?>">
    <input type="hidden" name="email"               value="<?= $email ?>">
    <?php
        if (is_array($categories['rubrik'])) {
            foreach ($categories['rubrik'] as $key => $_categories) {
                echo '    <input type="hidden" name="rubrik['.$key.']" value="'.$_categories.'" />';
            }
        }
    ?>
    <input type="hidden" name="solution_id"         value="<?= $solutionId ?>">
    <input type="hidden" name="revision"            value="<?= $revisionId ?>">
    <input type="hidden" name="active"              value="<?= $active ?>">
    <input type="hidden" name="changed"             value="<?= $changed ?>">
    <input type="hidden" name="comment"             value="<?= $comment ?>">
    <input type="hidden" name="dateStart"           value="<?= $dateStart ?>">
    <input type="hidden" name="dateEnd"             value="<?= $dateEnd ?>">
    <input type="hidden" name="userpermission"      value="<?= $user_permission ?>">
    <input type="hidden" name="restricted_users"    value="<?= $permissions['restricted_user'] ?>">
    <input type="hidden" name="grouppermission"     value="<?= $group_permission ?>">
    <input type="hidden" name="restricted_group"    value="<?= $permissions['restricted_groups'] ?>">
    <input type="hidden" name="notes"               value="<?= $notes ?>">
    <p class="text-center">
        <button class="btn btn-primary" type="submit" name="submit">
            <?= $PMF_LANG['ad_entry_back'] ?>
        </button>
    </p>
    </form>
<?php
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
