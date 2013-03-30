<?php
/**
 * Displays the user managment frontend
 *
 * PHP 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Sarah Hermann <sayh@gmx.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['edituser'] || $permission['deluser'] || $permission['adduser']) {
    // set some parameters
    $selectSize        = 10;
    $defaultUserAction = 'list';
    $defaultUserStatus = 'active';

    // what shall we do?
    // actions defined by url: user_action=
    $userAction = PMF_Filter::filterInput(INPUT_GET, 'user_action', FILTER_SANITIZE_STRING, $defaultUserAction);
    // actions defined by submit button
    if (isset($_POST['user_action_deleteConfirm'])) {
        $userAction = 'delete_confirm';
    }
    if (isset($_POST['cancel'])) {
        $userAction = $defaultUserAction;
    }

    // update user rights
    if ($userAction == 'update_rights' && $permission['edituser']) {
        $message    = '';
        $userAction = $defaultUserAction;
        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $user       = new PMF_User($faqConfig);
            $perm       = $user->perm;
            // @todo: Add PMF_Filter::filterInputArray()
            $userRights = isset($_POST['user_rights']) ? $_POST['user_rights'] : array();
            if (!$perm->refuseAllUserRights($userId)) {
                $message .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
            }
            foreach ($userRights as $rightId) {
                $perm->grantUserRight($userId, $rightId);
            }
            $idUser   = $user->getUserById($userId);
            $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                $PMF_LANG['ad_msg_savedsuc_1'],
                $user->getLogin(),
                $PMF_LANG['ad_msg_savedsuc_2']);
            $message .= '<script type="text/javascript">updateUser('.$userId.');</script>';
        }
    }

    // update user data
    if ($userAction == 'update_data' && $permission['edituser']) {
        $message    = '';
        $userAction = $defaultUserAction;
        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $userData                  = array();
            $userData['display_name']  = PMF_Filter::filterInput(INPUT_POST, 'display_name', FILTER_SANITIZE_STRING, '');
            $userData['email']         = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL, '');
            $userData['last_modified'] = PMF_Filter::filterInput(INPUT_POST, 'last_modified', FILTER_SANITIZE_STRING, '');
            $userStatus                = PMF_Filter::filterInput(INPUT_POST, 'user_status', FILTER_SANITIZE_STRING, $defaultUserStatus);

            $user = new PMF_User($faqConfig);
            $user->getUserById($userId);

            $stats = $user->getStatus();
            // set new password an send email if user is switched to active
            if ($stats == 'blocked' && $userStatus == 'active') {
                $consonants  = array("b","c","d","f","g","h","j","k","l","m","n","p","r","s","t","v","w","x","y","z");
                $vowels      = array("a","e","i","o","u");
                $newPassword = '';
                srand((double)microtime()*1000000);
                for ($i = 1; $i <= 4; $i++) {
                    $newPassword .= $consonants[rand(0,19)];
                    $newPassword .= $vowels[rand(0,4)];
                }
                $user->changePassword($newPassword);

                $mail = new PMF_Mail($faqConfig);
                $mail->addTo($userData['email']);
                $mail->subject = '[%sitename%] Login name / activation';
                $mail->message = sprintf("\nName: %s\nLogin name: %s\nNew password: %s\n\n",
                $userData['display_name'],
                $user->getLogin(),
                $newPassword);
                $result = $mail->send();
                unset($mail);
            }

            if (!$user->userdata->set(array_keys($userData), array_values($userData)) or !$user->setStatus($userStatus)) {
                $message .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
            } else {
                $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                    $PMF_LANG['ad_msg_savedsuc_1'],
                    $user->getLogin(),
                    $PMF_LANG['ad_msg_savedsuc_2']);
                $message .= '<script type="text/javascript">updateUser('.$userId.');</script>';
            }
        }
    }

    // delete user confirmation
    if ($userAction == 'delete_confirm' && $permission['deluser']) {
        $message    = '';
        $user       = new PMF_User_CurrentUser($faqConfig);

        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_list_select', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message   .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_user_error_noId']);
            $userAction = $defaultUserAction;
        } else {
            $user->getUserById($userId);
            // account is protected
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $message   .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_user_error_protectedAccount']);
                $userAction = $defaultUserAction;
            } else {
                $twig->loadTemplate('user/delete_confirm.twig')
                    ->display(
                        array(
                            'PMF_LANG'  => $PMF_LANG,
                            'csrfToken' => $user->getCsrfTokenFromSession(),
                            'userId'    => $userId,
                            'userLogin' => $user->getLogin()
                        )
                    );
            }
        }
    }

    // delete user
    if ($userAction == 'delete' && $permission['deluser']) {
        $message    = '';
        $user       = new PMF_User($faqConfig);
        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay   = true;
        $csrfToken  = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false; 
        }
        $userAction = $defaultUserAction;
        if ($userId == 0 && !$csrfOkay) {
            $message .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            if (!$user->getUserById($userId)) {
                $message .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_user_error_noId']);
            }
            if (!$user->deleteUser()) {
                $message .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_user_error_delete']);
            } else {
                // Move the categories ownership to admin (id == 1)
                $oCat = new PMF_Category($faqConfig, array(), false);
                $oCat->setUser($currentAdminUser);
                $oCat->setGroups($currentAdminGroups);
                $oCat->moveOwnership($userId, 1);

                // Remove the user from groups
                if ('medium' == $faqConfig->get('security.permLevel')) {
                    $oPerm = PMF_Perm::selectPerm('medium', $faqConfig);
                    $oPerm->removeFromAllGroups($userId);
                }

                $message .= sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_user_deleted']);
            }
            $userError = $user->error();
            if ($userError != "") {
                $message .= sprintf('<p class="alert alert-error">%s</p>', $userError);
            }
        }
    }

    // save new user
    if ($userAction == 'addsave' && $permission['adduser']) {
        $user                  = new PMF_User($faqConfig);
        $message               = '';
        $messages              = array();
        $user_name             = PMF_Filter::filterInput(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING, '');
        $user_realname         = PMF_Filter::filterInput(INPUT_POST, 'user_realname', FILTER_SANITIZE_STRING, '');
        $user_password         = PMF_Filter::filterInput(INPUT_POST, 'user_password', FILTER_SANITIZE_STRING, '');
        $user_email            = PMF_Filter::filterInput(INPUT_POST, 'user_email', FILTER_VALIDATE_EMAIL);
        $user_password         = PMF_Filter::filterInput(INPUT_POST, 'user_password', FILTER_SANITIZE_STRING, '');
        $user_password_confirm = PMF_Filter::filterInput(INPUT_POST, 'user_password_confirm', FILTER_SANITIZE_STRING, '');
        $csrfOkay              = true;
        $csrfToken             = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false; 
        }

        if ($user_password != $user_password_confirm) {
            $user_password         = '';
            $user_password_confirm = '';
            $messages[]            = $PMF_LANG['ad_user_error_passwordsDontMatch'];
        }

        // check login name
        if (!$user->isValidLogin($user_name)) {
            $user_name  = '';
            $messages[] = $PMF_LANG['ad_user_error_loginInvalid'];
        }
        if ($user->getUserByLogin($user_name)) {
            $user_name  = '';
            $messages[] = $PMF_LANG['ad_adus_exerr'];
        }
        // check realname
        if ($user_realname == '') {
            $user_realname = '';
            $messages[]    = $PMF_LANG['ad_user_error_noRealName'];
        }
        // check e-mail
        if (is_null($user_email)) {
            $user_email = '';
            $messages[] = $PMF_LANG['ad_user_error_noEmail'];
        }

        // ok, let's go
        if (count($messages) == 0 && $csrfOkay) {
            // create user account (login and password)
            if (!$user->createUser($user_name, $user_password)) {
                $messages[] = $user->error();
            } else {
                // set user data (realname, email)
                $user->userdata->set(array('display_name', 'email'), array($user_realname, $user_email));
                // set user status
                $user->setStatus($defaultUserStatus);
            }
        }
        // no errors, show list
        if (count($messages) == 0) {
            $userAction = $defaultUserAction;
            $message    = sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_adus_suc']);
            // display error messages and show form again
        } else {
            $userAction = 'add';
            $message    = '<p class="alert alert-error">';
            foreach ($messages as $err) {
                $message .= $err . '<br />';
            }
            $message .= '</p>';
        }
    }

    if (!isset($message)) {
        $message = '';
    }

    // show new user form
    if ($userAction == 'add' && $permission['adduser']) {
        $twig->loadTemplate('user/add.twig')
            ->display(
                array(
                    'PMF_LANG'            => $PMF_LANG,
                    'csrfToken'           => $user->getCsrfTokenFromSession(),
                    'userEmail'           => isset($user_email) ? $user_email : '',
                    'userName'            => isset($user_name) ? $user_name : '',
                    'userPassword'        => isset($user_password) ? $user_password : '',
                    'userPasswordConfirm' => isset($user_password_confirm) ? $user_password_confirm : '',
                    'userRealName'        => isset($user_realname) ? $user_realname : ''
                )
            );
    }

    // show list of users
    if ($userAction == 'list') {
?>
        <header>
            <h2>
                <i class="icon-user"></i> <?php print $PMF_LANG['ad_user']; ?>
                <div class="pull-right">
                    <a class="btn btn-success" href="?action=user&amp;user_action=add">
                        <i class="icon-plus"></i> <?php print $PMF_LANG["ad_user_add"]; ?>
                    </a>
                    <?php if ($permission['edituser']): ?>
                    <a class="btn btn-info" href="?action=user&amp;user_action=listallusers">
                        <i class="icon-list"></i> <?php print $PMF_LANG['list_all_users']; ?>
                    </a>
                    <?php endif; ?>
                </div>
            </h2>
        </header>

        <script type="text/javascript" src="assets/js/user.js"></script>
        <script type="text/javascript">
        /* <![CDATA[ */

        /**
         * Returns the user data as JSON object
         *
         * @param user_id User ID
         */
        function getUserData(user_id) {
            $('#user_data_table').empty();
            $.getJSON("index.php?action=ajax&ajax=user&ajaxaction=get_user_data&user_id=" + user_id, function(data) {
                $('#update_user_id').val(data.user_id);
                $('#user_status_select').val(data.status);
                $('#user_list_autocomplete').val(data.login);
                $("#user_list_select").val(data.user_id);
                // Append input fields
                $('#user_data_table').append(
                    '<div class="control-group">' +
                        '<label class="control-label"><?php print $PMF_LANG["ad_user_realname"]; ?></label>' +
                        '<div class="controls">' +
                            '<input type="text" name="display_name" value="' + data.display_name + '" required />' +
                        '</div>' +
                    '</div>' +
                    '<div class="control-group">' +
                        '<label class="control-label"><?php print $PMF_LANG["ad_entry_email"]; ?></label>' +
                        '<div class="controls">' +
                            '<input type="email" name="email" value="' + data.email + '" required />' +
                        '</div>' +
                    '</div>' +
                    '<input type="hidden" name="last_modified" value="' + data.last_modified + '" />'
                );
            });
        }
        /* ]]> */
        </script>
        <div id="user_message"><?php print $message; ?></div>

        <div class="row-fluid">
            <div class="span4" id="userAccounts">
                <fieldset>
                    <legend><?php print $PMF_LANG["ad_user_username"]; ?></legend>
                    <form name="user_select" id="user_select" action="?action=user&amp;user_action=delete_confirm"
                          method="post">

                        <label for="user_list_autocomplete"><?php print $PMF_LANG['ad_auth_user']; ?>:</label>
                        <input type="text" id="user_list_autocomplete" name="user_list_search" data-provide="typeahead" />
                        <script type="text/javascript">
                        //<![CDATA[
                        var mappedIds,
                            userNames;
                        $('#user_list_autocomplete').typeahead({
                            source: function (query, process) {
                                return $.get("index.php?action=ajax&ajax=user&ajaxaction=get_user_list", { q: query }, function (data) {
                                    mappedIds = [];
                                    userNames = [];
                                    $.each(data, function(i, user) {
                                        mappedIds[user.name] = user.user_id;
                                        userNames.push(user.name);
                                    });
                                    return process(userNames);
                                });
                            },
                            updater: function(userName) {
                                userId = mappedIds[userName];
                                $("#user_list_select").val(userId);
                                getUserData(userId);
                                getUserRights(userId);
                            }
                        });
                        //]]>
                        </script>
                        <p>
                            <input type="hidden" id="user_list_select" name="user_list_select" value="" />
                            <button class="btn btn-danger" type="submit">
                                <?php print $PMF_LANG['ad_gen_delete']; ?>
                            </button>
                        </p>
                    </form>
                </fieldset>
            </div>
            <div class="span4" id="userDetails">
                <fieldset>
                    <legend id="user_data_legend"><?php print $PMF_LANG["ad_user_profou"]; ?></legend>
                    <form action="?action=user&amp;user_action=update_data" method="post">
                        <input id="update_user_id" type="hidden" name="user_id" value="0" />
                        <p>
                            <label for="user_status_select" class="small">
                                <?php print $PMF_LANG['ad_user_status']; ?>
                            </label>
                            <select id="user_status_select" name="user_status" >
                                <option value="active"><?php print $PMF_LANG['ad_user_active']; ?></option>
                                <option value="blocked"><?php print $PMF_LANG['ad_user_blocked']; ?></option>
                                <option value="protected"><?php print $PMF_LANG['ad_user_protected']; ?></option>
                            </select>
                        </p>
                        <div id="user_data_table"></div><!-- end #user_data_table -->
                        <p>
                            <button class="btn btn-primary" type="submit">
                                <?php print $PMF_LANG["ad_gen_save"]; ?>
                            </button>
                        </p>
                    </form>
                </fieldset>
            </div>
            <div class="span4" id="userRights">
                <form id="rightsForm" action="?action=user&amp;user_action=update_rights" method="post">
                    <fieldset>
                        <legend id="user_rights_legend"><?php print $PMF_LANG["ad_user_rights"]; ?></legend>
                        <input id="rights_user_id" type="hidden" name="user_id" value="0" />

                        <a class="btn btn-small" href="javascript:formCheckAll('rightsForm')">
                            <?php print $PMF_LANG['ad_user_checkall']; ?>
                        </a>
                        <a class="btn btn-small" href="javascript:formUncheckAll('rightsForm')">
                            <?php print $PMF_LANG['ad_user_uncheckall']; ?>
                        </a>

                        <?php foreach ($user->perm->getAllRightsData() as $right): ?>
                        <label class="checkbox">
                            <input id="user_right_<?php print $right['right_id']; ?>" type="checkbox"
                                   name="user_rights[]" value="<?php print $right['right_id']; ?>"/>
                            <?php
                            if (isset($PMF_LANG['rightsLanguage'][$right['name']])) {
                                echo $PMF_LANG['rightsLanguage'][$right['name']];
                            } else {
                                echo $right['description'];
                            }
                            ?>
                        </label>
                        <?php endforeach; ?>

                        <button class="btn btn-primary" type="submit">
                            <?php print $PMF_LANG["ad_gen_save"]; ?>
                        </button>

                    </fieldset>
                </form>
            </div>
        </div>

<?php
        if (isset($_GET['user_id'])) {
            $userId     = PMF_Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, 0);
            echo '<script type="text/javascript">updateUser('.$userId.');</script>';
        }
    }

    // show list of all users
    if ($userAction == 'listallusers' && $permission['edituser']) {

        $allUsers  = $user->getAllUsers();
        $numUsers  = count($allUsers);
        $page      = PMF_Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 0);
        $perPage   = 10;
        $numPages  = ceil($numUsers / $perPage);
        $lastPage  = $page * $perPage;
        $firstPage = $lastPage - $perPage;

        $baseUrl = sprintf(
            '%s?action=user&amp;user_action=listallusers&amp;page=%d',
            PMF_Link::getSystemRelativeUri(),
            $page
        );

        // Pagination options
        $options = array(
            'baseUrl'       => $baseUrl,
            'total'         => $numUsers,
            'perPage'       => $perPage,
            'pageParamName' => 'page'
        );
        $pagination = new PMF_Pagination($faqConfig, $options);
?>
        <header>
            <h2>
                <i class="icon-user"></i> <?php print $PMF_LANG['ad_user']; ?>
                <div class="pull-right">
                    <a class="btn btn-success" href="?action=user&amp;user_action=add">
                        <i class="icon-plus"></i> <?php print $PMF_LANG["ad_user_add"]; ?>
                    </a>
                </div>
            </h2>
        </header>
        <div id="user_message"><?php print $message; ?></div>
        <table class="table table-striped">
        <thead>
            <tr>
                <th><?php print $PMF_LANG['ad_entry_id'] ?></th>
                <th><?php print $PMF_LANG['ad_user_status'] ?></th>
                <th><?php print $PMF_LANG['msgNewContentName'] ?></th>
                <th><?php print $PMF_LANG['ad_auth_user'] ?></th>
                <th><?php print $PMF_LANG['msgNewContentMail'] ?></th>
                <th colspan="2">&nbsp;</th>
            </tr>
        </thead>
        <?php if ($perPage < $numUsers): ?>
        <tfoot>
            <tr>
                <td colspan="7"><?php print $pagination->render(); ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
        <tbody>
        <?php
            $counter = $displayedCounter = 0;
            foreach ($allUsers as $userId) {
                $user->getUserById($userId);

                if ($displayedCounter >= $perPage) {
                    continue;
                }
                $counter++;
                if ($counter <= $firstPage) {
                    continue;
                }
                $displayedCounter++;


            ?>
            <tr class="row_user_id_<?php print $user->getUserId() ?>">
                <td><?php print $user->getUserId() ?></td>
                <td><i class="<?php
                switch($user->getStatus()) {
                    case 'active':
                        echo "icon-ok";
                        break;
                    case 'blocked':
                        echo 'icon-lock';
                        break;
                    case 'protected':
                        echo 'icon-ok-sign';
                        break;
                } ?>"></i></td>
                <td><?php print $user->getUserData('display_name') ?></td>
                <td><?php print $user->getLogin() ?></td>
                <td>
                    <a href="mailto:<?php print $user->getUserData('email') ?>">
                        <?php print $user->getUserData('email') ?>
                    </a>
                </td>
                <td>
                    <a href="?action=user&amp;user_id=<?php print $user->getUserData('user_id')?>" class="btn btn-info">
                        <?php print $PMF_LANG['ad_user_edit'] ?>
                    </a>
                </td>
                <td>
                    <?php if ($user->getStatus() !== 'protected'): ?>
                    <a onclick="deleteUser(<?php print $user->getUserData('user_id') ?>); return false;"
                       href="javascript:;" class="btn btn-danger">
                        <?php print $PMF_LANG['ad_user_delete'] ?>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
            }
            ?>
        </tbody>
        </table>
<script type="text/javascript">
/* <![CDATA[ */
/**
 * Ajax call to delete user
 *
 * @param userId
 */
function deleteUser(userId)
{
    if (confirm('<?php print $PMF_LANG['ad_user_del_3'] ?>')) {
        $.getJSON("index.php?action=ajax&ajax=user&ajaxaction=delete_user&user_id=" + userId,
        function(response) {
            $('#user_message').html(response);
            $('.row_user_id_' + userId).fadeOut('slow');
        });
    }
}
/* ]]> */
</script>
<?php 
    }
} else {
    require 'noperm.php';
}