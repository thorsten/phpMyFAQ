<?php
/**
* $Id: group.php,v 1.1 2006-01-02 16:36:50 b33blebr0x Exp $
*
* Displays the user managment frontend
*
* @author       Lars Tiedemann <php@larstiedemann.de>
* @since        2005-12-15
* @copyright    (c) 2005 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$permission['edituser'] and !$permission['deluser'] and !$permission['adduser']) {
    exit();
}

require_once(PMF_ROOT_DIR.'/inc/PMF_User/User.php');

// set some parameters
$selectSize = 10;
$defaultUserAction = 'list';
$defaultUserStatus = 'active';
$loginMinLength = 4;
$loginInvalidRegExp = '/(^[^a-z]{1}|[\W])/i';
$errorMessages = array(
    //'addUser_password' => $PMF_LANG['ad_user_error_password'], //"Please enter a password. ",
    //'addUser_passwordsDontMatch' => $PMF_LANG['ad_user_error_passwordsDontMatch'], //"Passwords do not match. ",
    //'addUser_loginExists' => $PMF_LANG["ad_adus_exerr"], //"Username <strong>exists</strong> already.",
    //'addUser_loginInvalid' => $PMF_LANG['ad_user_error_loginInvalid'], //"The specified user name is invalid.",
    //'addUser_noEmail' => $PMF_LANG['ad_user_error_noEmail'], //"Please enter a valid mail adress. ",
    //'addUser_noRealName' => $PMF_LANG['ad_user_error_noRealName'], //"Please enter your real name. ",
    //'delUser' => $PMF_LANG['ad_user_error_delete'], //"User account could not be deleted. ",
    //'delUser_noId' => $PMF_LANG['ad_user_error_noId'], //"No User-ID specified. ",
    //'delUser_protectedAccount' => $PMF_LANG['ad_user_error_protectedAccount'], //"User account is protected. ",
    //'updateUser_noId' => $PMF_LANG['ad_user_error_noId'], //"No User-ID specified. ",
    'updateRights_noId' => $PMF_LANG['ad_user_error_noId'], //"No User-ID  specified. ",
);
$successMessages = array(
    //'addUser' => $PMF_LANG["ad_adus_suc"], //"User <strong>successfully</strong> added.",
    //'delUser' => $PMF_LANG["ad_user_deleted"], //"The user was successfully deleted.",
);
$text = array(
    'header' => "Group Administration",
    //'selectUser' => $PMF_LANG["ad_user_username"], // "Registered users"
    'selectGroup' => "Groups",
    //'addUser' => $PMF_LANG["ad_adus_adduser"], // "add User"
    //'addUser_confirm' => $PMF_LANG["ad_gen_save"], //"Save",
    //'addUser_cancel' => $PMF_LANG['ad_gen_cancel'], //"Cancel",
    //'addUser_link' => $PMF_LANG["ad_user_add"], // "Add User"
    //'addUser_name' => $PMF_LANG["ad_adus_name"], // "Name: "
    //'addUser_displayName' => $PMF_LANG["ad_user_realname"], // "real name:"
    //'addUser_email' => $PMF_LANG["ad_entry_email"], // "email adress:"
    //'addUser_password' => $PMF_LANG["ad_adus_password"], // Password:
    //'addUser_password2' => $PMF_LANG["ad_passwd_con"], // Confirm:
    //'delUser' => $PMF_LANG['ad_user_deleteUser'], //"Delete User",
    //'delUser_button' => $PMF_LANG['ad_gen_delete'], //"Delete",
    //'delUser_question' => $PMF_LANG["ad_user_del_3"]." ".$PMF_LANG["ad_user_del_1"]." ".$PMF_LANG["ad_user_del_2"], //"Are you sure?"."The User"."shall be deleted?",
    //'delUser_confirm' => $PMF_LANG["ad_gen_yes"], //"Yes",
    //'delUser_cancel' => $PMF_LANG["ad_gen_no"], //"No",
    //'changeUser' => $PMF_LANG["ad_user_profou"], // "Profile of the User"
    //'changeUser_submit' => $PMF_LANG["ad_gen_save"], //"Save",
    //'changeUser_status' => $PMF_LANG['ad_user_status'], //"Status:",
    //'changeRights' => $PMF_LANG["ad_user_rights"], // "Rights"
    //'changeRights_submit' => $PMF_LANG["ad_gen_save"], //"Save",
);

// what shall we do?
// actions defined by url: group_action=
$userAction = isset($_GET['group_action']) ? $_GET['group_action'] : $defaultUserAction;
// actions defined by submit button
if (isset($_POST['group_action_deleteConfirm']))
    $userAction = 'delete_confirm';
if (isset($_POST['cancel']))
    $userAction = $defaultUserAction;


// update group rights
if ($userAction == 'update_rights') {
    $message = '';
    $userAction = $defaultUserAction;
    $groupId = isset($_POST['group_id']) ? $_POST['group_id'] : 0;
    if ($userId == 0) {
        $message .= '<p class="error">'.$errorMessages['updateRights_noId'].'</p>';
    } else {
        $user = new PMF_User();
        $userRights = isset($_POST['group_rights']) ? $_POST['group_rights'] : array();
        $user->perm->refuseAllUserRights($userId);
        foreach ($userRights as $rightId) {
            $user->perm->grantUserRight($userId, $rightId);
        }
    }
} // end if ($userAction == 'update_rights')
// update user data
if ($userAction == 'update_data') {
    $message = '';
    $userAction = $defaultUserAction;
    $userId = isset($_POST['group_id']) ? $_POST['group_id'] : 0;
    if ($userId == 0) {
        $message .= '<p class="error">'.$errorMessages['updateUser_noId'].'</p>';
    } else {
        $userData = array();
        $dataFields = array('display_name', 'email', 'last_modified');
        foreach ($dataFields as $field) {
            $userData[$field] = isset($_POST[$field]) ? $_POST[$field] : '';
        }
        $userStatus = isset($_POST['group_status']) ? $_POST['group_status'] : $defaultUserStatus;
        $user = new PMF_User();
        $user->getUserById($userId);
        $user->userdata->set(array_keys($userData), array_values($userData));
        $user->setStatus($userStatus);
        $message .= '<p class="success">'.$text['updateUser'].'</p>';
    }
} // end if ($userAction == 'update')
// delete user confirmation
if ($userAction == 'delete_confirm') {
    $message = '';
    $user = new PMF_User();
    $userId = isset($_POST['group_list_select']) ? $_POST['group_list_select'] : 0;
    if ($userId == 0) {
        $message .= '<p class="error">'.$errorMessages['delUser_noId'].'</p>';
        $userAction = $defaultUserAction;
    } else {
        $user->getUserById($userId);
        // account is protected
        if ($user->getStatus() == 'protected') {
            $userAction = $defaultUserAction;
            $message .= '<p class="error">'.$errorMessages['delUser_protectedAccount'].'</p>';
        } else {
?>
<h2><?php print $text['header']; ?></h2>
<div id="group_confirmDelete">
    <fieldset>
        <legend><?php print $text['delUser']; ?></legend>
        <strong><?php print $user->getLogin(); ?></strong>
        <p><?php print $text['delUser_question']; ?></p>
        <form action ="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=delete" method="post">
            <input type="hidden" name="group_id" value="<?php print $userId; ?>" />
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
    $userId = isset($_POST['group_id']) ? $_POST['group_id'] : 0;
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
    $group_name = isset($_POST['group_name']) ? $_POST['group_name'] : '';
    $group_realname = isset($_POST['group_realname']) ? $_POST['group_realname'] : '';
    $group_password = isset($_POST['group_password']) ? $_POST['group_password'] : '';
    $group_email = isset($_POST['group_email']) ? $_POST['group_email'] : '';
    $group_password = isset($_POST['group_password']) ? $_POST['group_password'] : '';
    $group_password_confirm = isset($_POST['group_password_confirm']) ? $_POST['group_password_confirm'] : '';
    // check password
    if ($group_password == "") {
        $group_password = "";
        $group_password_confirm = "";
        $messages[] = $errorMessages['addUser_password'];
    }
    if ($group_password != $group_password_confirm) {
        $group_password = "";
        $group_password_confirm = "";
        $messages[] = $errorMessages['addUser_passwordsDontMatch'];
    }
    // check e-mail. TO DO: MAIL ADRESS VALIDATOR
    if ($group_email == "") {
        $group_email = "";
        $messages[] = $errorMessages['addUser_noEmail'];
    }
    // check login name
    $user->setLoginMinLength($loginMinLength);
    $user->setLoginInvalidRegExp($loginInvalidRegExp);
    if (!$user->isValidLogin($group_name)) {
        $group_name = "";
        $messages[] = $errorMessages['addUser_loginInvalid'];
    }
    if ($user->getUserByLogin($group_name)) {
        $group_name = "";
        $messages[] = $errorMessages['addUser_loginExists'];
    }
    // check realname
    if ($group_realname == "") {
        $group_realname = "";
        $messages[] = $errorMessages['addUser_noRealName'];
    }
    // ok, let's go
    if (count($messages) == 0) {
        // create user account (login and password)
        if (!$user->createUser($group_name, $group_password)) {
            $messages[] = $user->error();
        } else {
            // set user data (realname, email)
            $user->userdata->set(array('display_name', 'email'), array($group_realname, $group_email));
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
<div id="group_message"><?php print $message; ?></div>
<div id="group_create">
    <fieldset>
        <legend><?php print $text['addUser']; ?></legend>
        <form name="group_create" action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=addsave" method="post">
            <div class="input_row">
                <label for="group_name"><?php print $text['addUser_name']; ?></label>
                <input type="text" name="group_name" value="<?php print (isset($group_name) ? $group_name : ''); ?>" tabindex="1" />
            </div>
            <div class="input_row">
                <label for="group_realname"><?php print $text['addUser_displayName']; ?></label>
                <input type="text" name="group_realname" value="<?php print (isset($group_realname) ? $group_realname : ''); ?>" tabindex="2" />
            </div>
            <div class="input_row">
                <label for="group_email"><?php print $text['addUser_email']; ?></label>
                <input type="text" name="group_email" value="<?php print (isset($group_email) ? $group_email : ''); ?>" tabindex="3" />
            </div>
            <div class="input_row">
                <label for="password"><?php print $text['addUser_password']; ?></label>
                <input type="password" name="group_password" value="<?php print (isset($group_password) ? $group_password : ''); ?>" tabindex="4" />
            </div>
            <div class="input_row">
                <label for="password_confirm"><?php print $text['addUser_password2']; ?></label>
                <input type="password" name="group_password_confirm" value="<?php print (isset($group_password_confirm) ? $group_password_confirm : ''); ?>" tabindex="5" />
            </div>
            <div class="button_row">
                <input class="submit" type="submit" value="<?php print $text['addUser_confirm']; ?>" tabindex="6" />
                <input class="reset" name="cancel" type="submit" value="<?php print $text['addUser_cancel']; ?>" tabindex="7" />
            </div>
            <div class="clear"></div>
        </form>
    </fieldset>
</div> <!-- end #group_create -->
<script type="text/javascript">
/* <![CDATA[ */
document.group_create.group_name.focus();
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
var groupList = new getxmlhttp();

function getGroupList() {
    groupList.open('get', '?aktion=ajax&ajax=group_list');
    groupList.onreadystatechange = processGroupList;
    groupList.send(null);
}

function processGroupList() {
    if (groupList.readyState == 4) {
        if (groupList.status == 200) {
            // process response
            clearGroupList();
            buildGroupList();
            //clearGroupData();
            //buildGroupData(0);
            //clearGroupRights();
            //buildGroupRights(0);
        } else {
            alert("There was a problem retrieving the XML data: \n" +groupList.statusText);
        }
    }
}


function clearGroupList()
{
    select_clear(document.getElementById("group_list_select"));
}

function buildGroupList()
{
    var groups = groupList.responseXML.getElementsByTagName("group");
    var id;
    var textNode;
    var classAttrValue = text_getFromParent(groupList.responseXML.getElementsByTagName("grouplist")[0], "select_class");
    for (var i = 0; i < groups.length; i++) {
        textNode = document.createTextNode(text_getFromParent(groups[i], "name"));
        id = groups[i].getAttribute("id");
        select_addOption(document.getElementById("group_list_select"), id, textNode, classAttrValue);
    }
}


function clearGroupData()
{
    //table_clear(document.getElementById("group_data_table"));
    document.getElementById("group_data_table").innerHTML = '';
}

function buildGroupData(id)
{
    var getValues = true;
    var groups = groupList.responseXML.getElementsByTagName("group");
    var group;
    // get user with given id
    if (id == 0) {
        getValues = false;
        group = groups[0];
    } else {
        getValues = true;
        for (var i = 0; i < users.length; i++) {
            if (groups[i].getAttribute("id") == id) {
                group = groups[i];
                break;
            }
        }
    }
    // change user-ID
    document.getElementById("update_group_id").setAttribute("value", id);
    // build new data div rows
    var dataList = user.getElementsByTagName("group_data")[0];
    var items = dataList.getElementsByTagName("item");
    var group_data_table = document.getElementById("group_data_table");
    var name;
    var value;
    var div;
    var input;
    var label;
    for (var i = 0; i < items.length; i++) {
        name = text_getFromParent(items[i], "name");
        if (getValues) {
            value = text_getFromParent(items[i], "value");
        } else {
            value = "";
        }
        input = document.createElement("input");
        input.setAttribute("type", "text");
        input.setAttribute("name", items[i].getAttribute("name"));
        input.setAttribute("value", value);
        input.setAttribute("tabindex", (i + 3));
        label = document.createElement("label");
        label.setAttribute("for", items[i].getAttribute("name"));
        label.appendChild(document.createTextNode(name));
        div = document.createElement("div");
        div.setAttribute("class", "input_row");
        div.appendChild(label);
        div.appendChild(input);
        group_data_table.appendChild(div);
    }
}


function clearGroupRights()
{
    table_clear(document.getElementById("group_rights_table"));
}

function buildGroupRights(id)
{
    var getValues = true;
    var groups = groupList.responseXML.getElementsByTagName("group");
    var group;
    // get user with given id
    if (id == 0) {
        getValues = false;
        group = groups[0];
    } else {
        getValues = true;
        for (var i = 0; i < users.length; i++) {
            if (groups[i].getAttribute("id") == id) {
                group = groups[i];
                break;
            }
        }
    }
    // change user-ID
    document.getElementById("rights_group_id").setAttribute("value", id);
    // build new table rows
    var rightsList = user.getElementsByTagName("group_rights")[0];
    var rights = rightsList.getElementsByTagName("right");
    var group_rights_table = document.getElementById("group_rights_table");
    var name;
    var isGroupRight;
    var checkbox;
    var right_id;
    for (var i = 0; i < rights.length; i++) {
        name = text_getFromParent(rights[i], "name");
        right_id = rights[i].getAttribute("id");
        if (getValues) {
            isGroupRight = text_getFromParent(rights[i], "is_group_right");
        } else {
            isGroupRight = "0";
        }
        checkbox = document.createElement("input");
        checkbox.setAttribute("type", "checkbox");
        checkbox.setAttribute("name", "group_rights[]");
        checkbox.setAttribute("value", right_id);
        if (isUserRight == "1") {
            checkbox.setAttribute("checked", "checked");
        }
        table_addRow(group_rights_table, i, checkbox, document.createTextNode(name));
    }
}



function groupSelect(evt)
{
    evt = (evt) ? evt : ((windows.event) ? windows.event : null);
    if (evt) {
        var select = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
        if (select && select.value > 0) {
            clearGroupData();
            buildGroupData(select.value);
            clearGroupRights();
            buildGroupRights(select.value);
        }
    }
}

getGroupList();

/* ]]> */
</script>

<h2><?php print $text['header']; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="user_accounts">
    <div id="user_list">
        <fieldset>
            <legend><?php print $text['selectGroup']; ?></legend>
            <form name="group_select" id="group_select" action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=delete_confirm" method="post">
                <select name="group_list_select" id="group_list_select" size="<?php print $selectSize; ?>" onchange="userSelect(event)" tabindex="1">
                    <option value="">select...</option>
                </select>
                <input class="admin" type="submit" value="<?php print $text['delUser_button']; ?>" tabindex="2" />
            </form>
        </fieldset>
        <p>[ <a href="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=add"><?php print $text['addUser_link']; ?></a> ]</p>
    </div> <!-- end #user_list -->
</div> <!-- end #user_accounts -->
<div id="user_details">
    <div id="user_data">
        <fieldset>
            <legend id="group_data_legend"><?php print $text['changeUser']; ?></legend>
            <form action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=update_data" method="post">
                <input id="update_group_id" type="hidden" name="group_id" value="0" />
                <div class="input_row">
                    <label for="group_status_select"><?php print $text['changeUser_status']; ?></label>
                    <select id="group_status_select" name="group_status" >
                        <option value="active">active</option>
                        <option value="blocked">blocked</option>
                        <option value="protected">protected</option>
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
            <legend id="group_rights_legend"><?php print $text['changeRights']; ?></legend>
            <form action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=update_rights" method="post">
                <input id="rights_group_id" type="hidden" name="group_id" value="0" />
                <table id="group_rights_table">
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
