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
        table_addRow(user_data_table, i, name, value);
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
    <div id="user_rights">&nbsp;</div>
