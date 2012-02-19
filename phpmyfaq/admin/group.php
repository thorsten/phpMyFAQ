<?php
/**
 * Displays the group management frontend
 *
 * PHP Version 5.2
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
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$permission['editgroup'] && !$permission['delgroup'] && !$permission['addgroup']) {
    exit();
}

// set some parameters
$groupSelectSize    = 10;
$memberSelectSize   = 10;
$descriptionRows    = 3;
$descriptionCols    = 15;
$defaultGroupAction = 'list';

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
if ($groupAction == 'update_members' && $permission['editgroup']) {
    $message      = '';
    $groupAction  = $defaultGroupAction;
    $groupId      = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    $groupMembers = isset($_POST['group_members']) ? $_POST['group_members'] : array();
    
    if ($groupId == 0) {
        $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        $user = new PMF_User();
        $perm = $user->perm;
        if (!$perm->removeAllUsersFromGroup($groupId)) {
            $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
        }
        foreach ($groupMembers as $memberId) {
            $perm->addToGroup((int)$memberId, $groupId);
        }
        $message .= sprintf('<p class="success">%s <strong>%s</strong> %s</p>',
            $PMF_LANG['ad_msg_savedsuc_1'],
            $perm->getGroupName($groupId),
            $PMF_LANG['ad_msg_savedsuc_2']);
    }
}

// update group rights
if ($groupAction == 'update_rights' && $permission['editgroup']) {
    $message     = '';
    $groupAction = $defaultGroupAction;
    $groupId     = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    if ($groupId == 0) {
        $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        $user = new PMF_User();
        $perm = $user->perm;
        $groupRights = isset($_POST['group_rights']) ? $_POST['group_rights'] : array();
        if (!$perm->refuseAllGroupRights($groupId)) {
            $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
        }
        foreach ($groupRights as $rightId) {
            $perm->grantGroupRight($groupId, (int)$rightId);
        }
        $message .= sprintf('<p class="success">%s <strong>%s</strong> %s</p>',
            $PMF_LANG['ad_msg_savedsuc_1'],
            $perm->getGroupName($groupId),
            $PMF_LANG['ad_msg_savedsuc_2']);
    }
}

// update group data
if ($groupAction == 'update_data' && $permission['editgroup']) {
    $message     = '';
    $groupAction = $defaultGroupAction;
    $groupId     = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    if ($groupId == 0) {
        $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        $groupData  = array();
        $dataFields = array('name', 'description', 'auto_join');
        foreach ($dataFields as $field) {
            $groupData[$field] = PMF_Filter::filterInput(INPUT_POST, $field, FILTER_SANITIZE_STRING, '');
        }
        $user = new PMF_User();
        $perm = $user->perm;
        if (!$perm->changeGroup($groupId, $groupData)) {
            $message .= sprintf('<p class="error">%s<br />%s</p>', $PMF_LANG['ad_msg_mysqlerr'], $perm->_db->error());
        } else {
            $message .= sprintf('<p class="success">%s <strong>%s</strong> %s</p>',
                $PMF_LANG['ad_msg_savedsuc_1'],
                $perm->getGroupName($groupId),
                $PMF_LANG['ad_msg_savedsuc_2']);
        }
    }
}

// delete group confirmation
if ($groupAction == 'delete_confirm' && $permission['delgroup']) {
    $message = '';
    $user    = new PMF_User_CurrentUser();
    $perm    = $user->perm;
    $groupId = PMF_Filter::filterInput(INPUT_POST, 'group_list_select', FILTER_VALIDATE_INT, 0);
    if ($groupId <= 0) {
        $message    .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_noId']);
        $groupAction = $defaultGroupAction;
    } else {
        $group_data = $perm->getGroupData($groupId);
?>
        <header>
            <h2><?php print $PMF_LANG['ad_group_deleteGroup']; ?> "<?php print $group_data['name']; ?>"</h2>
        </header>
        <p><?php print $PMF_LANG['ad_group_deleteQuestion']; ?></p>
        <form action ="?action=group&amp;group_action=delete" method="post">
            <input type="hidden" name="group_id" value="<?php print $groupId; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
            <p>
                <input class="submit" type="submit" name="cancel" value="<?php print $PMF_LANG['ad_gen_cancel']; ?>" />
                <input type="submit" value="<?php print $PMF_LANG['ad_gen_save']; ?>" />
            </p>
        </form>
<?php
    }
}

if ($groupAction == 'delete' && $permission['delgroup']) {
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
        $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        if (!$user->perm->deleteGroup($groupId) && !$csrfOkay) {
            $message .= sprintf('<p class="error">%s</p>', $PMF_LANG['ad_group_error_delete']);
        } else {
            $message .= sprintf('<p class="success">%s</p>', $PMF_LANG['ad_group_deleted']);
        }
        $userError = $user->error();
        if ($userError != "") {
            $message .= sprintf('<p class="error">%s</p>', $userError);
        }
    }

}

if ($groupAction == 'addsave' && $permission['addgroup']) {
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
        $messages[] = $PMF_LANG['ad_group_error_noName'];
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
            $messages[] = $PMF_LANG['ad_adus_dberr'];
    }
    // no errors, show list
    if (count($messages) == 0) {
        $groupAction = $defaultGroupAction;
        $message = sprintf('<p class="success">%s</p>', $PMF_LANG['ad_group_suc']);
    // display error messages and show form again
    } else {
        $groupAction = 'add';
        $message = '<p class="error">';
        foreach ($messages as $err) {
            $message .= $err . '<br />';
        }
        $message .= '</p>';
    }
}

if (!isset($message))
    $message = '';

// show new group form
if ($groupAction == 'add' && $permission['addgroup']) {
    $user = new PMF_User_CurrentUser();
?>
        <header>
            <h2><?php print $PMF_LANG['ad_group_add']; ?></h2>
        </header>

        <div id="user_message"><?php print $message; ?></div>
        <form class="form-horizontal" name="group_create" action="?action=group&amp;group_action=addsave" method="post">
        <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />

            <div class="control-group">
                <label for="group_name"><?php print $PMF_LANG['ad_group_name']; ?></label>
                <div class="controls">
                    <input type="text" name="group_name" id="group_name" autofocus="autofocus"
                           value="<?php print (isset($group_name) ? $group_name : ''); ?>" tabindex="1" />
                </div>
            </div>

            <div class="control-group">
                <label for="group_description"><?php print $PMF_LANG['ad_group_description']; ?></label>
                <div class="controls">
                    <textarea name="group_description" id="group_description" cols="<?php print $descriptionCols; ?>"
                              rows="<?php print $descriptionRows; ?>" tabindex="2"
                    ><?php print (isset($group_description) ? $group_description : ''); ?></textarea>
                </div>
            </div>

            <div class="control-group">
                <label for="group_auto_join"><?php print $PMF_LANG['ad_group_autoJoin']; ?></label>
                <div class="controls">
                    <input type="checkbox" name="group_auto_join" id="group_auto_join" value="1" tabindex="3"
                    <?php print ((isset($group_auto_join) && $group_auto_join) ? ' checked="checked"' : ''); ?> />

                </div>
            </div>

            <div class="form-actions">
                <input class="btn-primary" type="submit" value="<?php print $PMF_LANG['ad_gen_save']; ?>" tabindex="4" />
                <input class="btn-info" type="reset" name="cancel" value="<?php print $PMF_LANG['ad_gen_cancel']; ?>" tabindex="5" />
            </div>
        </form>
<?php
} // end if ($groupAction == 'add')

// show list of users
if ($groupAction == 'list') {
?>

        <header>
            <h2><?php print $PMF_LANG['ad_menu_group_administration']; ?></h2>
        </header>
    
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

        <div id="user_message"><?php print $message; ?></div>
        <div class="row">

            <div class="span3" id="group_list">
                <fieldset>
                    <legend><?php print $PMF_LANG['ad_groups']; ?></legend>
                    <form id="group_select" name="group_select" action="?action=group&amp;group_action=delete_confirm"
                          method="post">
                        <p>
                            <select name="group_list_select" id="group_list_select" style="width: 150px;"
                                    onchange="groupSelect(event)" size="<?php print $groupSelectSize; ?>" tabindex="1">
                            </select>
                        </p>
                        <p>
                            <input class="btn-danger" type="submit" value="<?php print $PMF_LANG['ad_gen_delete']; ?>" tabindex="2" />
                        </p>
                    </form>
                </fieldset>
                <p>[ <a href="?action=group&amp;group_action=add"><?php print $PMF_LANG['ad_group_add_link']; ?></a> ]</p>
            </div>

            <div class="span3" id="groupMemberships">
                <form id="group_membership" name="group_membership" action="?action=group&amp;group_action=update_members"
                  method="post" onsubmit="select_selectAll('group_member_list')">
                <input id="update_member_group_id" type="hidden" name="group_id" value="0" />
                <fieldset>
                    <legend><?php print $PMF_LANG['ad_group_membership']; ?></legend>
                    <fieldset id="group_userList">
                        <legend><?php print $PMF_LANG['ad_user_username']; ?></legend>
                        <p>
                            <span class="select_all">
                                <a href="javascript:select_selectAll('group_user_list')">
                                    <?php print $PMF_LANG['ad_user_checkall']; ?>
                                </a>
                            </span>
                            |
                            <span class="unselect_all">
                                <a href="javascript:select_unselectAll('group_user_list')">
                                    <?php print $PMF_LANG['ad_user_uncheckall']; ?>
                                </a>
                            </span>
                        </p>
                        <select id="group_user_list" multiple="multiple" style="width: 150px;"
                                size="<?php print $memberSelectSize; ?>">
                            <option value="0">...user list...</option>
                        </select>
                    </fieldset>
                    <div id="group_membershipButtons">
                        <input class="btn-success" type="button" value="<?php print $PMF_LANG['ad_group_addMember']; ?>"
                               onclick="addGroupMembers()" />
                        <input class="btn-danger" type="button" value="<?php print $PMF_LANG['ad_group_removeMember']; ?>"
                               onclick="removeGroupMembers()" />
                    </div>
                    <fieldset id="group_memberList">
                        <legend><?php print $PMF_LANG['ad_group_members']; ?></legend>
                        <p>
                            <span class="select_all">
                                <a href="javascript:select_selectAll('group_member_list')">
                                    <?php print $PMF_LANG['ad_user_checkall']; ?>
                                </a>
                            </span>
                            |
                            <span class="unselect_all">
                                <a href="javascript:select_unselectAll('group_member_list')">
                                    <?php print $PMF_LANG['ad_user_uncheckall']; ?>
                                </a>
                            </span>
                        </p>
                        <select id="group_member_list" name="group_members[]" multiple="multiple" style="width: 150px;"
                                size="<?php print $memberSelectSize; ?>">
                            <option value="0">...member list...</option>
                        </select>
                    </fieldset>
                    <p>
                        <input class="btn-primary" type="submit" value="<?php print $PMF_LANG['ad_gen_save']; ?>" />
                    </p>
                </fieldset>
                </form>
            </div>

            <div class="span3" id="groupDetails">
                <div id="group_data">
                    <fieldset>
                        <legend id="group_data_legend"><?php print $PMF_LANG['ad_group_details']; ?></legend>
                        <form action="?action=group&amp;group_action=update_data" method="post">
                            <input id="update_group_id" type="hidden" name="group_id" value="0" />
                            <div id="group_data_table">
                                <p>
                                    <label for="update_group_name" class="small"><?php print $PMF_LANG['ad_group_name']; ?></label>
                                    <input id="update_group_name" type="text" name="name" style="width: 150px;"
                                           tabindex="1" value="<?php print (isset($group_name) ? $group_name : ''); ?>" />
                                </p>
                                <p>
                                    <label for="update_group_description" class="small"><?php print $PMF_LANG['ad_group_description']; ?></label>
                                    <textarea id="update_group_description" name="description" cols="<?php print $descriptionCols; ?>"
                                              rows="<?php print $descriptionRows; ?>" style="width: 150px;"
                                              tabindex="2"><?php print (isset($group_description) ? $group_description : ''); ?></textarea>
                                </p>
                                <p>
                                    <label for="update_group_auto_join" class="small"><?php print $PMF_LANG['ad_group_autoJoin'] ?></label>
                                    <input id="update_group_auto_join" type="checkbox" name="auto_join" value="1"
                                           tabindex="3"<?php print ((isset($group_auto_join) && $group_auto_join) ? ' checked="checked"' : ''); ?> />
                                </p>
                            </div><!-- end #group_data_table -->
                            <p>
                                <input class="btn-primary" type="submit" value="<?php print $PMF_LANG['ad_gen_save']; ?>" tabindex="4" />
                            </p>
                        </form>
                    </fieldset>
                </div> <!-- end #groupDetails -->
                <div id="groupRights">
                    <fieldset>
                        <legend id="group_rights_legend"><?php print $PMF_LANG['ad_user_rights']; ?></legend>
                        <form id="rightsForm" action="?action=group&amp;group_action=update_rights" method="post">
                            <input id="rights_group_id" type="hidden" name="group_id" value="0" />
                            <div>
                                <span class="select_all">
                                    <a href="javascript:form_checkAll('rightsForm')">
                                        <?php print $PMF_LANG['ad_user_checkall']; ?>
                                    </a>
                                </span>
                                |
                                <span class="unselect_all">
                                    <a href="javascript:form_uncheckAll('rightsForm')">
                                        <?php print $PMF_LANG['ad_user_uncheckall']; ?>
                                    </a>
                                </span>
                            </div>
                            <table id="group_rights_table">
                            <?php foreach ($user->perm->getAllRightsData() as $right) { ?>
                            <tr>
                                <td><input id="group_right_<?php print $right['right_id']; ?>" type="checkbox"
                                           name="group_rights[]" value="<?php print $right['right_id']; ?>"/></td>
                                <td><?php print (isset($PMF_LANG['rightsLanguage'][$right['name']])
                                                 ?
                                                 $PMF_LANG['rightsLanguage'][$right['name']]
                                                 :
                                                 $right['description']); ?></td>
                            </tr>
                            <?php } ?>
                            </table>
                            <p>
                                <input class="btn-primary" type="submit" value="<?php print $PMF_LANG['ad_gen_save']; ?>" />
                            </p>
                        </form>
                    </fieldset>
                </div> <!-- end #groupRights -->
            </div> <!-- end #groupDetails -->
        </div> <!-- end #groupInteface -->
<?php
}
