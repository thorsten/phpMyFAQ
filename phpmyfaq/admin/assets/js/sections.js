/**
 * JavaScript functions for groups frontend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Timo Wolf <amna.wolf@gmail.com>
 * @copyright 2018-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-09-21
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
    $.getJSON('index.php?action=ajax&ajax=section&ajaxaction=get_all_sections', (data) => {
      $.each(data, (i, val) => {
        $('#section_list_select').append('<option value="' + val.section_id + '">' + val.name + '</option>');
      });
    });
    processSectionList();
  };

  const getSectionData = (section_id) => {
    $.getJSON('index.php?action=ajax&ajax=section&ajaxaction=get_section_data&section_id=' + section_id, (data) => {
      $('#update_section_id').val(data.id);
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
    event = event ? event : window.event ? window.event : null;
    if (event) {
      const select = event.target ? event.target : event.srcElement ? event.srcElement : null;
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
    $.getJSON('index.php?action=ajax&ajax=group&ajaxaction=get_all_groups', (data) => {
      $.each(data, (i, val) => {
        $('#group_list_select').append('<option value="' + val.group_id + '">' + val.name + '</option>');
      });
    });
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
    const selectedSection = $('#section_list_select option:selected');
    if (0 === selectedSection.length) {
      alert('Please choose a section.');
      return;
    }

    // get selected groups from list
    const selectedGroups = $('#group_list_select option:selected');
    if (selectedGroups.length > 0) {
      selectedGroups.each(function () {
        const members = $('#section_member_list option');
        const group = $(this);
        let isMember = false;
        members.each((member) => {
          isMember = group.val() === members[member].value;
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
    $.getJSON(
      'index.php?action=ajax&ajax=section&ajaxaction=get_all_members&section_id=' + section_id,
      function (data) {
        $('#section_member_list').empty();
        $.each(data, function (i, val) {
          $('#section_member_list').append('<option value="' + val.group_id + '" selected>' + val.name + '</option>');
        });
        $('#update_member_section_id').val(section_id);
      }
    );
  };

  const removeSectionMembers = function () {
    // make sure that a section is selected
    const selectedMemberList = $('#section_member_list option:selected');
    if (selectedMemberList.length === 0) {
      alert('Please choose a group. ');
      return;
    }

    // remove selected members from list
    selectedMemberList.each(function () {
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

  $('.pmf-add-section-member').on('click', function () {
    addSectionMembers();
  });

  $('.pmf-remove-section-member').on('click', function () {
    removeSectionMembers();
  });
});
