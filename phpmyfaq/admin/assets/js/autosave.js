/**
 * Auto Save functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <ab@php.net>
 * @copyright 2012-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-07-07
 */

/*global document: false, window: false, $: false */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  const autoSaveInterval = document.querySelector('meta[name="phpmyfaq-config-autosave-seconds"]').content;

  $(window).on('unload', () => {
    if (typeof window.tinyMCE !== 'undefined' && window.tinyMCE.activeEditor !== null) {
      if (window.tinyMCE.activeEditor.isDirty()) {
        // @todo should be translated
        const confirmed = window.confirm('Do you want to save the article before navigating away?');

        if (confirmed) {
          faqAutoSave();
        }
      }
    }
  });

  if (typeof window.tinyMCE !== 'undefined' && typeof autoSaveInterval !== 'undefined') {
    setInterval(() => {
      faqAutoSave();
    }, autoSaveInterval * 1000);
  }

  /**
   * Post auto save data via AJAX.
   *
   * @return void
   */
  function faqAutoSave() {
    const activeEditor = window.tinyMCE.activeEditor;
    if (activeEditor.isDirty()) {
      const formData = {};
      const category = document.getElementById('phpmyfaq-categories');
      const languages = document.getElementById('lang');

      formData.revision_id = $('#revisionId').attr('value');
      formData.record_id = $('#recordId').attr('value');
      formData.csrf = $('[name="csrf"]').attr('value');
      formData.openQuestionId = $('#openQuestionId').attr('value');
      formData.question = document.getElementById('question').value;
      formData.answer = activeEditor.getContent();
      formData.keywords = $('#keywords').attr('value');
      formData.rubrik = Array.from(category.options)
        .filter(o => o.selected)
        .map(o => o.value);
      formData.tags = $('#tags').attr('value');
      formData.author = $('#author').attr('value');
      formData.email = $('#email').attr('value');
      formData.lang = languages.options[languages.selectedIndex].value;
      formData.solution_id = $('#solutionId').attr('value');
      formData.active = $('input:checked[name="active"]').attr('value');
      formData.sticky = $('#sticky').attr('value');
      formData.comment = $('#comment').attr('value');
      formData.grouppermission = $('[name="grouppermission"]').attr('value');
      formData.userpermission = $('[name="userpermission"]').attr('value');
      formData.restricted_users = $('[name="restricted_users"]').attr('value');
      formData.dateActualize = $('#dateActualize').attr('value');
      formData.dateKeep = $('#dateKeep').attr('value');
      formData.dateCustomize = $('#dateCustomize').attr('value');
      formData.date = $('#date').attr('value');

      $.ajax({
        url: faqAutoSaveAction(),
        type: 'POST',
        data: formData,
        success: data => {
          const response = $.parseJSON(data);

          $('#pmf-admin-saving-data-indicator').html(response.msg);

          activeEditor.isNotDirty = true;

          $('#recordId').attr('value', response.record_id);
          $('#revisionId').attr('value', response.revision_id);
          // @todo check update more places on the page according to the new saved data
        },
      });
    }
  }

  /**
   * Produce AJAX autosave action.
   *
   * @return string
   */
  function faqAutoSaveAction() {
    let action,
      queryFunction = $('#faqEditor').attr('action');

    action = '?action=ajax&ajax=autosave&' + queryFunction.substr(1).replace(/action=/, 'do=');

    return action;
  }
});
