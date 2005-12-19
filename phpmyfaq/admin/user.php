<?php

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
);
$successMessages = array(
    'addUser' => "User account successfully created. ",
);

// what shall we do?
$userAction = isset($_GET['user_action']) ? $_GET['user_action'] : $defaultUserAction;

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
<h2><?php print $PMF_LANG["ad_user"]; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="user_create">
    <fieldset>
        <legend><?php print $PMF_LANG["ad_adus_adduser"]; ?></legend>
        <form name="user_create" action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=user&amp;user_action=addsave" method="post">
            <div class="input_row">
                <label for="user_name"><?php print $PMF_LANG["ad_adus_name"]; ?></label>
                <input type="text" name="user_name" value="<?php print (isset($user_name) ? $user_name : ''); ?>" />
            </div>
            <div class="input_row">
                <label for="user_realname"><?php print $PMF_LANG["ad_user_realname"]; ?></label>
                <input type="text" name="user_realname" value="<?php print (isset($user_realname) ? $user_realname : ''); ?>" />
            </div>
            <div class="input_row">
                <label for="user_email"><?php print $PMF_LANG["ad_entry_email"]; ?></label>
                <input type="text" name="user_email" value="<?php print (isset($user_email) ? $user_email : ''); ?>" />
            </div>
            <div class="input_row">
                <label for="password"><?php print $PMF_LANG["ad_adus_password"]; ?></label>
                <input type="password" name="user_password" value="<?php print (isset($user_password) ? $user_password : ''); ?>" />
            </div>
            <div class="input_row">
                <label for="password_confirm"><?php print $PMF_LANG["ad_passwd_con"]; ?></label>
                <input type="password" name="user_password_confirm" value="<?php print (isset($user_password_confirm) ? $user_password_confirm : ''); ?>" />
            </div>
            <div class="button_row">
                <input class="reset" type="button" value="cancel" onclick="location.href='<?php print $_SERVER['PHP_SELF']; ?>?aktion=user'" />
                <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_adus_add"]; ?>" />
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
    // build new table rows
    var dataList = user.getElementsByTagName("user_data")[0];
    var items = dataList.getElementsByTagName("item");
    var user_data_table = document.getElementById("user_data_table");
    var name;
    var value;
    for (var i = 0; i < items.length; i++) {
        name = text_getFromParent(items[i], "name");
        if (getValues) {
            value = text_getFromParent(items[i], "value");
        } else {
            value = "";
        }
        table_addRow(user_data_table, i, document.createTextNode(name), document.createTextNode(value));
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

<h2><?php print $PMF_LANG["ad_user"]; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="user_accounts">
    <div id="user_list">
        <a href="<?php print $_SERVER['PHP_SELF']; ?>?aktion=user&amp;user_action=add">Create User</a>
        <fieldset>
            <legend>User Selection</legend>
            <form>
                <select id="user_list_select" size="<?php print $selectSize; ?>" onchange="userSelect(event)">
                    <option value="">select...</option>
                </select>
            </form>
        </fieldset>
    </div> <!-- end #user_list -->
</div> <!-- end #user_accounts -->
<div id="user_details">
    <div id="user_data">
        <fieldset>
            <legend id="user_data_legend">User Data</legend>
            <table id="user_data_table">
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </fieldset>
    </div> <!-- end #user_details -->
    <div id="user_rights">
        <fieldset>
            <legend id="user_rights_legend">User Rights</legend>
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
