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
 * @copyright 2005-2010 phpMyFAQ Team
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
    $selectSize         = 10;
    $defaultUserAction  = 'list';
    $defaultUserStatus  = 'active';
    $loginMinLength     = 4;

    $errorMessages = array(
        'addUser_password'           => $PMF_LANG['ad_user_error_password'],
        'addUser_passwordsDontMatch' => $PMF_LANG['ad_user_error_passwordsDontMatch'],
        'addUser_loginExists'        => $PMF_LANG["ad_adus_exerr"],
        'addUser_loginInvalid'       => $PMF_LANG['ad_user_error_loginInvalid'],
        'addUser_noEmail'            => $PMF_LANG['ad_user_error_noEmail'],
        'addUser_noRealName'         => $PMF_LANG['ad_user_error_noRealName'],
        'delUser'                    => $PMF_LANG['ad_user_error_delete'],
        'delUser_noId'               => $PMF_LANG['ad_user_error_noId'],
        'delUser_protectedAccount'   => $PMF_LANG['ad_user_error_protectedAccount'],
        'updateUser'                 => $PMF_LANG['ad_msg_mysqlerr'],
        'updateUser_noId'            => $PMF_LANG['ad_user_error_noId'],
        'updateRights'               => $PMF_LANG['ad_msg_mysqlerr'],
        'updateRights_noId'          => $PMF_LANG['ad_user_error_noId']);

    $successMessages = array(
        'addUser'                    => $PMF_LANG["ad_adus_suc"],
        'delUser'                    => $PMF_LANG["ad_user_deleted"],
        'updateUser'                 => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2'],
        'updateRights'               => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2']);

    $text = array(
        'header'                     => $PMF_LANG['ad_user'],
        'selectUser'                 => $PMF_LANG["ad_user_username"],
        'addUser'                    => $PMF_LANG["ad_adus_adduser"],
        'addUser_confirm'            => $PMF_LANG["ad_gen_save"],
        'addUser_cancel'             => $PMF_LANG['ad_gen_cancel'],
        'addUser_link'               => $PMF_LANG["ad_user_add"],
        'addUser_name'               => $PMF_LANG["ad_adus_name"],
        'addUser_displayName'        => $PMF_LANG["ad_user_realname"],
        'addUser_email'              => $PMF_LANG["ad_entry_email"],
        'addUser_password'           => $PMF_LANG["ad_adus_password"],
        'addUser_password2'          => $PMF_LANG["ad_passwd_con"],
        'delUser'                    => $PMF_LANG['ad_user_deleteUser'],
        'delUser_button'             => $PMF_LANG['ad_gen_delete'],
        'delUser_question'           => $PMF_LANG["ad_user_del_3"]." ".$PMF_LANG["ad_user_del_1"]." ".$PMF_LANG["ad_user_del_2"],
        'delUser_confirm'            => $PMF_LANG["ad_gen_yes"],
        'delUser_cancel'             => $PMF_LANG["ad_gen_no"],
        'changeUser'                 => $PMF_LANG["ad_user_profou"],
        'changeUser_submit'          => $PMF_LANG["ad_gen_save"],
        'changeUser_status'          => $PMF_LANG['ad_user_status'],
        'changeRights'               => $PMF_LANG["ad_user_rights"],
        'changeRights_submit'        => $PMF_LANG["ad_gen_save"],
        'changeRights_checkAll'      => $PMF_LANG['ad_user_checkall'],
        'changeRights_uncheckAll'    => $PMF_LANG['ad_user_uncheckall'],
        'listAllUsers_link'          => $PMF_LANG['list_all_users']);

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
            $message .= '<p class="error">'.$errorMessages['updateRights_noId'].'</p>';
        } else {
            $user       = new PMF_User();
            $perm       = $user->perm;
            // @todo: Add PMF_Filter::filterInputArray()
            $userRights = isset($_POST['user_rights']) ? $_POST['user_rights'] : array();
            if (!$perm->refuseAllUserRights($userId)) {
                $message .= '<p class="error">'.$errorMessages['updateRights'].'</p>';
            }
            foreach ($userRights as $rightId) {
                $perm->grantUserRight($userId, $rightId);
            }
            $idUser   = $user->getUserById($userId);
            $message .= '<p class="success">'.sprintf($successMessages['updateRights'], $user->getLogin()).'</p>';
            $message .= '<script type="text/javascript">updateUser('.$userId.');</script>';
        }
    }

    // update user data
    if ($userAction == 'update_data') {
        $message    = '';
        $userAction = $defaultUserAction;
        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= '<p class="error">'.$errorMessages['updateUser_noId'].'</p>';
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
                $message .= '<p class="error">'.$errorMessages['updateUser'].'</p>';
            } else {
                $message .= '<p class="success">'.sprintf($successMessages['updateUser'], $user->getLogin()).'</p>';
                $message .= '<script type="text/javascript">updateUser('.$userId.');</script>';
            }
        }
    }

    // delete user confirmation
    if ($userAction == 'delete_confirm') {
        $message    = '';
        $user       = new PMF_User();

        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_list_select', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= '<p class="error">'.$errorMessages['delUser_noId'].'</p>';
            $userAction = $defaultUserAction;
        } else {
            $user->getUserById($userId);
            // account is protected
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $userAction = $defaultUserAction;
                $message .= '<p class="error">'.$errorMessages['delUser_protectedAccount'].'</p>';
            } else {
?>
<h2><?php print $text['header']; ?></h2>
<div id="user_confirmDelete">
    <fieldset>
        <legend><?php print $text['delUser']; ?></legend>
        <strong><?php print $user->getLogin(); ?></strong>
        <p><?php print $text['delUser_question']; ?></p>
        <form action ="?action=user&amp;user_action=delete" method="post">
            <input type="hidden" name="user_id" value="<?php print $userId; ?>" />
            <div class="button_row">
                <input class="reset" type="submit" name="cancel" value="<?php print $text['delUser_cancel']; ?>" />
                <input class="submit" type="submit" value="<?php print $text['delUser_confirm']; ?>" />
            </div>
        </form>
    </fieldset>
</div>
<?php
            }
        }
    }

    // delete user
    if ($userAction == 'delete') {
        $message    = '';
        $user       = new PMF_User();
        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $userAction = $defaultUserAction;
        if ($userId == 0) {
            $message .= '<p class="error">'.$errorMessages['delUser_noId'].'</p>';
        } else {
            if (!$user->getUserById($userId)) {
                $message .= '<p class="error">'.$errorMessages['delUser_noId'].'</p>';
            }
            if (!$user->deleteUser()) {
                $message .= '<p class="error">'.$errorMessages['delUser'].'</p>';
            } else {
                // Move the categories ownership to admin (id == 1)
                $oCat = new PMF_Category($current_admin_user, $current_admin_groups, false);
                $oCat->moveOwnership($userId, 1);

                // Remove the user from groups
                if ('medium' == PMF_Configuration::getInstance()->get('main.permLevel')) {
                    $oPerm = PMF_Perm::selectPerm('medium');
                    $oPerm->removeFromAllGroups($userId);
                }

                $message .= '<p class="success">'.$successMessages['delUser'].'</p>';
            }
            $userError = $user->error();
            if ($userError != "") {
                $message .= '<p>ERROR: '.$userError.'</p>';
            }
        }
    }

    // save new user
    if ($userAction == 'addsave') {
        $user     = new PMF_User();
        $message  = '';
        $messages = array();
        // check input data
        $user_name             = PMF_Filter::filterInput(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING, '');
        $user_realname         = PMF_Filter::filterInput(INPUT_POST, 'user_realname', FILTER_SANITIZE_STRING, '');
        $user_password         = PMF_Filter::filterInput(INPUT_POST, 'user_password', FILTER_SANITIZE_STRING, '');
        $user_email            = PMF_Filter::filterInput(INPUT_POST, 'user_email', FILTER_VALIDATE_EMAIL);
        $user_password         = PMF_Filter::filterInput(INPUT_POST, 'user_password', FILTER_SANITIZE_STRING, '');
        $user_password_confirm = PMF_Filter::filterInput(INPUT_POST, 'user_password_confirm', FILTER_SANITIZE_STRING, '');

        if ($user_password != $user_password_confirm) {
            $user_password         = '';
            $user_password_confirm = '';
            $messages[]            = $errorMessages['addUser_passwordsDontMatch'];
        }

        // check login name
        $user->setLoginMinLength($loginMinLength);
        if (!$user->isValidLogin($user_name)) {
            $user_name  = '';
            $messages[] = $errorMessages['addUser_loginInvalid'];
        }
        if ($user->getUserByLogin($user_name)) {
            $user_name  = '';
            $messages[] = $errorMessages['addUser_loginExists'];
        }
        // check realname
        if ($user_realname == "") {
            $user_realname = '';
            $messages[]    = $errorMessages['addUser_noRealName'];
        }
        // check e-mail
        if (is_null($user_email)) {
            $user_email = '';
            $messages[] = $errorMessages['addUser_noEmail'];
        }

        // ok, let's go
        if (count($messages) == 0) {
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
            $message = '<p class="success">'.$successMessages['addUser'].'</p>';
            // display error messages and show form again
        } else {
            $userAction = 'add';
            foreach ($messages as $err) {
                $message .= '<p class="error">'.$err.'</p>';
            }
        }
    }

    if (!isset($message)) {
        $message = '';
    }

    // show new user form
    if ($userAction == 'add') {
?>
<h2><?php print $text['header']; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="user_create">
    <fieldset>
        <legend><?php print $text['addUser']; ?></legend>
        <form action="?action=user&amp;user_action=addsave" method="post">
            <label class="left" for="user_name"><?php print $text['addUser_name']; ?></label>
            <input type="text" name="user_name" value="<?php print (isset($user_name) ? $user_name : ''); ?>" tabindex="1" /><br />

            <label class="left" for="user_realname"><?php print $text['addUser_displayName']; ?></label>
            <input type="text" name="user_realname" value="<?php print (isset($user_realname) ? $user_realname : ''); ?>" tabindex="2" /><br />

            <label class="left" for="user_email"><?php print $text['addUser_email']; ?></label>
            <input type="text" name="user_email" value="<?php print (isset($user_email) ? $user_email : ''); ?>" tabindex="3" /><br />

            <label class="left" for="password"><?php print $text['addUser_password']; ?></label>
            <input type="password" name="user_password" value="<?php print (isset($user_password) ? $user_password : ''); ?>" tabindex="4" /><br />

            <label class="left" for="password_confirm"><?php print $text['addUser_password2']; ?></label>
            <input type="password" name="user_password_confirm" value="<?php print (isset($user_password_confirm) ? $user_password_confirm : ''); ?>" tabindex="5" /><br />

            <input style="margin-left: 190px;" class="submit" type="submit" value="<?php print $text['addUser_confirm']; ?>" tabindex="6" />
            <input class="submit" name="cancel" type="submit" value="<?php print $text['addUser_cancel']; ?>" tabindex="7" /><br />
        </form>
    </fieldset>
</div> <!-- end #user_create -->
<?php
    }

    // show list of users
    if ($userAction == 'list') {
?>
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
            $('#user_data_table').append('<br /><label><?php print $PMF_LANG["ad_user_realname"]; ?></label>');
            $('#user_data_table').append('<input type="text" class="input_row" name="display_name" value="' + data.display_name + '" />');
            $('#user_data_table').append('<br /><label><?php print $PMF_LANG["ad_entry_email"]; ?></label>');
            $('#user_data_table').append('<input type="text" class="input_row" name="email" value="' + data.email + '" />');
            $('#user_data_table').append('<br /><label><?php print $PMF_LANG["ad_user_lastModified"]; ?></label>');
            $('#user_data_table').append('<input type="text" class="input_row" name="last_modified" value="' + data.last_modified + '" />');
            
        });
}
/* ]]> */
</script>
<h2><?php print $text['header']; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="user_accounts">
    <div id="user_list">
        <fieldset>
            <legend><?php print $text['selectUser']; ?></legend>
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
                <div class="button_row">
                    <input type="hidden" id="user_list_select" name="user_list_select" value="" />
                    <input class="submit" type="submit" value="<?php print $text['delUser_button']; ?>" tabindex="2" />
                </div>
            </form>
        </fieldset>
        <p>
            [ <a href="?action=user&amp;user_action=add"><?php print $text['addUser_link']; ?></a> ]<br/>
            [ <a href="?action=user&amp;user_action=listallusers"><?php print $text['listAllUsers_link']; ?></a> ]        
        </p>
    </div> <!-- end #user_list -->
</div> <!-- end #user_accounts -->
<div id="user_data">
    <fieldset>
        <legend id="user_data_legend"><?php print $text['changeUser']; ?></legend>
        <form action="?action=user&amp;user_action=update_data" method="post">
            <input id="update_user_id" type="hidden" name="user_id" value="0" />
            <div class="input_row">
                <label for="user_status_select"><?php print $text['changeUser_status']; ?></label>
                <select id="user_status_select" name="user_status" >
                    <option value="active"><?php print $PMF_LANG['ad_user_active']; ?></option>
                    <option value="blocked"><?php print $PMF_LANG['ad_user_blocked']; ?></option>
                    <option value="protected"><?php print $PMF_LANG['ad_user_protected']; ?></option>
                </select>
            </div>
            <div id="user_data_table"></div><!-- end #user_data_table -->
            <div class="button_row">
                <input class="submit" type="submit" value="<?php print $text['changeUser_submit']; ?>" tabindex="6" />
            </div>
        </form>
    </fieldset>
</div> <!-- end #user_details -->
<div id="user_rights">
    <fieldset>
        <legend id="user_rights_legend"><?php print $text['changeRights']; ?></legend>
        <form id="rightsForm" action="?action=user&amp;user_action=update_rights" method="post">
            <input id="rights_user_id" type="hidden" name="user_id" value="0" />
            <div>
                <span><a href="javascript:form_checkAll('rightsForm')"><?php print $text['changeRights_checkAll']; ?></a></span>
                <span><a href="javascript:form_uncheckAll('rightsForm')"><?php print $text['changeRights_uncheckAll']; ?></a></span>
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
                <input class="submit" type="submit" value="<?php print $text['changeRights_submit']; ?>" />
            </div>
        </form>
    </fieldset>
</div> <!-- end #user_rights -->
<div class="clear"></div>
<?php 
        if (isset($_GET['user_id'])) {
            $userId     = PMF_Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, 0);
            echo '<script type="text/javascript">updateUser('.$userId.');</script>';
        }
    }

    // show list of all users
    if ($userAction == 'listallusers') {
?>

<h2><?php print $text['header']; ?></h2>
<div id="user_message"><?php print $message; ?></div>
    <table class="listrecords" style="width: 700px; float:left;">
    <thead>
        <tr>
            <th class="listhead"><?php print $PMF_LANG['ad_entry_id']?>:</th>
            <th class="listhead"><?php print $PMF_LANG['msgNewContentName']?></th>
            <th class="listhead"><?php print $PMF_LANG['msgNewContentMail']?></th>
            <th class="listhead"><?php print $PMF_LANG['ad_entry_action']?></th>
        </tr>
    </thead>
        <tbody>
        <?php 
            foreach ($user->getAllUsers() as $userId) {
                $user->getUserById($userId);
        ?>
            <tr>
                <td class="list"><?php print $user->getUserData('user_id')?></td>
                <td class="list"><?php print $user->getUserData('display_name')?></td>
                <td class="list"><?php print $user->getUserData('email')?></td>
                <td class="list"><a href="?action=user&amp;user_id=<?php echo $user->getUserData('user_id')?>"><?php echo $PMF_LANG['ad_user_edit']?></a></td>
            </tr>
        <?php
            }
        ?>
        </tbody>
    </table>
<?php 
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}