<?php
/**
 * Displays the user managment frontend
 *
 * @package    phpMyFAQ
 * @subpackage Administration 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @author     Uwe Pries <uwe.pries@digartis.de>
 * @author     Sarah Hermann <sayh@gmx.de>
 * @since      2005-12-15
 * @version    SVN: $Id$
 * @copyright  2005-2009 phpMyFAQ Team
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
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$permission['edituser'] and !$permission['deluser'] and !$permission['adduser']) {
    exit();
}

// set some parameters
$selectSize = 10;
$defaultUserAction = 'list';
$defaultUserStatus = 'active';
$loginMinLength = 4;
$loginInvalidRegExp = '/(^[^a-z]{1}|[\W])/i';

$errorMessages = array(
    'addUser_password'              => $PMF_LANG['ad_user_error_password'],
    'addUser_passwordsDontMatch'    => $PMF_LANG['ad_user_error_passwordsDontMatch'],
    'addUser_loginExists'           => $PMF_LANG["ad_adus_exerr"],
    'addUser_loginInvalid'          => $PMF_LANG['ad_user_error_loginInvalid'],
    'addUser_noEmail'               => $PMF_LANG['ad_user_error_noEmail'],
    'addUser_noRealName'            => $PMF_LANG['ad_user_error_noRealName'],
    'delUser'                       => $PMF_LANG['ad_user_error_delete'],
    'delUser_noId'                  => $PMF_LANG['ad_user_error_noId'],
    'delUser_protectedAccount'      => $PMF_LANG['ad_user_error_protectedAccount'],
    'updateUser'                    => $PMF_LANG['ad_msg_mysqlerr'],
    'updateUser_noId'               => $PMF_LANG['ad_user_error_noId'],
    'updateRights'                  => $PMF_LANG['ad_msg_mysqlerr'],
    'updateRights_noId'             => $PMF_LANG['ad_user_error_noId']);

$successMessages = array(
    'addUser'                       => $PMF_LANG["ad_adus_suc"],
    'delUser'                       => $PMF_LANG["ad_user_deleted"],
    'updateUser'                    => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2'],
    'updateRights'                  => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2']);

$text = array(
    'header'                        => $PMF_LANG['ad_user'],
    'selectUser'                    => $PMF_LANG["ad_user_username"],
    'addUser'                       => $PMF_LANG["ad_adus_adduser"],
    'addUser_confirm'               => $PMF_LANG["ad_gen_save"],
    'addUser_cancel'                => $PMF_LANG['ad_gen_cancel'],
    'addUser_link'                  => $PMF_LANG["ad_user_add"],
    'addUser_name'                  => $PMF_LANG["ad_adus_name"],
    'addUser_displayName'           => $PMF_LANG["ad_user_realname"],
    'addUser_email'                 => $PMF_LANG["ad_entry_email"],
    'addUser_password'              => $PMF_LANG["ad_adus_password"],
    'addUser_password2'             => $PMF_LANG["ad_passwd_con"],
    'delUser'                       => $PMF_LANG['ad_user_deleteUser'],
    'delUser_button'                => $PMF_LANG['ad_gen_delete'],
    'delUser_question'              => $PMF_LANG["ad_user_del_3"]." ".$PMF_LANG["ad_user_del_1"]." ".$PMF_LANG["ad_user_del_2"],
    'delUser_confirm'               => $PMF_LANG["ad_gen_yes"],
    'delUser_cancel'                => $PMF_LANG["ad_gen_no"],
    'changeUser'                    => $PMF_LANG["ad_user_profou"],
    'changeUser_submit'             => $PMF_LANG["ad_gen_save"],
    'changeUser_status'             => $PMF_LANG['ad_user_status'],
    'changeRights'                  => $PMF_LANG["ad_user_rights"],
    'changeRights_submit'           => $PMF_LANG["ad_gen_save"],
    'changeRights_checkAll'         => $PMF_LANG['ad_user_checkall'],
    'changeRights_uncheckAll'       => $PMF_LANG['ad_user_uncheckall']);

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
    $message = '';
    $userAction = $defaultUserAction;
    $userId = isset($_POST['user_id']) ? $_POST['user_id'] : 0;
    if ($userId == 0) {
        $message .= '<p class="error">'.$errorMessages['updateRights_noId'].'</p>';
    } else {
        $user = new PMF_User();
        $perm = $user->perm;
        $userRights = isset($_POST['user_rights']) ? $_POST['user_rights'] : array();
        if (!$perm->refuseAllUserRights($userId)) {
            $message .= '<p class="error">'.$errorMessages['updateRights'].'</p>';
        }
        foreach ($userRights as $rightId) {
            $perm->grantUserRight($userId, $rightId);
        }
        $idUser = $user->getUserById($userId);
        $message .= '<p class="success">'.sprintf($successMessages['updateRights'], $user->getLogin()).'</p>';
        $message .= '<script type="text/javascript">updateUser('.$userId.');</script>';
    }
} // end if ($userAction == 'update_rights')
// update user data
if ($userAction == 'update_data') {
    $message = '';
    $userAction = $defaultUserAction;
    $userId = isset($_POST['user_id']) ? $_POST['user_id'] : 0;
    if ($userId == 0) {
        $message .= '<p class="error">'.$errorMessages['updateUser_noId'].'</p>';
    } else {
        $userData = array();
        $dataFields = array('display_name', 'email', 'last_modified');
        foreach ($dataFields as $field) {
            $userData[$field] = isset($_POST[$field]) ? $_POST[$field] : '';
        }
        $userStatus = isset($_POST['user_status']) ? $_POST['user_status'] : $defaultUserStatus;
        $user = new PMF_User();
        $user->getUserById($userId);

        $stats = $user->getStatus();
        // set new password an send email if user is switched to active
        if ($stats == 'blocked' && $userStatus == 'active') {
            $consonants = array("b","c","d","f","g","h","j","k","l","m","n","p","r","s","t","v","w","x","y","z");
            $vowels = array("a","e","i","o","u");
            $newPassword = "";
            srand((double)microtime()*1000000);
            for ($i = 1; $i <= 4; $i++) {
                $newPassword .= $consonants[rand(0,19)];
                $newPassword .= $vowels[rand(0,4)];
            }
            $user->changePassword($newPassword);
            $text = "\nUsername: ".$userData['display_name']."\nLoginname: ".$user->getLogin()."\nNew Password: ".$newPassword."\n\n";

            $mail = new PMF_Mail();
            $mail->addTo($userData['email']);
            $mail->subject = '[%sitename%] Username / activation';
            $mail->message = $text;
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
} // end if ($userAction == 'update') // end if ($userAction == 'update')
// delete user confirmation
if ($userAction == 'delete_confirm') {
    $message = '';
    $user = new PMF_User();
    $userId = isset($_POST['user_list_select']) ? $_POST['user_list_select'] : 0;
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
        <form action ="<?php print $_SERVER['PHP_SELF']; ?>?action=user&amp;user_action=delete" method="post">
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
} // end if ($userAction == 'delete_confirm')
// delete user
if ($userAction == 'delete') {
    $message = '';
    $user = new PMF_User();
    $userId = isset($_POST['user_id']) ? $_POST['user_id'] : 0;
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
            $permLevel = isset($PMF_CONF['main.permLevel']) && ('' != $PMF_CONF['main.permLevel']) ? $PMF_CONF['main.permLevel'] : 'basic';
            if ('medium' == $permLevel) {
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

} // end if ($userAction == 'delete')
// save new user
if ($userAction == 'addsave') {
    $user = new PMF_User();
    $message = '';
    $messages = array();
    // check input data
    $user_name = isset($_POST['user_name']) ? $_POST['user_name'] : '';
    $user_realname = isset($_POST['user_realname']) ? $_POST['user_realname'] : '';
    $user_password = isset($_POST['user_password']) ? $_POST['user_password'] : '';
    $user_email = isset($_POST['user_email']) ? $_POST['user_email'] : '';
    $user_password = isset($_POST['user_password']) ? $_POST['user_password'] : '';
    $user_password_confirm = isset($_POST['user_password_confirm']) ? $_POST['user_password_confirm'] : '';
    // check e-mail. TODO: MAIL ADRESS VALIDATOR
    if ($user_email == "") {
        $user_password = "";
        $user_password_confirm = "";
        $messages[] = $errorMessages['addUser_password'];
    }
    if ($user_password != $user_password_confirm) {
        $user_password = "";
        $user_password_confirm = "";
        $messages[] = $errorMessages['addUser_passwordsDontMatch'];
    }
    // check e-mail.
    if (PMF_Filter::filterVar($user_email, FILTER_VALIDATE_EMAIL) == false) {
        $user_email = "";
        $messages[] = $errorMessages['addUser_noEmail'];
    }
    // check login name
    $user->setLoginMinLength($loginMinLength);
    $user->setLoginInvalidRegExp($loginInvalidRegExp);
    if (!$user->isValidLogin($user_name)) {
        $user_name = "";
        $messages[] = $errorMessages['addUser_loginInvalid'];
    }
    if ($user->getUserByLogin($user_name)) {
        $user_name = "";
        $messages[] = $errorMessages['addUser_loginExists'];
    }
    // check realname
    if ($user_realname == "") {
        $user_realname = "";
        $messages[] = $errorMessages['addUser_noRealName'];
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
} // end if ($userAction == 'addsave')


if (!isset($message))
    $message = '';

// show new user form
if ($userAction == 'add') {
?>
<h2><?php print $text['header']; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="user_create">
    <fieldset>
        <legend><?php print $text['addUser']; ?></legend>
        <form action="<?php print $_SERVER['PHP_SELF']; ?>?action=user&amp;user_action=addsave" method="post">
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
<script type="text/javascript">
/* <![CDATA[ */
document.user_create.user_name.focus();
/* ]]> */
</script>
<?php
} // end if ($userAction == 'add')

// show list of users
if ($userAction == 'list') {
?>
<script type="text/javascript">
/* <![CDATA[ */

/* HTTP Request object */
var userList;

function getUserList(userid) {
    var url = 'index.php';
    var pars = 'action=ajax&ajax=user_list&userid=' + userid;
    var myAjax = new Ajax.Request( url, {method: 'get', parameters: pars, onComplete: processUserList} );
}

function processUserList(XmlRequest) {
    // process response
    userList = XmlRequest;
    clearUserData();
    clearUserRights();
    var users = userList.responseXML.getElementsByTagName('user');
    var userid = users[0].getAttribute('id');
    buildUserData(userid);
    selectUserStatus(userid);
    buildUserRights(userid);
}

function clearUserList()
{
    select_clear($('user_list_select'));
}

function buildUserList()
{
    var users = userList.responseXML.getElementsByTagName('user');
    var id;
    var textNode;
    var classAttrValue = text_getFromParent(userList.responseXML.getElementsByTagName('userlist')[0], "select_class");
    for (var i = 0; i < users.length; i++) {
        textNode = document.createTextNode(text_getFromParent(users[i], "login"));
        id = users[i].getAttribute('id');
        select_addOption($('user_list_select'), id, textNode, classAttrValue);
    }
}

function clearUserData()
{
    //table_clear(document.getElementById("user_data_table"));
    $('user_data_table').innerHTML = "";
}

function buildUserData(id)
{
    var getValues = true;
    var users = userList.responseXML.getElementsByTagName('user');
    var user;
    // get user with given id
    if (id == 0) {
        getValues = false;
        user = users[0];
    } else {
        getValues = true;
        for (var i = 0; i < users.length; i++) {
            if (users[i].getAttribute('id') == id) {
                user = users[i];
                break;
            }
        }
    }
    // change user-ID
    $('update_user_id').setAttribute('value', id);
    // build new data div rows
    var dataList = user.getElementsByTagName('user_data')[0];
    var items = dataList.getElementsByTagName('item');
    var user_data_table = $('user_data_table');
    var name;
    var value;
    var div;
    var input;
    var label;
    for (var i = 0; i < items.length; i++) {
        name = text_getFromParent(items[i], 'name');
        if (getValues) {
            value = text_getFromParent(items[i], 'value');
        } else {
            value = "";
        }
        input = document.createElement('input');
        input.setAttribute('type', "text");
        input.setAttribute('name', items[i].getAttribute('name'));
        input.setAttribute('value', value);
        input.setAttribute('tabindex', (i + 3));
        label = document.createElement('label');
        label.setAttribute('for', items[i].getAttribute('name'));
        label.appendChild(document.createTextNode(name));
        div = document.createElement('div');
        div.setAttribute('class', "input_row");
        div.appendChild(label);
        div.appendChild(input);
        user_data_table.appendChild(div);
    }
}

function selectUserStatus(id)
{
    var getValues = true;
    var users = userList.responseXML.getElementsByTagName('user');
    var user;
    // get user with given id
    if (id == 0) {
        getValues = false;
        user = users[0];
    } else {
        getValues = true;
        for (var i = 0; i < users.length; i++) {
            if (users[i].getAttribute('id') == id) {
                user = users[i];
                break;
            }
        }
    }
    var status = text_getFromParent(user, 'status');
    $('user_status_select').value = status;
}

function clearUserRights()
{
    table_clear($('user_rights_table'));
}

function buildUserRights(id)
{
    var user_rights_table = $('user_rights_table');
    var getValues = true;
    // get user with given id
    if (id == 0) {
        getValues = false;
    } else {
        getValues = true;
        // loop through user-elements
        var users = userList.responseXML.getElementsByTagName('user');
        var user;
        for (var i = 0; i < users.length; i++) {
            if (users[i].getAttribute('id') == id) {
                user = users[i];
                break;
            }
        }
    }
    // change user-ID
    $('rights_user_id').setAttribute('value', id);
    var right_id;
    var right_name;
    var right_description;
    var checkbox;
    var isUserRight = 0;
    // loop through rightlist at beginning (all user rights)
    var rightList = userList.responseXML.getElementsByTagName('rightlist')[0].getElementsByTagName('right');
    for (var i = 0; i < rightList.length; i++) {
        right_name = text_getFromParent(rightList[i], 'name');
        right_description = text_getFromParent(rightList[i], 'description');
        right_id = rightList[i].getAttribute('id');
        // search for that right in user right list
        isUserRight = 0;
        if (getValues) {
            var userRights = user.getElementsByTagName('right');
            var j = 0;
            while (isUserRight == 0 && j < userRights.length) {
                if (userRights[j].getAttribute('id') == right_id) {
                    isUserRight = 1;
                    break;
                } else {
                    isUserRight = 0;
                    j++;
                }
            }
        } else {
            isUserRight = 0;
        }
        // build new table row
        checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'user_rights[]';
        checkbox.value = right_id;
        setTimeout((function(checkbox, isUserRight) {
            return function() {
                checkbox.checked = isUserRight == 1;
            }
        })(checkbox, isUserRight), 10);

        table_addRow(user_rights_table, i, checkbox, document.createTextNode(right_name));
    }
}

function userSelect(text, li)
{
    var userid = li.id;
    getUserList(userid);
    $('user_list_select').value = userid;
}

function updateUser(id)
{
    var userid = id;
    getUserList(userid);
}

/* ]]> */
</script>
<h2><?php print $text['header']; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="user_accounts">
    <div id="user_list">
        <fieldset>
            <legend><?php print $text['selectUser']; ?></legend>
            <form name="user_select" id="user_select" action="<?php print $_SERVER['PHP_SELF']; ?>?action=user&amp;user_action=delete_confirm" method="post">
                <input type="text" id="user_list_autocomplete" name="user_list_search" />
                <div id="user_list_autocomplete_choices" class="user_list_autocomplete" style="display: none;"></div>
                <script type="text/javascript">
                <!--
                    //$('#user_list_autocomplete').autocomplete("index.php?action=ajax&ajax=user_list_autocomplete", { width: 260, selectFirst: true } );
                    var url = 'index.php';
                    var pars = 'action=ajax&ajax=user_list_autocomplete';
                    new Ajax.Autocompleter(
                        "user_list_autocomplete",
                        "user_list_autocomplete_choices",
                        url,
                        {
                            method: 'get',
                            parameters: pars,
                            minChars: 1,
                            afterUpdateElement: userSelect
                        }
                    );
                //-->
                </script>
                <div class="button_row">
                    <input type="hidden" name="user_list_select" id="user_list_select">
                    <input class="submit" type="submit" value="<?php print $text['delUser_button']; ?>" tabindex="2" />
                </div>
            </form>
        </fieldset>
        <p>[ <a href="<?php print $_SERVER['PHP_SELF']; ?>?action=user&amp;user_action=add"><?php print $text['addUser_link']; ?></a> ]</p>
    </div> <!-- end #user_list -->
</div> <!-- end #user_accounts -->
<div id="user_details">
    <div id="user_data">
        <fieldset>
            <legend id="user_data_legend"><?php print $text['changeUser']; ?></legend>
            <form action="<?php print $_SERVER['PHP_SELF']; ?>?action=user&amp;user_action=update_data" method="post">
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
            <form id="rightsForm" action="<?php print $_SERVER['PHP_SELF']; ?>?action=user&amp;user_action=update_rights" method="post">
                <input id="rights_user_id" type="hidden" name="user_id" value="0" />
                <div>
                    <span><a href="javascript:form_checkAll('rightsForm')"><?php print $text['changeRights_checkAll']; ?></a></span>
                    <span><a href="javascript:form_uncheckAll('rightsForm')"><?php print $text['changeRights_uncheckAll']; ?></a></span>
                </div>
                <table id="user_rights_table">
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
                <div class="button_row">
                    <input class="submit" type="submit" value="<?php print $text['changeRights_submit']; ?>" />
                </div>
            </form>
        </fieldset>
    </div> <!-- end #user_rights -->
</div> <!-- end #user_details -->
<div class="clear"></div>
<?php
} // end if ($userAction == 'list')
?>
