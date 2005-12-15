<?php

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
if (!$permission['edituser'] and !$permission['deluser'] and !$permission['adduser']) {
    exit();
}

$selectSize = 10;

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

getUserList();

/* ]]> */
</script>

    <h2><?php print $PMF_LANG["ad_user"]; ?></h2>
    <div id="user_list">
        <fieldset>
            <legend>User Selection</legend>
            <form>
                <select id="user_list_select" size="<?php print $selectSize; ?>" onchange="userSelect(event)">
                    <option value="">select...</option>
                </select>
            </form>
        </fieldset>
    </div>
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
    </div>
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
    </div>
