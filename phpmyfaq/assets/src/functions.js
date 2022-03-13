/**
 * Some JavaScript functions used in the admin backend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Periklis Tsirakidis <tsirakidis@phpdevel.de>
 * @author Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author Minoru TODA <todam@netjapan.co.jp>
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2003-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-11-13
 */

/*global document: false, window: false, $: false */

let selectSelectAll, selectUnselectAll, saveVoting;

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  /**
   * selects all list options in the select with the given ID.
   *
   * @param select_id
   * @return void
   */
  selectSelectAll = function selectSelectAll(select_id) {
    const selectOptions = $('#' + select_id + ' option');
    for (let i = 0; i < selectOptions.length; i += 1) {
      selectOptions[i].selected = true;
    }
  };

  /**
   * deselects all list options in the select with the given ID.
   *
   * @param select_id
   * @return void
   */
  selectUnselectAll = function selectUnselectAll(select_id) {
    const selectOptions = $('#' + select_id + ' option');
    for (let i = 0; i < selectOptions.length; i += 1) {
      selectOptions[i].selected = false;
    }
  };

  /**
   * Saves all content from the given form via Ajax
   *
   * @param action   Actions: savecomment, savefaq, savequestion,
   *                          saveregistration, savevoting, sendcontact,
   *                          sendtofriends
   * @param formName Name of the current form
   *
   * @return boolean
   */
  window.saveFormValues = function saveFormValues(action, formName) {
    const formValues = $('#formValues');
    const loader = $('#loader');
    const formNameId = $('#' + formName + 's');

    loader.show().fadeIn(400).html('<img src="assets/img/ajax-loader.gif">Saving ...');

    $.ajax({
      type: 'post',
      url: 'ajaxservice.php?action=' + action,
      data: formValues.serialize(),
      dataType: 'json',
      cache: false,
      success: function (json) {
        if (json.success === undefined) {
          formNameId.html(
            '<p class="alert alert-danger">' +
              '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
              json.error +
              '</p>'
          );
          loader.hide();
        } else {
          formNameId.html(
            '<p class="alert alert-success">' +
              '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
              json.success +
              '</p>'
          );
          formNameId.fadeIn('slow');
          loader.hide();
          $('#' + formName + 'Form').hide();
          formValues[0].reset();
          // @todo add reload of content
        }
      },
    });

    return false;
  };

  /**
   * Saves the voting by Ajax
   *
   * @param type
   * @param id
   * @param value
   * @param lang
   */
  saveVoting = function saveVoting(type, id, value, lang) {
    const votings = $('#votings');
    const loader = $('#loader');
    $.ajax({
      type: 'post',
      url: 'ajaxservice.php?action=savevoting',
      data: 'type=' + type + '&id=' + id + '&vote=' + value + '&lang=' + lang,
      dataType: 'json',
      cache: false,
      success: function (json) {
        if (json.success === undefined) {
          votings.append(
            '<p class="alert alert-danger">' +
              '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
              json.error +
              '</p>'
          );
          loader.hide();
        } else {
          votings.append(
            '<p class="alert alert-success">' +
              '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
              json.success +
              '</p>'
          );
          $('#rating').empty().append(json.rating);
          votings.fadeIn('slow');
          loader.hide();
        }
      },
    });

    return false;
  };

  /**
   * Checks the content of a question by Ajax
   *
   */
  console.info('Needs to be rewritten without jQuery.');

  /*
  window.checkQuestion = function checkQuestion() {
    const formValues = $('#formValues');
    const loader = $('#loader');
    const answerForm = $('#answerForm');
    const answers = $('#answers');
    const hintSuggestions = $('.hint-search-suggestion');

    loader.show();
    loader.fadeIn(400).html('<img src="assets/img/ajax-loader.gif">Saving ...');

    $.ajax({
      type: 'post',
      url: 'ajaxservice.php?action=savequestion',
      data: formValues.serialize(),
      dataType: 'json',
      cache: false,
      success: function (json) {
        if (json.result === undefined && json.success === undefined) {
          $('#qerror').html(
            '<p class="alert alert-danger">' +
              '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
              json.error +
              '</p>'
          );
          loader.hide();
        } else if (json.success === undefined) {
          $('#qerror').empty();
          hintSuggestions.removeClass('d-none');
          $('#questionForm').fadeOut('slow');
          answerForm.html(json.result);
          answerForm.fadeIn('slow');
          loader.hide();
          formValues.append('<input type="hidden" name="save" value="1">');
          $('#captcha').val('');
        } else {
          answers.html(
            '<p class="alert alert-success">' +
              '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
              json.success +
              '</p>'
          );
          answers.fadeIn('slow');
          answerForm.fadeOut('slow');
          hintSuggestions.fadeOut('slow');
          loader.hide();
          formValues.hide();
        }
      },
    });

    return false;
  };

  $('#captcha-button').on('click', function () {
    const action = $(this).data('action');
    $.ajax({
      url: 'index.php?action=' + action + '&gen=img&ck=' + new Date().getTime(),
      success: function () {
        const captcha = $('#captcha');
        $('#captchaImage').attr('src', 'index.php?action=' + action + '&gen=img&ck=' + new Date().getTime());
        captcha.val('');
        captcha.focus();
      },
    });
  });

  */
});
