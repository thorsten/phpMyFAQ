<?php
/**
 * Displays the user managment frontend
 * 
 * PHP 5.2
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
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Sarah Hermann <sayh@gmx.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
    $loginMinLength    = 4;

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
    if ($userAction == 'update_rights') {
        $message    = '';
        $userAction = $defaultUserAction;
        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $user       = new PMF_User();
            $perm       = $user->perm;
            // @todo: Add PMF_Filter::filterInputArray()
            $userRights = isset($_POST['user_rights']) ? $_POST['user_rights'] : array();
            if (!$perm->refuseAllUserRights($userId)) {
                $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
            }
            foreach ($userRights as $rightId) {
                $perm->grantUserRight($userId, $rightId);
            }
            $idUser   = $user->getUserById($userId);
            $message .= sprintf('<p class="success">%s <strong>%s</strong> %s</p>',
                $PMF_LANG['ad_msg_savedsuc_1'],
                $user->getLogin(),
                $PMF_LANG['ad_msg_savedsuc_2']);
            $message .= '<script type="text/javascript">updateUser('.$userId.');</script>';
        }
    }

    // update user data
    if ($userAction == 'update_data') {
        $message    = '';
        $userAction = $defaultUserAction;
        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $userData                  = array();
            $userData['display_name']  = PMF_Filter::filterInput(INPUT_POST, 'display_name', FILTER_SANITIZE_STRING, '');
            $userData['email']         = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL, '');
            $userData['last_modified'] = PMF_Filter::filterInput(INPUT_POST, 'last_modified', FILTER_SANITIZE_STRING, '');
            $userStatus                = PMF_Filter::filterInput(INPUT_POST, 'user_status', FILTER_SANITIZE_STRING, $defaultUserStatus);

            $user = new PMF_User();
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

                $mail = new PMF_Mail();
                $mail->addTo($userData['email']);
                $mail->subject = '[%sitename%] Username / activation';
                $mail->message = sprintf("\nUsername: %s\nLoginname: %s\nNew Password: %s\n\n",
                $userData['display_name'],
                $user->getLogin(),
                $newPassword);
                $result = $mail->send();
                unset($mail);
            }

            if (!$user->userdata->set(array_keys($userData), array_values($userData)) or !$user->setStatus($userStatus)) {
                $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
            } else {
                $message .= sprintf('<p class="success">%s <strong>%s</strong> %s</p>',
                    $PMF_LANG['ad_msg_savedsuc_1'],
                    $user->getLogin(),
                    $PMF_LANG['ad_msg_savedsuc_2']);
                $message .= '<script type="text/javascript">updateUser('.$userId.');</script>';
            }
        }
    }

    // delete user confirmation
    if ($userAction == 'delete_confirm') {
        $message    = '';
        $user       = new PMF_User_CurrentUser();

        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_list_select', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message   .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_noId']);
            $userAction = $defaultUserAction;
        } else {
            $user->getUserById($userId);
            // account is protected
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $message   .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_protectedAccount']);
                $userAction = $defaultUserAction;
            } else {
?>
        <header>
            <h2><?php print $PMF_LANG['ad_user_deleteUser']; ?> <strong><?php print $user->getLogin(); ?></strong></h2>
        </header>
        <p><?php print $PMF_LANG["ad_user_del_3"].' '.$PMF_LANG["ad_user_del_1"].' '.$PMF_LANG["ad_user_del_2"]; ?></p>
        <form action ="?action=user&amp;user_action=delete" method="post">
            <input type="hidden" name="user_id" value="<?php print $userId; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
            <p>
                <input class="reset" type="submit" name="cancel" value="<?php print $PMF_LANG["ad_gen_no"]; ?>" />
                <input type="submit" value="<?php print $PMF_LANG["ad_gen_yes"]; ?>" />
            </p>
        </form>
<?php
            }
        }
    }

    // delete user
    if ($userAction == 'delete') {
        $message    = '';
        $user       = new PMF_User();
        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay   = true;
        $csrfToken  = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false; 
        }
        $userAction = $defaultUserAction;
        if ($userId == 0 && !$csrfOkay) {
            $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            if (!$user->getUserById($userId)) {
                $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_noId']);
            }
            if (!$user->deleteUser()) {
                $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_delete']);
            } else {
                // Move the categories ownership to admin (id == 1)
                $oCat = new PMF_Category($current_admin_user, $current_admin_groups, false);
                $oCat->moveOwnership($userId, 1);

                // Remove the user from groups
                if ('medium' == PMF_Configuration::getInstance()->get('main.permLevel')) {
                    $oPerm = PMF_Perm::selectPerm('medium');
                    $oPerm->removeFromAllGroups($userId);
                }

                $message .= sprintf('<p class="success">%s</p>', $PMF_LANG['ad_user_deleted']);
            }
            $userError = $user->error();
            if ($userError != "") {
                $message .= '<p>ERROR: '.$userError.'</p>';
            }
        }
    }

    // save new user
    if ($userAction == 'addsave') {
        $user                  = new PMF_User();
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
        $user->setLoginMinLength($loginMinLength);
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
            $message    = sprintf('<p class="success">%s</p>', $PMF_LANG['ad_adus_suc']);
            // display error messages and show form again
        } else {
            $userAction = 'add';
            $message    = '<p class="error">';
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
    if ($userAction == 'add') {
?>
        <header>
            <h2><?php print $PMF_LANG["ad_adus_adduser"]; ?></h2>
        </header>

        <div id="user_message"><?php print $message; ?></div>
        <div id="user_create">

            <form action="?action=user&amp;user_action=addsave" method="post">
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />

            <p>
                <label for="user_name"><?php print $PMF_LANG["ad_adus_name"]; ?></label>
                <input type="text" name="user_name" id="user_name"
                       value="<?php print (isset($user_name) ? $user_name : ''); ?>" required="required" tabindex="1" />
            </p>

            <p>
                <label for="user_realname"><?php print $PMF_LANG["ad_user_realname"]; ?></label>
                <input type="text" name="user_realname" id="user_realname"
                       value="<?php print (isset($user_realname) ? $user_realname : ''); ?>" required="required" tabindex="2" />
            </p>

            <p>
                <label for="user_email"><?php print $PMF_LANG["ad_entry_email"]; ?></label>
                <input type="email" name="user_email" id="user_email"
                       value="<?php print (isset($user_email) ? $user_email : ''); ?>" required="required" tabindex="3" />
            </p>

            <p>
                <label for="password"><?php print $PMF_LANG["ad_adus_password"]; ?></label>
                <input type="password" name="user_password" id="password"
                       value="<?php print (isset($user_password) ? $user_password : ''); ?>" required="required" tabindex="4" />
            </p>

            <p>
                <label for="password_confirm"><?php print $PMF_LANG["ad_passwd_con"]; ?></label>
                <input type="password" name="user_password_confirm" id="password_confirm"
                       value="<?php print (isset($user_password_confirm) ? $user_password_confirm : ''); ?>" required="required" tabindex="5" />
            </p>

            <p>
                <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_gen_save"]; ?>" tabindex="6" />
                <input name="cancel" type="submit" value="<?php print $PMF_LANG['ad_gen_cancel']; ?>" tabindex="7" />
            </p>
        </form>
</div> <!-- end #user_create -->
<?php
    }

    // show list of users
    if ($userAction == 'list') {
?>
        
        <header>
            <h2><?php print $PMF_LANG['ad_user']; ?></h2>
        </header>

        <script type="text/javascript" src="js/user.js"></script>
        <script type="text/javascript">
/* <![CDATA[ */

/**
 * Returns the user data as JSON object
 *
 * @param integer user_id User ID
 */
function getUserData(user_id)
{
    $('#user_data_table').empty();
    $.getJSON("index.php?action=ajax&ajax=user&ajaxaction=get_user_data&user_id=" + user_id,
        function(data) {
            $('#update_user_id').val(data.user_id);
            $('#user_status_select').val(data.status);
            $('#user_list_autocomplete').val(data.login);
            $("#user_list_select").val(data.user_id);
            // Append input fields
            $('#user_data_table').append('<p>' +
                                         '<label class="small"><?php print $PMF_LANG["ad_user_realname"]; ?></label>' +
                                         '<input type="text" name="display_name" value="' + data.display_name + '" />' +
                                         '</p>' +
                                         '<p>' +
                                         '<label class="small"><?php print $PMF_LANG["ad_entry_email"]; ?></label>' +
                                         '<input type="email" name="email" value="' + data.email + '" />' +
                                         '</p>' +
                                         '<p>' +
                                         '<label class="small"><?php print $PMF_LANG["ad_user_lastModified"]; ?></label>' +
                                         '<input type="text" name="last_modified" value="' + data.last_modified + '" />' +
                                         '</p>');
        });
}
        /* ]]> */
        </script>
        <div id="user_message"><?php print $message; ?></div>
        <div id="userInterface">
            <div id="userAccounts">
                <div id="userList">
                <fieldset>
                    <legend><?php print $PMF_LANG["ad_user_username"]; ?></legend>
                    <form name="user_select" id="user_select" action="?action=user&amp;user_action=delete_confirm" method="post">

                        <input type="text" id="user_list_autocomplete" name="user_list_search" />
                        <script type="text/javascript">
                        //<![CDATA[
                            $('#user_list_autocomplete').autocomplete("index.php?action=ajax&ajax=user&ajaxaction=get_user_list", { width: 180, selectFirst: true } );
                            $('#user_list_autocomplete').result(function(event, data, formatted) {
                                var user_id = data[1];
                                $("#user_list_select").val(user_id);
                                getUserData(user_id);
                                getUserRights(user_id);
                            });
                            //]]>
                        </script>
                        <p>
                            <input type="hidden" id="user_list_select" name="user_list_select" value="" />
                            <input type="submit" value="<?php print $PMF_LANG['ad_gen_delete']; ?>" />
                        </p>
                    </form>
                </fieldset>
                <p>
                    [ <a href="?action=user&amp;user_action=add"><?php print $PMF_LANG["ad_user_add"]; ?></a> ]<br/>
                    [ <a href="?action=user&amp;user_action=listallusers"><?php print $PMF_LANG['list_all_users']; ?></a> ]
                </p>
                </div> <!-- end #userList -->
            </div> <!-- end #userAccounts -->
            <div id="userDetails">
                <fieldset>
                    <legend id="user_data_legend"><?php print $PMF_LANG["ad_user_profou"]; ?></legend>
                    <form action="?action=user&amp;user_action=update_data" method="post">
                        <input id="update_user_id" type="hidden" name="user_id" value="0" />
                        <p>
                            <label for="user_status_select" class="small"><?php print $PMF_LANG['ad_user_status']; ?></label>
                            <select id="user_status_select" name="user_status" >
                                <option value="active"><?php print $PMF_LANG['ad_user_active']; ?></option>
                                <option value="blocked"><?php print $PMF_LANG['ad_user_blocked']; ?></option>
                                <option value="protected"><?php print $PMF_LANG['ad_user_protected']; ?></option>
                            </select>
                        </p>
                        <div id="user_data_table"></div><!-- end #user_data_table -->
                        <p>
                            <input type="submit" value="<?php print $PMF_LANG["ad_gen_save"]; ?>" tabindex="6" />
                        </p>
                    </form>
                </fieldset>
            </div> <!-- end #userDetails -->
            <div id="userRights">
                <fieldset>
                    <legend id="user_rights_legend"><?php print $PMF_LANG["ad_user_rights"]; ?></legend>
                    <form id="rightsForm" action="?action=user&amp;user_action=update_rights" method="post">
                        <input id="rights_user_id" type="hidden" name="user_id" value="0" />
                        <div>
                            <span><a href="javascript:form_checkAll('rightsForm')"><?php print $PMF_LANG['ad_user_checkall']; ?></a></span>
                            <span><a href="javascript:form_uncheckAll('rightsForm')"><?php print $PMF_LANG['ad_user_uncheckall']; ?></a></span>
                        </div>
                        <table id="user_rights_table">
            <?php foreach ($user->perm->getAllRightsData() as $right) { ?>
                            <tr>
                                <td><input id="user_right_<?php print $right['right_id']; ?>" type="checkbox" name="user_rights[]" value="<?php print $right['right_id']; ?>"/></td>
                                <td>&nbsp;<?php print (isset($PMF_LANG['rightsLanguage'][$right['name']]) ? $PMF_LANG['rightsLanguage'][$right['name']] : $right['description']); ?></td>
                            </tr>
            <?php } ?>
                        </table>
                        <div class="button_row">
                            <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_gen_save"]; ?>" />
                        </div>
                    </form>
                </fieldset>
            </div> <!-- end #userRights -->
        </div>
<?php 
        if (isset($_GET['user_id'])) {
            $userId     = PMF_Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, 0);
            echo '<script type="text/javascript">updateUser('.$userId.');</script>';
        }
    }

    // show list of all users
    if ($userAction == 'listallusers') {
?>
        <header>
            <h2><?php print $PMF_LANG['ad_user']; ?></h2>
        </header>
        <div id="user_message"><?php print $message; ?></div>
        <table style="width: 760px;">
        <thead>
            <tr>
                <th><?php print $PMF_LANG['ad_entry_id']?>:</th>
                <th><?php print $PMF_LANG['msgNewContentName']?></th>
                <th><?php print $PMF_LANG['ad_user_username'] ?></th>
                <th><?php print $PMF_LANG['msgNewContentMail']?></th>
                <th><?php print $PMF_LANG['ad_entry_action']?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($user->getAllUsers() as $userId) { $user->getUserById($userId); ?>

            <tr>
                <td><?php print $user->getUserData('user_id')?></td>
                <td><?php print $user->getUserData('display_name')?></td>
                <td><?php print $user->getLogin() ?></td>
                <td><?php print $user->getUserData('email')?></td>
                <td><a href="?action=user&amp;user_id=<?php echo $user->getUserData('user_id')?>"><?php echo $PMF_LANG['ad_user_edit']?></a></td>
            </tr>
            <?php } ?>
        
        </tbody>
        </table>
<?php 
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}