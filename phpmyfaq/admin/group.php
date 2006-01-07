<?php
/**
* $Id: group.php,v 1.11 2006-01-07 16:58:44 b33blebr0x Exp $
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
$groupSelectSize = 10;
$memberSelectSize = 10;
$descriptionRows = 3;
$descriptionCols = 15;
$defaultGroupAction = 'list';
$errorMessages = array(
    'addGroup_noName' => $PMF_LANG['ad_group_error_noName'], //"Please enter a group name. ",
    'addGroup_db' => $PMF_LANG['ad_adus_dberr'], // "<strong>database error!</strong>"
    'delGroup' => $PMF_LANG['ad_group_error_delete'], //"Group could not be deleted. ",
    'delGroup_noId' => $PMF_LANG['ad_user_error_noId'], //"No ID specified. ",
    'updateGroup' => $PMF_LANG['ad_msg_mysqlerr'], //"Due to a <strong>database error</strong>, the profile could not be saved."
    'updateGroup_noId' => $PMF_LANG['ad_user_error_noId'], //"No ID specified. ",
    'updateRights' => $PMF_LANG['ad_msg_mysqlerr'], //"Due to a <strong>database error</strong>, the profile could not be saved."
    'updateRights_noId' => $PMF_LANG['ad_user_error_noId'], //"No ID  specified. ",
    'updateMembers' => $PMF_LANG['ad_msg_mysqlerr'], //"Due to a <strong>database error</strong>, the profile could not be saved."
    'updateMembers_noId' => $PMF_LANG['ad_user_error_noId'], //"No ID  specified. ",
);
$successMessages = array(
    'addGroup' => $PMF_LANG['ad_group_suc'], //"Group <strong>successfully</strong> added.",
    'delGroup' => $PMF_LANG['ad_group_deleted'], //"The group was successfully deleted.",
    'updateGroup' => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2'],
    'updateRights' => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2'],
    'updateMembers' => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2'],
);
$text = array(
    'header' => "Group Administration",
    'selectGroup' => "Groups",
    'addGroup' => $PMF_LANG['ad_group_add'], // "Add Group"
    'addGroup_confirm' => $PMF_LANG['ad_gen_save'], //"Save",
    'addGroup_cancel' => $PMF_LANG['ad_gen_cancel'], //"Cancel",
    'addGroup_link' => $PMF_LANG['ad_group_add_link'], // "Add Group"
    'addGroup_name' => $PMF_LANG['ad_group_name'], // "Name: "
    'addGroup_description' => $PMF_LANG['ad_group_description'], // "Description: "
    'addGroup_autoJoin' => $PMF_LANG['ad_group_autoJoin'], // "Auto-join:"
    'delGroup' => $PMF_LANG['ad_group_deleteGroup'], //"Delete Group",
    'delGroup_button' => $PMF_LANG['ad_gen_delete'], //"Delete",
    'delGroup_question' => $PMF_LANG['ad_group_deleteQuestion'], //"Are you sure that this group shall be deleted?",
    'delGroup_confirm' => $PMF_LANG['ad_gen_yes'], //"Yes",
    'delGroup_cancel' => $PMF_LANG['ad_gen_no'], //"No",
    'changeGroup' => $PMF_LANG['ad_group_details'], // "Group Details"
    'changeGroup_submit' => $PMF_LANG['ad_gen_save'], //"Save",
    'changeRights' => $PMF_LANG['ad_user_rights'], // "Rights"
    'changeRights_submit' => $PMF_LANG['ad_gen_save'], //"Save",
    'changeRights_checkAll' => $PMF_LANG['ad_user_checkall'], //"Select All",
    'changeRights_uncheckAll' => $PMF_LANG['ad_user_uncheckall'], //"Unselect All",
    'groupMembership' => $PMF_LANG['ad_group_membership'], //"Group Membership",
    'groupMembership_memberList' => $PMF_LANG['ad_group_members'], //"Members",
    'groupMembership_userList' => $PMF_LANG['ad_user_username'], //"Registered users",
    'addMember_button' => $PMF_LANG['ad_group_addMember'], //"Add",
    'removeMember_button' => $PMF_LANG['ad_group_removeMember'], //"Remove",
    'updateMember_submit' => $PMF_LANG['ad_gen_save'], //"Save",
    'groupMembership_selectAll' => $PMF_LANG['ad_user_checkall'], //"Select All",
    'groupMembership_unselectAll' => $PMF_LANG['ad_user_uncheckall'], //"Unselect All",
);

// what shall we do?
// actions defined by url: group_action=
$groupAction = isset($_GET['group_action']) ? $_GET['group_action'] : $defaultGroupAction;
// actions defined by submit button
if (isset($_POST['group_action_deleteConfirm']))
    $groupAction = 'delete_confirm';
if (isset($_POST['cancel']))
    $groupAction = $defaultGroupAction;


// update group members
if ($groupAction == 'update_members') {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = isset($_POST['group_id']) ? $_POST['group_id'] : 0;
    $groupMembers = isset($_POST['group_members']) ? $_POST['group_members'] : array();
    if ($groupId == 0) {
        $message .= '<p class="error">'.$errorMessages['updateMembers_noId'].'</p>';
    } else {
        $user = new PMF_User();
        $perm = $user->perm;
        if (!$perm->removeAllUsersFromGroup($groupId)) {
            $message .= '<p class="error">'.$errorMessages['updateMembers'].'</p>';
        }
        foreach ($groupMembers as $memberId) {
            $perm->addToGroup($memberId, $groupId);
        }
        $message .= '<p class="success">'.sprintf($successMessages['updateMembers'], $perm->getGroupName($groupId)).'</p>';
    }
} // end if ($groupAction == 'update_members')
// update group rights
if ($groupAction == 'update_rights') {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = isset($_POST['group_id']) ? $_POST['group_id'] : 0;
    if ($groupId == 0) {
        $message .= '<p class="error">'.$errorMessages['updateRights_noId'].'</p>';
    } else {
        $user = new PMF_User();
        $perm = $user->perm;
        $groupRights = isset($_POST['group_rights']) ? $_POST['group_rights'] : array();
        if (!$perm->refuseAllGroupRights($groupId)) {
            $message .= '<p class="error">'.$errorMessages['updateRights'].'</p>';
        }
        foreach ($groupRights as $rightId) {
            $perm->grantGroupRight($groupId, $rightId);
        }
        $message .= '<p class="success">'.sprintf($successMessages['updateRights'], $perm->getGroupName($groupId)).'</p>';
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
    clearUserList();
    //buildUserList();
    clearMemberList();
    buildMemberList(0);
}


function getGroupNode(groupId)
{
    // loop through group-elements
    var group_list = groupList.responseXML.getElementsByTagName('grouplist')[0];
    var groups = group_list.getElementsByTagName('group');
    var group = null;
    for (var i = 0; i < groups.length; i++) {
        if (groups[i].getAttribute('id') == groupId) {
            group = groups[i];
            break;
        }
    }
    return group;
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
    var group_rights_table = $('group_rights_table');
    var getValues = true;
    // get group with given id
    if (id == 0) {
        getValues = false;
    } else {
        getValues = true;
        // loop through group-elements
        var groups = groupList.responseXML.getElementsByTagName('group');
        var group;
        for (var i = 0; i < groups.length; i++) {
            if (groups[i].getAttribute('id') == id) {
                group = groups[i];
                break;
            }
        }
    }
    // change group-ID
    $('rights_group_id').setAttribute('value', id);
    var right_id;
    var right_name;
    var right_description;
    var checkbox;
    var isGroupRight = 0;
    // loop through rightlist at beginning (all group rights)
    var rightList = groupList.responseXML.getElementsByTagName('rightlist')[0].getElementsByTagName('right');
    for (var i = 0; i < rightList.length; i++) {
        right_name = text_getFromParent(rightList[i], 'name');
        right_description = text_getFromParent(rightList[i], 'description');
        right_id = rightList[i].getAttribute('id');
        // search for that right in group right list
        isGroupRight = 0;
        if (getValues) {
            var groupRights = group.getElementsByTagName('right');
            var j = 0;
            while (isGroupRight == 0 && j < groupRights.length) {
                if (groupRights[j].getAttribute('id') == right_id) {
                    isGroupRight = 1;
                    break;
                } else {
                    isGroupRight = 0;
                    j++;
                }
            }
        } else {
            isGroupRight = 0;
        }
        // build new table row
        checkbox = document.createElement('input');
        checkbox.setAttribute('type', "checkbox");
        checkbox.setAttribute('name', "group_rights[]");
        checkbox.setAttribute('value', right_id);
        if (isGroupRight == 1) {
            checkbox.setAttribute('checked', "checked");
        }
        table_addRow(group_rights_table, i, checkbox, document.createTextNode(right_name));
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
            clearUserList();
            buildUserList();
            clearMemberList();
            buildMemberList(select.value);
        }
    }
}


function clearUserList()
{
    select_clear($('group_user_list'));
}

function buildUserList()
{
    var user_list = groupList.responseXML.getElementsByTagName('userlist')[0];
    var users = user_list.getElementsByTagName('user');
    var id;
    var textNode;
    var classAttrValue = text_getFromParent(user_list, "select_class");
    for (var i = 0; i < users.length; i++) {
        textNode = document.createTextNode(text_getFromParent(users[i], "login"));
        id = users[i].getAttribute('id');
        select_addOption($('group_user_list'), id, textNode, classAttrValue);
    }
}


function clearMemberList()
{
    select_clear($('group_member_list'));
}

function buildMemberList(groupId)
{
    if (groupId == 0) {
        clearMemberList();
        return;
    }
    // update group-id
    $('update_member_group_id').setAttribute('value', groupId);
    // loop through user_list
    var user_list = groupList.responseXML.getElementsByTagName('userlist')[0];
    var users = user_list.getElementsByTagName('user');
    var user_id;
    var login;
    var isGroupMember = false;
    for (var i = 0; i < users.length; i++) {
        user_id = users[i].getAttribute('id');
        // search for user element in group
        var group = getGroupNode(groupId);
        var group_members = group.getElementsByTagName('group_members')[0];
        var members = group_members.getElementsByTagName('user');
        for (var j = 0; j < members.length; j++) {
            if (members[j].getAttribute('id') == user_id) {
                isGroupMember = true;
                break;
            } else {
                isGroupMember = false;
            }
        }
        if (isGroupMember == true) {
            var login = text_getFromParent(users[i], 'login');
            select_addOption($('group_member_list'), user_id, document.createTextNode(login), text_getFromParent(user_list, 'select_class'));
        }
    }
}


function addGroupMembers()
{
    // make sure that a group is selected
    var group_list = $('group_list_select');
    if (group_list.value == '') {
        alert('Please choose a group. ');
        return;
    }
    // get selected users from list
    var users = $('group_user_list').options;
    for (var i = 0; i < users.length; i++) {
        if (users[i].selected == true) {
            // check if user is already in member list
            var members = $('group_member_list').options;
            var isMember = false;
            for (var j = 0; j < members.length; j++) {
                if (members[j].value == users[i].value) {
                    isMember = true;
                    break;
                } else {
                    isMember = false;
                }
            }
            // add new member
            if (isMember == false) {
                select_addOption($('group_member_list'), users[i].value, document.createTextNode(users[i].text), users[i].getAttribute('class'));
            }
        }
    }
}
function removeGroupMembers()
{
    // make sure that a group is selected
    var group_list = $('group_list_select');
    if (group_list.value == '') {
        alert('Please choose a group. ');
        return;
    }
    // get selected members from list
    var members = $('group_member_list').options;
    for (var i = 0; i < members.length; i++) {
        if (members[i].selected) {
            $('group_member_list').removeChild(members[i]);
            i--; // members.length was reduced by removeChild
        }
    }
}

getGroupList();

/* ]]> */
</script>

<h2><?php print $text['header']; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="groups">
    <div id="group_list">
        <fieldset>
            <legend><?php print $text['selectGroup']; ?></legend>
            <form id="group_select" name="group_select" action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=delete_confirm" method="post">
                <select class="admin" name="group_list_select" id="group_list_select" size="<?php print $groupSelectSize; ?>" onchange="groupSelect(event)" tabindex="1">
                    <option value="">select...</option>
                </select>
                <input class="submit" type="submit" value="<?php print $text['delGroup_button']; ?>" tabindex="2" />
            </form>
        </fieldset>
        <p>[ <a href="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=add"><?php print $text['addGroup_link']; ?></a> ]</p>
    </div> <!-- end #group_list -->
</div> <!-- end #groups -->
<div id="group_details">
    <div id="group_data">
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
    <div id="group_rights">
        <fieldset>
            <legend id="group_rights_legend"><?php print $text['changeRights']; ?></legend>
            <form id="rightsForm" action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=update_rights" method="post">
                <input id="rights_group_id" type="hidden" name="group_id" value="0" />
                <div>
                    <span class="select_all"><a href="javascript:form_checkAll('rightsForm')"><?php print $text['changeRights_checkAll']; ?></a></span>
                    <span class="unselect_all"><a href="javascript:form_uncheckAll('rightsForm')"><?php print $text['changeRights_uncheckAll']; ?></a></span>
                </div>
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
<div id="group_membership">
    <form id="group_membership" name="group_membership" action="<?php print $_SERVER['PHP_SELF']; ?>?aktion=group&amp;group_action=update_members" method="post" onsubmit="select_selectAll('group_member_list')">
        <input id="update_member_group_id" type="hidden" name="group_id" value="0" />
        <fieldset>
            <legend><?php print $text['groupMembership']; ?></legend>
            <fieldset id="group_userList">
                <legend><?php print $text['groupMembership_userList']; ?></legend>
                <div>
                    <span class="select_all"><a href="javascript:select_selectAll('group_user_list')"><?php print $text['groupMembership_selectAll']; ?></a></span>
                    <span class="unselect_all"><a href="javascript:select_unselectAll('group_user_list')"><?php print $text['groupMembership_unselectAll']; ?></a></span>
                </div>
                <select id="group_user_list" multiple="multiple" size="<?php print $memberSelectSize; ?>">
                    <option value="0">...user list...</option>
                </select>
            </fieldset>
            <fieldset id="group_memberList">
                <legend><?php print $text['groupMembership_memberList']; ?></legend>
                <div>
                    <span class="select_all"><a href="javascript:select_selectAll('group_member_list')"><?php print $text['groupMembership_selectAll']; ?></a></span>
                    <span class="unselect_all"><a href="javascript:select_unselectAll('group_member_list')"><?php print $text['groupMembership_unselectAll']; ?></a></span>
                </div>
                <select id="group_member_list" name="group_members[]" multiple="multiple" size="<?php print $memberSelectSize; ?>">
                    <option value="0">...member list...</option>
                </select>
            </fieldset>
            <div id="group_membershipButtons">
                <input type="button" value="<?php print $text['addMember_button']; ?>" onclick="addGroupMembers()" />
                <input type="button" value="<?php print $text['removeMember_button']; ?>" onclick="removeGroupMembers()" />
            </div>
            <div class="clear"></div>
            <div class="button_row">
                <input class="submit" type="submit" value="<?php print $text['updateMember_submit']; ?>" />
            </div>
        </fieldset>
    </form>
</div> <!-- end #group_membership -->
<div class="clear"></div>
<?php
} // end if ($groupAction == 'list')
?>
