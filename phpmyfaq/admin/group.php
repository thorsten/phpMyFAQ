<?php
/**
* $Id: group.php,v 1.5 2006-01-05 19:43:37 b33blebr0x Exp $
*
* Displays the user managment frontend
*
* @author       Lars Tiedemann <php@larstiedemann.de>
* @since        2005-12-15
* @copyright    (c) 2006 phpMyFAQ Team
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
$descriptionRows = 3;
$descriptionCols = 15;
$defaultGroupAction = 'list';
$errorMessages = array(
    //'addUser_password' => $PMF_LANG['ad_user_error_password'], //"Please enter a password. ",
    //'addUser_passwordsDontMatch' => $PMF_LANG['ad_user_error_passwordsDontMatch'], //"Passwords do not match. ",
    //'addUser_loginExists' => $PMF_LANG["ad_adus_exerr"], //"Username <strong>exists</strong> already.",
    //'addUser_loginInvalid' => $PMF_LANG['ad_user_error_loginInvalid'], //"The specified user name is invalid.",
    //'addUser_noEmail' => $PMF_LANG['ad_user_error_noEmail'], //"Please enter a valid mail adress. ",
    //'addUser_noRealName' => $PMF_LANG['ad_user_error_noRealName'], //"Please enter your real name. ",
    'addGroup_noName' => $PMF_LANG['ad_group_error_noName'], //"Please enter a group name. ",
    'addGroup_db' => $PMF_LANG['ad_adus_dberr'], // "<strong>database error!</strong>"
    //'delUser' => $PMF_LANG['ad_user_error_delete'], //"User account could not be deleted. ",
    'delGroup' => $PMF_LANG['ad_group_error_delete'], //"Group could not be deleted. ",
    //'delUser_noId' => $PMF_LANG['ad_user_error_noId'], //"No User-ID specified. ",
    'delGroup_noId' => $PMF_LANG['ad_group_error_noId'], //"No Group-ID specified. ",
    //'delUser_protectedAccount' => $PMF_LANG['ad_user_error_protectedAccount'], //"User account is protected. ",
    //'updateUser_noId' => $PMF_LANG['ad_user_error_noId'], //"No ID specified. ",
    //'updateUser' => $PMF_LANG['ad_msg_mysqlerr'], //"Due to a <strong>database error</strong>, the profile could not be saved."
    'updateGroup' => $PMF_LANG['ad_msg_mysqlerr'], //"Due to a <strong>database error</strong>, the profile could not be saved."
    'updateGroup_noId' => $PMF_LANG['ad_user_error_noId'], //"No ID specified. ",
    'updateRights_noId' => $PMF_LANG['ad_user_error_noId'], //"No ID  specified. ",
);
$successMessages = array(
    //'addUser' => $PMF_LANG["ad_adus_suc"], //"User <strong>successfully</strong> added.",
    'addGroup' => $PMF_LANG['ad_group_suc'], //"Group <strong>successfully</strong> added.",
    //'delUser' => $PMF_LANG["ad_user_deleted"], //"The user was successfully deleted.",
    'delGroup' => $PMF_LANG['ad_group_deleted'], //"The group was successfully deleted.",
    //'updateUser' => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2'],
    'updateGroup' => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2'],
);
$text = array(
    'header' => "Group Administration",
    //'selectUser' => $PMF_LANG['ad_user_username'], // "Registered users"
    'selectGroup' => "Groups",
    //'addUser' => $PMF_LANG['ad_adus_adduser'], // "add User"
    'addGroup' => $PMF_LANG['ad_group_add'], // "Add Group"
    //'addUser_confirm' => $PMF_LANG['ad_gen_save'], //"Save",
    'addGroup_confirm' => $PMF_LANG['ad_gen_save'], //"Save",
    //'addUser_cancel' => $PMF_LANG['ad_gen_cancel'], //"Cancel",
    'addGroup_cancel' => $PMF_LANG['ad_gen_cancel'], //"Cancel",
    //'addUser_link' => $PMF_LANG['ad_user_add'], // "Add User"
    'addGroup_link' => $PMF_LANG['ad_group_add_link'], // "Add Group"
    //'addUser_name' => $PMF_LANG['ad_adus_name'], // "Name: "
    'addGroup_name' => $PMF_LANG['ad_group_name'], // "Name: "
    'addGroup_description' => $PMF_LANG['ad_group_description'], // "Description: "
    'addGroup_autoJoin' => $PMF_LANG['ad_group_autoJoin'], // "Auto-join:"
    //'delUser' => $PMF_LANG['ad_user_deleteUser'], //"Delete User",
    'delGroup' => $PMF_LANG['ad_group_deleteGroup'], //"Delete Group",
    //'delUser_button' => $PMF_LANG['ad_gen_delete'], //"Delete",
    'delGroup_button' => $PMF_LANG['ad_gen_delete'], //"Delete",
    //'delUser_question' => $PMF_LANG['ad_user_del_3']." ".$PMF_LANG['ad_user_del_1']." ".$PMF_LANG['ad_user_del_2'], //"Are you sure?"."The User"."shall be deleted?",
    'delGroup_question' => $PMF_LANG['ad_group_deleteQuestion'], //"Are you sure that this group shall be deleted?",
    //'delUser_confirm' => $PMF_LANG['ad_gen_yes'], //"Yes",
    'delGroup_confirm' => $PMF_LANG['ad_gen_yes'], //"Yes",
    //'delUser_cancel' => $PMF_LANG['ad_gen_no'], //"No",
    'delGroup_cancel' => $PMF_LANG['ad_gen_no'], //"No",
    //'changeUser' => $PMF_LANG['ad_user_profou'], // "Profile of the User"
    'changeGroup' => $PMF_LANG['ad_group_details'], // "Group Details"
    //'changeUser_submit' => $PMF_LANG['ad_gen_save'], //"Save",
    'changeGroup_submit' => $PMF_LANG['ad_gen_save'], //"Save",
    //'changeUser_status' => $PMF_LANG['ad_user_status'], //"Status:",
    'changeRights' => $PMF_LANG['ad_user_rights'], // "Rights"
    'changeRights_submit' => $PMF_LANG['ad_gen_save'], //"Save",
);

// what shall we do?
// actions defined by url: group_action=
$groupAction = isset($_GET['group_action']) ? $_GET['group_action'] : $defaultGroupAction;
// actions defined by submit button
if (isset($_POST['group_action_deleteConfirm']))
    $groupAction = 'delete_confirm';
if (isset($_POST['cancel']))
    $groupAction = $defaultGroupAction;


// update group rights
if ($groupAction == 'update_rights') {
    $message = '';
    $groupAction = $defaultGroupAction;
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
} // end if ($groupAction == 'update_rights')
// update group data
if ($groupAction == 'update_data') {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = (isset($_POST['group_id']) && $_POST['group_id'] > 0) ? $_POST['group_id'] : 0;
    if ($groupId == 0) {
        $message .= '<p class="error">'.$errorMessages['updateGroup_noId'].'</p>';
    } else {
        $groupData = array();
        $dataFields = array('name', 'description', 'auto_join');
        foreach ($dataFields as $field) {
            $groupData[$field] = isset($_POST[$field]) ? $_POST[$field] : '';
        }
        $user = new PMF_User();
        $perm = $user->perm;
        if (!$perm->changeGroup($groupId, $groupData)) {
            $message .= '<p class="error">'.$errorMessages['updateGroup'].'</p>';
            $message .= '<p class="error">'.$perm->_db->error().'</p>';
        } else {
            $message .= '<p class="success">'.sprintf($successMessages['updateGroup'], $groupData['name']).'</p>';
        }
    }
} // end if ($groupAction == 'update')
// delete group confirmation
if ($groupAction == 'delete_confirm') {
    $message = '';
    $user = new PMF_User();
    $perm = $user->perm;
    $groupId = (isset($_POST['group_list_select']) && $_POST['group_list_select'] > 0) ? $_POST['group_list_select'] : 0;
    if ($groupId <= 0) {
        $message .= '<p class="error">'.$errorMessages['delGroup_noId'].'</p>';
        $groupAction = $defaultGroupAction;
    } else {
        $group_data = $perm->getGroupData($groupId);
?>
<h2><?php print $text['header']; ?></h2>
<div id="group_confirmDelete">
    <fieldset>
        <legend><?php print $text['delGroup']; ?></legend>
        <strong><?php print $group_data['name']; ?></strong>
        <p><?php print $text['delGroup_question']; ?></p>
        <form action ="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=delete" method="post">
            <input type="hidden" name="group_id" value="<?php print $groupId; ?>" />
            <div class="button_row">
                <input class="reset" type="submit" name="cancel" value="<?php print $text['delGroup_cancel']; ?>" />
                <input class="submit" type="submit" value="<?php print $text['delGroup_confirm']; ?>" />
            </div>
        </form>
    </fieldset>
</div>
<?php
    }
} // end if ($groupAction == 'delete_confirm')
// delete group
if ($groupAction == 'delete') {
    $message = '';
    $user = new PMF_User();
    $perm = $user->perm;
    $groupId = (isset($_POST['group_id']) && $_POST['group_id'] > 0) ? (int) $_POST['group_id'] : 0;
    $groupAction = $defaultGroupAction;
    if ($groupId <= 0) {
        $message .= '<p class="error">'.$errorMessages['delGroup_noId'].'</p>';
    } else {
        if (!$perm->deleteGroup($groupId)) {
            $message .= '<p class="error">'.$errorMessages['delGroup'].'</p>';
        } else {
            $message .= '<p class="success">'.$successMessages['delGroup'].'</p>';
        }
        $userError = $user->error();
        if ($userError != "") {
            $message .= '<p>ERROR: '.$userError.'</p>';
        }
    }

} // end if ($groupAction == 'delete')
// save new group
if ($groupAction == 'addsave') {
    $user = new PMF_User();
    $message = '';
    $messages = array();
    // check input data
    $group_name = isset($_POST['group_name']) ? $_POST['group_name'] : '';
    $group_description = isset($_POST['group_description']) ? $_POST['group_description'] : '';
    $group_auto_join = isset($_POST['group_auto_join']) ? $_POST['group_auto_join'] : '';
    // check group name
    if ($group_name == "") {
        $messages[] = $errorMessages['addGroup_noName'];
    }
    // ok, let's go
    if (count($messages) == 0) {
        // create group
        $group_data = array(
            'name' => $group_name,
            'description' => $group_description,
            'auto_join' => $group_auto_join
        );
        if ($user->perm->addGroup($group_data) <= 0)
            $messages[] = $errorMessages['addGroup_db'];
    }
    // no errors, show list
    if (count($messages) == 0) {
        $groupAction = $defaultGroupAction;
        $message = '<p class="success">'.$successMessages['addGroup'].'</p>';
    // display error messages and show form again
    } else {
        $groupAction = 'add';
        foreach ($messages as $err) {
            $message .= '<p class="error">'.$err.'</p>';
        }
    }
} // end if ($groupAction == 'addsave')


if (!isset($message))
    $message = '';

// show new group form
if ($groupAction == 'add') {
?>
<h2><?php print $text['header']; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="group_create">
    <fieldset>
        <legend><?php print $text['addGroup']; ?></legend>
        <form name="group_create" action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=addsave" method="post">
            <div class="input_row">
                <label for="group_name"><?php print $text['addGroup_name']; ?></label>
                <input class="admin" type="text" name="group_name" value="<?php print (isset($group_name) ? $group_name : ''); ?>" tabindex="1" />
            </div>
            <div class="input_row">
                <label for="group_description"><?php print $text['addGroup_description']; ?></label>
                <textarea class="admin" name="group_description" cols="<?php print $descriptionCols; ?>" rows="<?php print $descriptionRows; ?>" tabindex="2"><?php print (isset($group_description) ? $group_description : ''); ?></textarea>
            </div>
            <div class="input_row">
                <label for="group_auto_join"><?php print $text['addGroup_autoJoin']; ?></label>
                <input class="admin" type="checkbox" name="group_auto_join" value="1" tabindex="3"<?php print ((isset($group_auto_join) && $group_auto_join) ? ' checked="checked"' : ''); ?> />
            </div>
            <div class="button_row">
                <input class="submit" type="submit" value="<?php print $text['addGroup_confirm']; ?>" tabindex="6" />
                <input class="reset" name="cancel" type="submit" value="<?php print $text['addGroup_cancel']; ?>" tabindex="7" />
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
} // end if ($groupAction == 'add')

// show list of users
if ($groupAction == 'list') {
?>
<script type="text/javascript">
/* <![CDATA[ */

/* HTTP Request object */

/* HTTP Request object */
var groupList;

function getGroupList() {
    var url = 'index.php';
    var pars = 'aktion=ajax&ajax=group_list';
    var myAjax = new Ajax.Request( url, {method: 'get', parameters: pars, onComplete: processGroupList} );
}

function processGroupList(XmlRequest) {
    // process response
    groupList = XmlRequest;
    clearGroupList();
    buildGroupList();
    clearGroupData();
    buildGroupData(0);
    clearGroupRights();
    buildGroupRights(0);
}

function clearGroupList()
{
    select_clear($('group_list_select'));
}

function buildGroupList()
{
    var groups = groupList.responseXML.getElementsByTagName('group');
    var id;
    var textNode;
    var classAttrValue = text_getFromParent(groupList.responseXML.getElementsByTagName('grouplist')[0], "select_class");
    for (var i = 0; i < groups.length; i++) {
        textNode = document.createTextNode(text_getFromParent(groups[i], 'name'));
        id = groups[i].getAttribute('id');
        select_addOption($('group_list_select'), id, textNode, classAttrValue);
    }
}


function clearGroupData()
{
    $('update_group_id').removeAttribute('value');
    $('update_group_name').removeAttribute('value');
    $('update_group_description').value = '';
    if ($('update_group_auto_join').getAttribute('checked')) {
        $('update_group_auto_join').removeAttributeNode($('update_group_auto_join').getAttributeNode('checked'));
    }
}

function buildGroupData(id)
{
    var getValues = true;
    var groups = groupList.responseXML.getElementsByTagName('group');
    var group;
    // get group with given id
    if (id == 0) {
        getValues = false;
        group = groups[0];
    } else {
        getValues = true;
        for (var i = 0; i < groups.length; i++) {
            if (groups[i].getAttribute('id') == id) {
                group = groups[i];
                break;
            }
        }
    }
    var message = '';
    // change group-ID
    $('update_group_id').setAttribute('value', id);
    var name = text_getFromParent(group, 'name');
    $('update_group_name').setAttribute('value', name);
    var description = text_getFromParent(group, 'description');
    $('update_group_description').value = description;
    var auto_join = text_getFromParent(group, 'auto_join');
    if (auto_join == "1") {
        $('update_group_auto_join').setAttribute('checked', "checked");
    } else {
        if ($('update_group_auto_join').getAttribute('checked')) {
            $('update_group_auto_join').removeAttributeNode($('update_group_auto_join').getAttributeNode('checked'));
        }
    }
    message = message + 'name = ' + name;
    message = message + 'description = ' + description;
    //alert(message);
}


function clearGroupRights()
{
    table_clear($('group_rights_table'));
}

function buildGroupRights(id)
{
    var getValues = true;
    var groups = groupList.responseXML.getElementsByTagName('group');
    var group;
    // get group with given id
    if (id == 0) {
        getValues = false;
        group = groups[0];
    } else {
        getValues = true;
        for (var i = 0; i < groups.length; i++) {
            if (groups[i].getAttribute('id') == id) {
                group = groups[i];
                break;
            }
        }
    }
    // change group-ID
    $('rights_group_id').setAttribute('value', id);
    // build new table rows
    var rightsList = group.getElementsByTagName('group_rights')[0];
    var rights = rightsList.getElementsByTagName('right');
    var group_rights_table = $('group_rights_table');
    var name;
    var isGroupRight;
    var checkbox;
    var right_id;
    for (var i = 0; i < rights.length; i++) {
        name = text_getFromParent(rights[i], 'name');
        right_id = rights[i].getAttribute('id');
        if (getValues) {
            isGroupRight = text_getFromParent(rights[i], 'is_group_right');
        } else {
            isGroupRight = "0";
        }
        checkbox = document.createElement('input');
        checkbox.setAttribute('type', "checkbox");
        checkbox.setAttribute('name', "group_rights[]");
        checkbox.setAttribute('value', right_id);
        if (isGroupRight == "1") {
            checkbox.setAttribute('checked', "checked");
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
                <select class="admin" name="group_list_select" id="group_list_select" size="<?php print $selectSize; ?>" onchange="groupSelect(event)" tabindex="1">
                    <option value="">select...</option>
                </select>
                <input class="submit" type="submit" value="<?php print $text['delGroup_button']; ?>" tabindex="2" />
            </form>
        </fieldset>
        <p>[ <a href="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=add"><?php print $text['addGroup_link']; ?></a> ]</p>
    </div> <!-- end #user_list -->
</div> <!-- end #user_accounts -->
<div id="user_details">
    <div id="user_data">
        <fieldset>
            <legend id="group_data_legend"><?php print $text['changeGroup']; ?></legend>
            <form action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=update_data" method="post">
                <input id="update_group_id" type="hidden" name="group_id" value="0" />
                <div id="group_data_table">
                    <div class="input_row">
                        <label for="name"><?php print $text['addGroup_name']; ?></label>
                        <input class="admin" id="update_group_name" type="text" name="name" value="<?php print (isset($group_name) ? $group_name : ''); ?>" tabindex="1" />
                    </div>
                    <div class="input_row">
                        <label for="description"><?php print $text['addGroup_description']; ?></label>
                        <textarea class="admin" id="update_group_description" name="description" cols="<?php print $descriptionCols; ?>" rows="<?php print $descriptionRows; ?>" tabindex="2"><?php print (isset($group_description) ? $group_description : ''); ?></textarea>
                    </div>
                    <div class="input_row">
                        <label for="auto_join"><?php print $text['addGroup_autoJoin']; ?></label>
                        <input class="admin" id="update_group_auto_join" type="checkbox" name="auto_join" value="1" tabindex="3"<?php print ((isset($group_auto_join) && $group_auto_join) ? ' checked="checked"' : ''); ?> />
                    </div>
                </div><!-- end #group_data_table -->
                <div class="button_row">
                    <input class="submit" type="submit" value="<?php print $text['changeGroup_submit']; ?>" tabindex="4" />
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
} // end if ($groupAction == 'list')
?>
