/**
 * JavaScript functions for groups frontend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2016-01-05
 */

/*global $:false, alert:false */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  /**
   * Group related functions
   *
   */
  const getGroupList = () => {
    clearGroupList();
    $.getJSON('index.php?action=ajax&ajax=group&ajaxaction=get_all_groups', (data) => {
      $.each(data, (i, val) => {
        $('#group_list_select').append('<option value="' + val.group_id + '">' + val.name + '</option>');
      });
    });
    processGroupList();
  };

  const getGroupData = (group_id) => {
    $.getJSON('index.php?action=ajax&ajax=group&ajaxaction=get_group_data&group_id=' + group_id, (data) => {
      $('#update_group_id').val(data.group_id);
      $('#update_group_name').val(data.name);
      $('#update_group_description').val(data.description);
      if (1 === parseInt(data.auto_join)) {
        $('#update_group_auto_join').attr('checked', true);
      } else {
        $('#update_group_auto_join').attr('checked', false);
      }
    });
  };

  const clearGroupList = function () {
    $('#group_list_select').empty();
  };

  const clearGroupData = function () {
    const updateGroupAutoJoin = $('update_group_auto_join');
    $('#update_group_id').empty();
    $('#update_group_name').empty();
    $('#update_group_description').empty();
    if ('checked' === updateGroupAutoJoin.attr('checked')) {
      updateGroupAutoJoin.attr('checked', false);
    }
  };

  const getGroupRights = function (group_id) {
    $.getJSON('index.php?action=ajax&ajax=group&ajaxaction=get_group_rights&group_id=' + group_id, function (data) {
      $.each(data, function (i, val) {
        $('#group_right_' + val).prop('checked', true);
      });
      $('#rights_group_id').val(group_id);
    });
  };

  const clearGroupRights = () => {
    $('#groupRights input[type=checkbox]').prop('checked', false);
  };

  const groupSelect = function (event) {
    event = event ? event : window.event ? window.event : null;
    if (event) {
      const select = event.target ? event.target : event.srcElement ? event.srcElement : null;
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
  };

  /**
   * User related functions
   *
   */
  const clearUserList = function () {
    $('#group_user_list option').empty();
  };

  const getUserList = function () {
    $.getJSON('index.php?action=ajax&ajax=group&ajaxaction=get_all_users', function (data) {
      $('#group_user_list').empty();
      $.each(data, function (i, val) {
        $('#group_user_list').append('<option value="' + val.user_id + '">' + val.login + '</option>');
      });
    });
  };

  /**
   * Member related functions
   *
   */
  const addGroupMembers = () => {
    // make sure that a group is selected
    const selectedGroup = $('#group_list_select option:checked');
    if (0 === selectedGroup.length) {
      alert('Please choose a group.');
      return;
    }

    // get selected users from list
    const selectedUsers = $('#group_user_list option:selected');
    if (selectedUsers.length > 0) {
      selectedUsers.each(function () {
        const members = $('#group_member_list option');
        const user = $(this);
        let isMember = false;
        members.each((member) => {
          isMember = user.val() === members[member].value;
        });
        if (isMember === false) {
          $('#group_member_list').append(
            '<option value="' + $(this).val() + '" selected>' + $(this).text() + '</option>'
          );
        }
      });
    }
  };

  const clearMemberList = () => {
    $('#group_member_list').empty();
  };

  const getMemberList = function (group_id) {
    if (0 === group_id) {
      clearMemberList();
      return;
    }
    $.getJSON('index.php?action=ajax&ajax=group&ajaxaction=get_all_members&group_id=' + group_id, function (data) {
      $('#group_member_list').empty();
      $.each(data, function (i, val) {
        $('#group_member_list').append('<option value="' + val.user_id + '" selected>' + val.login + '</option>');
      });
      $('#update_member_group_id').val(group_id);
    });
  };

  const removeGroupMembers = function () {
    // make sure that a group is selected
    var selected_user_list = $('#group_member_list option:selected');
    if (selected_user_list.length === 0) {
      alert('Please choose a user. ');
      return;
    }

    // remove selected members from list
    selected_user_list.each(function () {
      $('#group_member_list option:selected').remove();
    });
  };

  const processGroupList = function () {
    clearGroupData();
    clearGroupRights();
    clearUserList();
    getUserList();
    clearMemberList();
  };

  getGroupList();

  $('#group_list_select').on('change', function (event) {
    groupSelect(event);
  });

  $('.pmf-add-member').on('click', function () {
    addGroupMembers();
  });

  $('.pmf-remove-member').on('click', function () {
    removeGroupMembers();
  });
});
