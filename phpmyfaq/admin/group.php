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
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$permission['edituser'] and !$permission['deluser'] and !$permission['adduser']) {
    exit();
}

// set some parameters
$groupSelectSize    = 10;
$memberSelectSize   = 10;
$descriptionRows    = 3;
$descriptionCols    = 15;
$defaultGroupAction = 'list';

$errorMessages = array(
    'addGroup_noName'    => $PMF_LANG['ad_group_error_noName'],
    'addGroup_db'        => $PMF_LANG['ad_adus_dberr'],
    'delGroup'           => $PMF_LANG['ad_group_error_delete'],
    'delGroup_noId'      => $PMF_LANG['ad_user_error_noId'],
    'updateGroup'        => $PMF_LANG['ad_msg_mysqlerr'],
    'updateGroup_noId'   => $PMF_LANG['ad_user_error_noId'],
    'updateRights'       => $PMF_LANG['ad_msg_mysqlerr'],
    'updateRights_noId'  => $PMF_LANG['ad_user_error_noId'],
    'updateMembers'      => $PMF_LANG['ad_msg_mysqlerr'],
    'updateMembers_noId' => $PMF_LANG['ad_user_error_noId']);

$successMessages = array(
    'addGroup'           => $PMF_LANG['ad_group_suc'],
    'delGroup'           => $PMF_LANG['ad_group_deleted'],
    'updateGroup'        => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2'],
    'updateRights'       => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2'],
    'updateMembers'      => $PMF_LANG['ad_msg_savedsuc_1'].' <strong>%s</strong> '.$PMF_LANG['ad_msg_savedsuc_2']);

$text = array(
    'header'                      => $PMF_LANG["ad_menu_group_administration"],
    'selectGroup'                 => $PMF_LANG['ad_groups'],
    'addGroup'                    => $PMF_LANG['ad_group_add'],
    'addGroup_confirm'            => $PMF_LANG['ad_gen_save'],
    'addGroup_cancel'             => $PMF_LANG['ad_gen_cancel'],
    'addGroup_link'               => $PMF_LANG['ad_group_add_link'],
    'addGroup_name'               => $PMF_LANG['ad_group_name'],
    'addGroup_description'        => $PMF_LANG['ad_group_description'],
    'addGroup_autoJoin'           => $PMF_LANG['ad_group_autoJoin'],
    'delGroup'                    => $PMF_LANG['ad_group_deleteGroup'],
    'delGroup_button'             => $PMF_LANG['ad_gen_delete'],
    'delGroup_question'           => $PMF_LANG['ad_group_deleteQuestion'],
    'delGroup_confirm'            => $PMF_LANG['ad_gen_yes'],
    'delGroup_cancel'             => $PMF_LANG['ad_gen_no'],
    'changeGroup'                 => $PMF_LANG['ad_group_details'],
    'changeGroup_submit'          => $PMF_LANG['ad_gen_save'],
    'changeRights'                => $PMF_LANG['ad_user_rights'],
    'changeRights_submit'         => $PMF_LANG['ad_gen_save'],
    'changeRights_checkAll'       => $PMF_LANG['ad_user_checkall'],
    'changeRights_uncheckAll'     => $PMF_LANG['ad_user_uncheckall'],
    'groupMembership'             => $PMF_LANG['ad_group_membership'],
    'groupMembership_memberList'  => $PMF_LANG['ad_group_members'],
    'groupMembership_userList'    => $PMF_LANG['ad_user_username'],
    'addMember_button'            => $PMF_LANG['ad_group_addMember'],
    'removeMember_button'         => $PMF_LANG['ad_group_removeMember'],
    'updateMember_submit'         => $PMF_LANG['ad_gen_save'],
    'groupMembership_selectAll'   => $PMF_LANG['ad_user_checkall'],
    'groupMembership_unselectAll' => $PMF_LANG['ad_user_uncheckall']);

// what shall we do?
// actions defined by url: group_action=
$groupAction = PMF_Filter::filterInput(INPUT_GET, 'group_action', FILTER_SANITIZE_STRING, $defaultGroupAction);
// actions defined by submit button
if (isset($_POST['group_action_deleteConfirm'])) {
    $groupAction = 'delete_confirm';
}
if (isset($_POST['cancel'])) {
    $groupAction = $defaultGroupAction;
}

// update group members
if ($groupAction == 'update_members') {
    $message      = '';
    $groupAction  = $defaultGroupAction;
    $groupId      = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
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
            $perm->addToGroup((int)$memberId, $groupId);
        }
        $message .= '<p class="success">'.sprintf($successMessages['updateMembers'], $perm->getGroupName($groupId)).'</p>';
    }
} // end if ($groupAction == 'update_members')
// update group rights
if ($groupAction == 'update_rights') {
    $message     = '';
    $groupAction = $defaultGroupAction;
    $groupId     = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
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
            $perm->grantGroupRight($groupId, (int)$rightId);
        }
        $message .= '<p class="success">'.sprintf($successMessages['updateRights'], $perm->getGroupName($groupId)).'</p>';
    }
} // end if ($groupAction == 'update_rights')
// update group data
if ($groupAction == 'update_data') {
    $message     = '';
    $groupAction = $defaultGroupAction;
    $groupId     = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    if ($groupId == 0) {
        $message .= '<p class="error">'.$errorMessages['updateGroup_noId'].'</p>';
    } else {
        $groupData  = array();
        $dataFields = array('name', 'description', 'auto_join');
        foreach ($dataFields as $field) {
            $groupData[$field] = PMF_Filter::filterInput(INPUT_POST, $field, FILTER_SANITIZE_STRING, '');
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
    $user    = new PMF_User_CurrentUser();
    $perm    = $user->perm;
    $groupId = PMF_Filter::filterInput(INPUT_POST, 'group_list_select', FILTER_VALIDATE_INT, 0);
    if ($groupId <= 0) {
        $message    .= '<p class="error">'.$errorMessages['delGroup_noId'].'</p>';
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
        <form action ="?action=group&amp;group_action=delete" method="post">
            <input type="hidden" name="group_id" value="<?php print $groupId; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
            <div class="button_row">
                <input class="reset" type="submit" name="cancel" value="<?php print $text['delGroup_cancel']; ?>" />
                <input class="submit" type="submit" value="<?php print $text['delGroup_confirm']; ?>" />
            </div>
        </form>
    </fieldset>
</div>
<?php
    }
}

if ($groupAction == 'delete') {
    $message   = '';
    $user      = new PMF_User();
    $groupId   = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    $csrfOkay  = true;
    $csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
        $csrfOkay = false; 
    }
    $groupAction = $defaultGroupAction;
    if ($groupId <= 0) {
        $message .= '<p class="error">'.$errorMessages['delGroup_noId'].'</p>';
    } else {
        if (!$user->perm->deleteGroup($groupId) && !$csrfOkay) {
            $message .= '<p class="error">'.$errorMessages['delGroup'].'</p>';
        } else {
            $message .= '<p class="success">'.$successMessages['delGroup'].'</p>';
        }
        $userError = $user->error();
        if ($userError != "") {
            $message .= '<p>ERROR: '.$userError.'</p>';
        }
    }

}

if ($groupAction == 'addsave') {
    $user              = new PMF_User();
    $message           = '';
    $messages          = array();
    $group_name        = PMF_Filter::filterInput(INPUT_POST, 'group_name', FILTER_SANITIZE_STRING, '');
    $group_description = PMF_Filter::filterInput(INPUT_POST, 'group_description', FILTER_SANITIZE_STRING, '');
    $group_auto_join   = PMF_Filter::filterInput(INPUT_POST, 'group_auto_join', FILTER_SANITIZE_STRING, '');
    $csrfOkay          = true;
    $csrfToken         = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
        $csrfOkay = false; 
    }
    // check group name
    if ($group_name == '') {
        $messages[] = $errorMessages['addGroup_noName'];
    }
    // ok, let's go
    if (count($messages) == 0 && $csrfOkay) {
        // create group
        $group_data = array(
            'name'        => $group_name,
            'description' => $group_description,
            'auto_join'   => $group_auto_join
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
    $user = new PMF_User_CurrentUser();
?>
<h2><?php print $text['header']; ?></h2>
<div id="user_message"><?php print $message; ?></div>
<div id="group_create">
    <form name="group_create" action="?action=group&amp;group_action=addsave" method="post">
    <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
        <fieldset>
            <legend><?php print $text['addGroup']; ?></legend>
                <div class="input_row">
                    <label for="group_name"><?php print $text['addGroup_name']; ?></label>
                    <input type="text" name="group_name" value="<?php print (isset($group_name) ? $group_name : ''); ?>" tabindex="1" />
                </div>
                <div class="input_row">
                    <label for="group_description"><?php print $text['addGroup_description']; ?></label>
                    <textarea name="group_description" cols="<?php print $descriptionCols; ?>" rows="<?php print $descriptionRows; ?>" tabindex="2"><?php print (isset($group_description) ? $group_description : ''); ?></textarea>
                </div>
                <div class="input_row">
                    <label for="group_auto_join"><?php print $text['addGroup_autoJoin']; ?></label>
                    <input type="checkbox" name="group_auto_join" value="1" tabindex="3"<?php print ((isset($group_auto_join) && $group_auto_join) ? ' checked="checked"' : ''); ?> />
                </div>
        </fieldset>
        <br />
        <div class="button_row">
            <input class="submit" type="submit" value="<?php print $text['addGroup_confirm']; ?>" tabindex="6" />
            <input class="reset" name="cancel" type="submit" value="<?php print $text['addGroup_cancel']; ?>" tabindex="7" />
        </div>
        <div class="clear"></div>
    </form>
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

var groupList;

/**
 * Load groups as JSON using HTTP GET
 *
 * @return void
 */
function getGroupList()
{
    clearGroupList();
    $.getJSON("index.php?action=ajax&ajax=group&ajaxaction=get_all_groups",
        function(data) {
            $.each(data, function(i, val) {
                $('#group_list_select').append('<option value="' + val.group_id + '">' + val.name + '</option>');
            });
        });
    processGroupList();
}

/**
 * Processes everything we need 
 *
 * @return void
 */
function processGroupList()
{
    clearGroupData();
    clearGroupRights();
    clearUserList();
    getUserList();
    clearMemberList();
}

/**
 * Removes all entries from the group list
 *
 * @return void
 */
function clearGroupList()
{
    $('#group_list_select').empty();
}

/**
 * Removes all values from the group data form
 *
 * @return void
 */
function clearGroupData()
{
    $('#update_group_id').empty();
    $('#update_group_name').empty();
    $('#update_group_description').empty();
    if ($('update_group_auto_join').attr('checked') == 'checked') {
        $('update_group_auto_join').attr('checked', false);
    }
}

/**
 * Returns the group data as JSON object and fills the input forms
 *
 * @param integer group_id Group ID
 */
function getGroupData(group_id)
{
    $.getJSON("index.php?action=ajax&ajax=group&ajaxaction=get_group_data&group_id=" + group_id,
        function(data) {
            $('#update_group_id').val(data.group_id);
            $('#update_group_name').val(data.name);
            $('#update_group_description').val(data.description);
            if (data.auto_join == 1) {
                $('#update_group_auto_join').attr('checked', true);
            } else {
                $('#update_group_auto_join').attr('checked', false);
            }
        });
}

/**
 * Unchecks all checkboxes
 *
 * @return void
 */
function clearGroupRights()
{
    $('#group_rights_table input').attr('checked', false);
}

/**
 * Returns the group rights as JSON object and checks the checkboxes
 *
 * @param integer group_id Group ID
 */
function getGroupRights(group_id)
{
    $.getJSON("index.php?action=ajax&ajax=group&ajaxaction=get_group_rights&group_id=" + group_id,
        function(data) {
            $.each(data, function(i, val) {
                $('#group_right_' + val).attr('checked', true);
            });
            $('#rights_group_id').val(group_id);
        });
}

/**
 * Handles the group selection event
 *
 * @return void
 */
function groupSelect(evt)
{
    evt = (evt) ? evt : ((windows.event) ? windows.event : null);
    if (evt) {
        var select = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
        if (select && select.value > 0) {
            clearGroupData();
            getGroupData(select.value);
            clearGroupRights();
            getGroupRights(select.value);
            clearUserList();
            getUserList();
            clearMemberList();
            getMemberList(select.value);
        }
    }
}

/**
 * Clears the user list
 *
 * @return void
 */
function clearUserList()
{
    $('#group_user_list option').empty();
}

/**
 * Adds all users to the user list select box
 *
 * @return void
 */
function getUserList()
{
    $.getJSON("index.php?action=ajax&ajax=group&ajaxaction=get_all_users",
        function(data) {
    	$('#group_user_list').empty();
            $.each(data, function(i, val) {
                $('#group_user_list').append('<option value="' + val.user_id + '">' + val.login + '</option>');
            });

        });
}

/**
 * Clears the member list
 *
 * @return void
 */
function clearMemberList()
{
    $('#group_member_list').empty();
}

/**
 * Adds all members to the members list select box
 *
 * @return void
 */
function getMemberList(group_id)
{
    if (group_id == 0) {
        clearMemberList();
        return;
    }
    $.getJSON("index.php?action=ajax&ajax=group&ajaxaction=get_all_members&group_id=" + group_id,
            function(data) {
                $('#group_member_list').empty();
                $.each(data, function(i, val) {
                    $('#group_member_list').append('<option value="' + val.user_id + '">' + val.login + '</option>');
                });
                $('#update_member_group_id').val(group_id);
            });
}

/**
 * Adds a user to the group members selection list
 *
 * @return void
 */
function addGroupMembers()
{
    // make sure that a group is selected
    var selected_group = $('#group_list_select option:selected');
    if (selected_group.size() == 0) {
        alert('Please choose a group.');
        return;
    }

    // get selected users from list
    var selected_users = $('#group_user_list option:selected');
    if (selected_users.size() > 0) {
        selected_users.each(function() {

            var members  = $('#group_member_list option');
            var isMember = false;
            var user     = $(this);

            members.each(function(member) {

                if (user.val() == member) {
                    isMember = true;
                } else {
                    isMember = false;
                }
            });

            if (isMember == false) {
            	$('#group_member_list').append('<option value="' + $(this).val() + '">' + $(this).text() + '</option>');
            }
            
        });
    }
}

/**
 *Remove users from a group
 *
 * @return void
 */
function removeGroupMembers()
{
    // make sure that a group is selected
    var selected_user_list = $('#group_member_list option:selected');
    if (selected_user_list.size() == 0) {
        alert('Please choose a user. ');
        return;
    }
    
    // remove selected members from list
    selected_user_list.each(function(i, option){
        document.getElementById('group_member_list').options[option.index] = null
    })
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
            <form id="group_select" name="group_select" action="?action=group&amp;group_action=delete_confirm" method="post">
                <select class="admin" name="group_list_select" id="group_list_select" size="<?php print $groupSelectSize; ?>" onchange="groupSelect(event)" tabindex="1">
                </select>
                <br />
                <input class="submit" type="submit" value="<?php print $text['delGroup_button']; ?>" tabindex="2" />
            </form>
        </fieldset>
        <p>[ <a href="?action=group&amp;group_action=add"><?php print $text['addGroup_link']; ?></a> ]</p>
    </div> <!-- end #group_list -->
</div> <!-- end #groups -->
<div id="group_membership">
    <form id="group_membership" name="group_membership" action="?action=group&amp;group_action=update_members" method="post" onsubmit="select_selectAll('group_member_list')">
        <input id="update_member_group_id" type="hidden" name="group_id" value="0" />
        <fieldset>
            <legend><?php print $text['groupMembership']; ?></legend>
            <fieldset id="group_userList">
                <legend><?php print $text['groupMembership_userList']; ?></legend>
                <div>
                    <span class="select_all"><a href="javascript:select_selectAll('group_user_list')"><?php print $text['groupMembership_selectAll']; ?></a></span> |
                    <span class="unselect_all"><a href="javascript:select_unselectAll('group_user_list')"><?php print $text['groupMembership_unselectAll']; ?></a></span>
                </div>
                <select id="group_user_list" multiple="multiple" size="<?php print $memberSelectSize; ?>">
                    <option value="0">...user list...</option>
                </select>
            </fieldset>
            <div id="group_membershipButtons">
                <input type="button" value="<?php print $text['addMember_button']; ?>" onclick="addGroupMembers()" />
                <input type="button" value="<?php print $text['removeMember_button']; ?>" onclick="removeGroupMembers()" />
            </div>
            <fieldset id="group_memberList">
                <legend><?php print $text['groupMembership_memberList']; ?></legend>
                <div>
                    <span class="select_all"><a href="javascript:select_selectAll('group_member_list')"><?php print $text['groupMembership_selectAll']; ?></a></span> |
                    <span class="unselect_all"><a href="javascript:select_unselectAll('group_member_list')"><?php print $text['groupMembership_unselectAll']; ?></a></span>
                </div>
                <select id="group_member_list" name="group_members[]" multiple="multiple" size="<?php print $memberSelectSize; ?>">
                    <option value="0">...member list...</option>
                </select>
            </fieldset>
            <div class="clear"></div>
            <div class="button_row">
                <input class="submit" type="submit" value="<?php print $text['updateMember_submit']; ?>" />
            </div>
        </fieldset>
    </form>
</div> <!-- end #group_membership -->
<div id="group_details">
    <div id="group_data">
        <fieldset>
            <legend id="group_data_legend"><?php print $text['changeGroup']; ?></legend>
            <form action="?action=group&amp;group_action=update_data" method="post">
                <input id="update_group_id" type="hidden" name="group_id" value="0" />
                <div id="group_data_table">
                    <div class="input_row">
                        <label for="name"><?php print $text['addGroup_name']; ?></label>
                        <input id="update_group_name" type="text" name="name" value="<?php print (isset($group_name) ? $group_name : ''); ?>" tabindex="1" />
                    </div>
                    <div class="input_row">
                        <label for="description"><?php print $text['addGroup_description']; ?></label>
                        <textarea id="update_group_description" name="description" cols="<?php print $descriptionCols; ?>" rows="<?php print $descriptionRows; ?>" tabindex="2"><?php print (isset($group_description) ? $group_description : ''); ?></textarea>
                    </div>
                    <div class="input_row">
                        <label for="auto_join"><?php print $text['addGroup_autoJoin']; ?></label>
                        <input id="update_group_auto_join" type="checkbox" name="auto_join" value="1" tabindex="3"<?php print ((isset($group_auto_join) && $group_auto_join) ? ' checked="checked"' : ''); ?> />
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
            <form id="rightsForm" action="?action=group&amp;group_action=update_rights" method="post">
                <input id="rights_group_id" type="hidden" name="group_id" value="0" />
                <div>
                    <span class="select_all"><a href="javascript:form_checkAll('rightsForm')"><?php print $text['changeRights_checkAll']; ?></a></span> |
                    <span class="unselect_all"><a href="javascript:form_uncheckAll('rightsForm')"><?php print $text['changeRights_uncheckAll']; ?></a></span>
                </div>
                <table id="group_rights_table">
<?php foreach ($user->perm->getAllRightsData() as $right) { ?>
                <tr>
                    <td><input id="group_right_<?php print $right['right_id']; ?>" type="checkbox" name="group_rights[]" value="<?php print $right['right_id']; ?>"/></td>
                    <td><?php print (isset($PMF_LANG['rightsLanguage'][$right['name']]) ? $PMF_LANG['rightsLanguage'][$right['name']] : $right['description']); ?></td>
                </tr>
<?php } ?>
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
}
