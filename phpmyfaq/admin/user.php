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
            // retrieve response
            //var responseText = userList.responseText;
            var responseXML  = userList.responseXML;
            // process response
            var user_select = document.getElementById("user_list_select");
            clearSelect(user_select);
            var optionList = responseXML.getElementsByTagName("item");
            var id;
            var textNode;
            var classAttrValue;
            for (var i = 0; i < optionList.length; i++) {
                textNode = document.createTextNode(getChildText("name", optionList[i]));
                id = getChildText("id", optionList[i]);
                classAttrValue = getChildText("type", optionList[i]);
                addToSelect(user_select, id, textNode, classAttrValue);
            }
        } else {
            alert("There was a problem retrieving the XML data: \n" +userList.statusText);
        }
    }
}

function clearSelect(select)
{
    while (select.length > 0) {
        select.remove(0);
    }
}

function addToSelect(select, value, content, classValue)
{
    var opt;
    opt = document.createElement("option");
    opt.value = value;
    if (classValue) {
        opt.className = classValue;
    }
    opt.appendChild(content);
    select.appendChild(opt);
}

function getChildText(childElement, parentObject)
{
    var result = "";
    result = parentObject.getElementsByTagName(childElement)[0];
    if (result) {
        if (result.childNodes.length > 1) {
            return result.childNodes[1].nodeValue;
        } else {
            if (result.firstChild) {
                return result.firstChild.nodeValue;
            } else {
                return "";
            }
        }
    } else {
        return "n/a";
    }
}

var userData = new getxmlhttp();

function getUserData(user_id) {
    userData.open('get', '?aktion=ajax&ajax=user_data&user_id=' + user_id);
    userData.onreadystatechange = processUserData;
    userData.send(null);
}

function processUserData() {
    if (userData.readyState == 4) {
        if (userData.status == 200) {
            // retrieve response
            //var responseText = userData.responseText;
            var responseXML  = userData.responseXML;
            // process response
            var user_data_legend = document.getElementById("user_data_legend");
            var user_data_table = document.getElementById("user_data_table");
            var dataList = responseXML.getElementsByTagName("item");
            clearTable(user_data_table);
            var name;
            var value;
            for (var i = 0; i < dataList.length; i++) {
                name = getChildText("name", dataList[i]);
                value = getChildText("value", dataList[i]);
                addRowToTable(user_data_table, i, name, value);
            }
        } else {
            alert("There was a problem retrieving the XML data: \n" +userData.statusText);
        }
    }
}

function clearTable(table)
{
    while (table.rows.length > 0) {
        table.deleteRow(0);
    }
}

function addRowToTable(table, rowNumber, col1, col2)
{
    var td1, td1text;
    var td2, td2text;
    var tr;
    td1 = document.createElement("td");
    td1text = document.createTextNode(col1);
    td1.appendChild(td1text);
    td2 = document.createElement("td");
    td2text = document.createTextNode(col2);
    td2.appendChild(td2text);
    tr = table.insertRow(rowNumber);
    tr.appendChild(td1);
    tr.appendChild(td2);
}

getUserList();
getUserData(0);

function updateUserData()
{
    getUserData(document.getElementById("user_list_select").value);
}

/* ]]> */
</script>

    <h2><?php print $PMF_LANG["ad_user"]; ?></h2>
    <div id="user_list">
        <fieldset>
            <legend>User Selection</legend>
            <form>
                <select id="user_list_select" size="<?php print $selectSize; ?>" onchange="updateUserData">
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
