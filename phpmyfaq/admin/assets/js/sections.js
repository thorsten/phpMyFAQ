/**
 * JavaScript functions for groups frontend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Timo Wolf <amna.wolf@gmail.com>
 * @copyright 2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-09-21
 */

/*global $:false, alert:false */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';
  
    /**
     * Section related functions
     *
     */
    const getSectionList = () => {
      clearSectionList();
      $.getJSON('index.php?action=ajax&ajax=section&ajaxaction=get_all_sections',
        (data) => {
          $.each(data, (i, val) => {
            $('#section_list_select').append(
              '<option value="' + val.group_id + '">' + val.name + '</option>'
            );
          });
        });
      processSectionList();
    };
  
    const getSectionData = (section_id) => {
      $.getJSON('index.php?action=ajax&ajax=section&ajaxaction=get_section_data&section_id=' + section_id,
        (data) => {
          $('#update_section_id').val(data.section_id);
          $('#update_section_name').val(data.name);
          $('#update_section_description').val(data.description);
        });
    };
  
    const clearSectionList = function () {
      $('#section_list_select').empty();
    };
  
    const clearSectionData = function () {
      $('#update_section_id').empty();
      $('#update_section_name').empty();
      $('#update_section_description').empty();
    };
  
    const sectionSelect = function (event) {
      event = (event) ? event : ((window.event) ? window.event : null);
      if (event) {
        var select = (event.target) ? event.target : ((event.srcElement) ? event.srcElement : null);
        if (select && select.value > 0) {
          clearSectionData();
          getSectionData(select.value);
          clearGroupList();
          getGroupList();
          clearSectionMemberList();
          getSectionMemberList(select.value);
        }
      }
    };
  
    /**
   * Group related functions
   *
   */
  const getGroupList = () => {
    clearGroupList();
    $.getJSON('index.php?action=ajax&ajax=group&ajaxaction=get_all_groups',
      (data) => {
        $.each(data, (i, val) => {
          $('#group_list_select').append(
            '<option value="' + val.group_id + '">' + val.name + '</option>'
          );
        });
      });
    processGroupList();
  };

  const clearGroupList = function () {
    $('#group_list_select').empty();
  };

    /**
     * Member related functions
     *
     */
    const addSectionMembers = () => {
      // make sure that a group is selected
      const selectedSection = $('#section_list_select option:checked');
      if (0 === selectedSection.length) {
        alert('Please choose a group.');
        return;
      }
  
      // get selected users from list
      const selectedGroups = $('#section_group_list option:selected');
      if (selectedGroups.length > 0) {
        selectedGroups.each(function () {
          const members = $('#section_group_list option');
          const group = $(this);
          let isMember = false;
          members.each((member) => {
            isMember = (group.val() === member);
          });
          if (isMember === false) {
            $('#section_member_list').append(
              '<option value="' + $(this).val() + '" selected>' + $(this).text() + '</option>'
            );
          }
        });
      }
    };
  
    const clearSectionMemberList = () => {
      $('#section_member_list').empty();
    };
  
    const getSectionMemberList = function (section_id) {
      if (0 === section_id) {
        clearSectionMemberList();
        return;
      }
      $.getJSON('index.php?action=ajax&ajax=section&ajaxaction=get_all_members&section_id=' + section_id,
        function (data) {
          $('#section_member_list').empty();
          $.each(data, function (i, val) {
            $('#section_member_list').append(
              '<option value="' + val.group_id + '" selected>' + val.name + '</option>'
            );
          });
          $('#update_member_section_id').val(section_id);
        });
    };
  
    const removeSectionMembers = function () {
      // make sure that a group is selected
      var selected_group_list = $('#section_member_list option:selected');
      if (selected_group_list.size() === 0) {
        alert('Please choose a group. ');
        return;
      }
  
      // remove selected members from list
      selected_user_list.each(function () {
        $('#section_member_list option:selected').remove();
      });
    };
  
    const processSectionList = function () {
      clearSectionData();
      clearSectionList();
      clearSectionMemberList();
      getSectionMemberList();
    };
  
    getSectionList();
  
    $('#section_list_select').on('change', function (event) {
      sectionSelect(event);
    });
  
    $('.pmf-add-member').on('click', function () {
      addSectionMembers();
    });
  
    $('.pmf-remove-member').on('click', function () {
      removeSectionMembers();
    });
  
  });