<?php
/**
* $Id: user.php,v 1.9 2005-12-22 21:03:40 b33blebr0x Exp $
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
$loginMinLength = 4;
$loginInvalidRegExp = '/(^[^a-z]{1}|[\W])/i';
$errorMessages = array(
    'addUser_password' => "Please enter a password. ",
    'addUser_passwordsDontMatch' => "Passwords do not match. ",
    'addUser_loginExists' => "Specified user name already exists. ",
    'addUser_loginInvalid' => "The specified user name is invalid.",
    'addUser_noEmail' => "Please enter a valid mail adress. ",
    'addUser_noRealName' => "Please enter your real name. ",
    'delUser' => "User account could not be deleted. ",
    'delUser_noId' => "No User-ID specified. ",
    'delUser_protectedAccount' => "User account is protected. ",
    'updateUser_noId' => "No User-ID specified. ",
);
$successMessages = array(
    'addUser' => "User account successfully created. ",
    'delUser' => "User account successfully deleted. ",
);
$text = array(
    'button_cancel' => "cancel",
    'header' => $PMF_LANG['ad_user'], // "User Administration"
    'addUser' => $PMF_LANG["ad_adus_adduser"], // "add User"
    'addUser_confirm' => "add",
    'delUser' => "delete user",
    'delUser_question' => "Really delete this account? ",
    'delUser_confirm' => "delete",
    'changeUser' => $PMF_LANG["ad_user_profou"], // "Profile of the User
    'changeUser_submit' => "change",
);

// what shall we do?
// actions defined by url: user_action=
$userAction = isset($_GET['user_action']) ? $_GET['user_action'] : $defaultUserAction;
// actions defined by submit button
if (isset($_POST['user_action_deleteConfirm']))
    $userAction = 'delete_confirm';
if (isset($_POST['cancel']))
    $userAction = $defaultUserAction;


// update user data
if ($userAction == 'update') {
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
        $user = new PMF_User();
        $user->getUserById($userId);
        $user->userdata->set(array_keys($userData), array_values($userData));
        $message .= '<p class="success">'.$text['updateUser'].'</p>';
    }
} // end if ($userAction == 'update')
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
        if ($user->getStatus() == 'protected') {
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
        <form action ="<?php print $_SERVER['PHP_SELF']; ?>?aktion=user&amp;user_action=delete" method="post">
            <input type="hidden" name="user_id" value="<?php print $userId; ?>" />
            <div class="button_row">
                <input class="reset" type="submit" name="cancel" value="<?php print $text['button_cancel']; ?>" />
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
    // check password
    if ($user_password == "") {
        $user_password = "";
        $user_password_confirm = "";
        $messages[] = $errorMessages['addUser_password'];
    }
    if ($user_password != $user_password_confirm) {
        $user_password = "";
        $user_password_confirm = "";
        $messages[] = $errorMessages['addUser_passwordsDontMatch'];
    }
    // check e-mail. TO DO: MAIL ADRESS VALIDATOR
    if ($user_email == "") {
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
        <form name="user_create" action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=user&amp;user_action=addsave" method="post">
            <div class="input_row">
                <label for="user_name"><?php print $PMF_LANG["ad_adus_name"]; ?></label>
                <input type="text" name="user_name" value="<?php print (isset($user_name) ? $user_name : ''); ?>" tabindex="1" />
            </div>
            <div class="input_row">
                <label for="user_realname"><?php print $PMF_LANG["ad_user_realname"]; ?></label>
                <input type="text" name="user_realname" value="<?php print (isset($user_realname) ? $user_realname : ''); ?>" tabindex="2" />
            </div>
            <div class="input_row">
                <label for="user_email"><?php print $PMF_LANG["ad_entry_email"]; ?></label>
                <input type="text" name="user_email" value="<?php print (isset($user_email) ? $user_email : ''); ?>" tabindex="3" />
            </div>
            <div class="input_row">
                <label for="password"><?php print $PMF_LANG["ad_adus_password"]; ?></label>
                <input type="password" name="user_password" value="<?php print (isset($user_password) ? $user_password : ''); ?>" tabindex="4" />
            </div>
            <div class="input_row">
                <label for="password_confirm"><?php print $PMF_LANG["ad_passwd_con"]; ?></label>
                <input type="password" name="user_password_confirm" value="<?php print (isset($user_password_confirm) ? $user_password_confirm : ''); ?>" tabindex="5" />
            </div>
            <div class="button_row">
                <input class="submit" type="submit" value="<?php print $text['addUser_confirm']; ?>" tabindex="6" />
                <input class="reset" name="cancel" type="submit" value="<?php print $text['button_cancel']; ?>" tabindex="7" />
            </div>
            <div class="clear"></div>
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
var userList = new getxmlhttp();

function getUserList() {
    userList.open('get', '?aktion=ajax&ajax=user_list');
    userList.onreadystatechange = processUserList;
    userList.send(null);
}

function processUserList() {
    if (userList.readyState == 4) {
        if (userList.status == 200) {
            // process response
            clearUserList();
            buildUserList();
            clearUserData();
            buildUserData(0);
            clearUserRights();
            buildUserRights(0);
        } else {
            alert("There was a problem retrieving the XML data: \n" +userList.statusText);
        }
    }
}


function clearUserList()
{
    select_clear(document.getElementById("user_list_select"));
}

function buildUserList()
{
    var users = userList.responseXML.getElementsByTagName("user");
    var id;
    var textNode;
    var classAttrValue = text_getFromParent(userList.responseXML.getElementsByTagName("userlist")[0], "select_class");
    for (var i = 0; i < users.length; i++) {
        textNode = document.createTextNode(text_getFromParent(users[i], "login"));
        id = users[i].getAttribute("id");
        select_addOption(document.getElementById("user_list_select"), id, textNode, classAttrValue);
    }
}


function clearUserData()
{
    table_clear(document.getElementById("user_data_table"));
}

function buildUserData(id)
{
    var getValues = true;
    var users = userList.responseXML.getElementsByTagName("user");
    var user;
    // get user with given id
    if (id == 0) {
        getValues = false;
        user = users[0];
    } else {
        getValues = true;
        for (var i = 0; i < users.length; i++) {
            if (users[i].getAttribute("id") == id) {
                user = users[i];
                break;
            }
        }
    }
    // change user-ID
    document.getElementById("update_user_id").setAttribute("value", id);
    // build new data rows
    var dataList = user.getElementsByTagName("user_data")[0];
    var items = dataList.getElementsByTagName("item");
    var user_data_table = document.getElementById("user_data_table");
    var name;
    var value;
    var input;
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
        table_addRow(user_data_table, i, document.createTextNode(name), input);
    }
}


function clearUserRights()
{
    table_clear(document.getElementById("user_rights_table"));
}

function buildUserRights(id)
{
    var getValues = true;
    var users = userList.responseXML.getElementsByTagName("user");
    var user;
    // get user with given id
    if (id == 0) {
        getValues = false;
        user = users[0];
    } else {
        getValues = true;
        for (var i = 0; i < users.length; i++) {
            if (users[i].getAttribute("id") == id) {
                user = users[i];
                break;
            }
        }
    }
    // build new table rows
    var rightsList = user.getElementsByTagName("user_rights")[0];
    var rights = rightsList.getElementsByTagName("right");
    var user_rights_table = document.getElementById("user_rights_table");
    var name;
    var isUserRight;
    var checkbox;
    var right_id;
    for (var i = 0; i < rights.length; i++) {
        name = text_getFromParent(rights[i], "name");
        right_id = rights[i].getAttribute("id");
        if (getValues) {
            isUserRight = text_getFromParent(rights[i], "is_user_right");
        } else {
            isUserRight = "0";
        }
        checkbox = document.createElement("input");
        checkbox.setAttribute("type", "checkbox");
        if (isUserRight == "1") {
            checkbox.setAttribute("checked", "checked");
        }
        table_addRow(user_rights_table, i, checkbox, document.createTextNode(name));
    }
}



function userSelect(evt)
{
    evt = (evt) ? evt : ((windows.event) ? windows.event : null);
    if (evt) {
        var select = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
        if (select && select.value > 0) {
            clearUserData();
            buildUserData(select.value);
            clearUserRights();
            buildUserRights(select.value);
        }
    }
}



var addUserHTTP = new getxmlhttp();

function addUser() {
    addUserHTTP.open('get', '?aktion=ajax&ajax=user_add');
    addUserHTTP.onreadystatechange = processAddUserHTTP;
    addUserHTTP.send(null);
}

function processAddUserHTTP() {
    if (addUserHTTP.readyState == 4) {
        if (addUserHTTP.status == 200) {
            // process response
            
        } else {
            alert("There was a problem retrieving the XML data: \n" +addUserHTTP.statusText);
        }
    }
}

getUserList();

/* ]]> */
</script>

<h2><?php print $text['header']; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="user_accounts">
    <div id="user_list">
        <fieldset>
            <legend><?php print $PMF_LANG["ad_user_username"]; ?></legend>
            <form name="user_select" id="user_select" action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=user" method="post">
                <select name="user_list_select" id="user_list_select" size="<?php print $selectSize; ?>" onchange="userSelect(event)" tabindex="1">
                    <option value="">select...</option>
                </select>
                <input class="admin" type="submit" name="user_action_deleteConfirm" value="<?php print $PMF_LANG['ad_user_delete']; ?>" tabindex="2" />
            </form>
        </fieldset>
        <p>[ <a href="<?php print $_SERVER['PHP_SELF']; ?>?aktion=user&amp;user_action=add"><?php print $PMF_LANG["ad_user_add"]; ?></a> ]</p>
    </div> <!-- end #user_list -->
</div> <!-- end #user_accounts -->
<div id="user_details">
    <div id="user_data">
        <fieldset>
            <legend id="user_data_legend"><?php print $text['changeUser']; ?></legend>
            <form action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=user&amp;user_action=update" method="post">
                <input id="update_user_id" type="hidden" name="user_id" value="0" />
                <table id="user_data_table">
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
                <div class="button_row">
                    <input class="submit" type="submit" value="<?php print $text['changeUser_submit']; ?>" tabindex="6" />
                </div>
            </form>
        </fieldset>
    </div> <!-- end #user_details -->
    <div id="user_rights">
        <fieldset>
            <legend id="user_rights_legend"><?php print $PMF_LANG["ad_user_rights"]; ?></legend>
            <table id="user_rights_table">
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </fieldset>
    </div> <!-- end #user_rights -->
</div> <!-- end #user_details -->
<div class="clear"></div>
<?php
} // end if ($userAction == 'list')
?>
