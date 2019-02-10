/**
 * Some JavaScript functions used in the admin backend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Periklis Tsirakidis <tsirakidis@phpdevel.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-11-13
 */

/*global document: false, window: false, $: false, self: false */

var infoBox,
    selectSelectAll,
    selectUnselectAll,
    closeWindow,
    addAttachmentLink,
    saveVoting;

$(document).ready(function () {
    'use strict';

    /**
     * selects all list options in the select with the given ID.
     *
     * @param select_id
     * @return void
     */
    selectSelectAll = function selectSelectAll(select_id) {
        var selectOptions = $('#' + select_id + ' option'),
            i = 0;
        for (i = 0; i < selectOptions.length; i += 1) {
            selectOptions[i].selected = true;
        }
    };

    /**
     * unselects all list options in the select with the given ID.
     *
     * @param select_id
     * @return void
     */
    selectUnselectAll = function selectUnselectAll(select_id) {
        var selectOptions = $('#' + select_id + ' option'),
            i = 0;
        for (i = 0; i < selectOptions.length; i += 1) {
            selectOptions[i].selected = false;
        }
    };

    /**
     * Displays or hides the info boxes
     *
     * @return void
     */
    infoBox = function infoBox(infobox_id) {
        var domId = $('#' + infobox_id);
        if (domId.css('display') === 'none') {
            $('.faqTabContent').hide();
            domId.show();
        } else {
            domId.hide();
        }
    };

    /**
     * Adds the link to the attachment in the main FAQ window
     * @param attachmentId
     * @param fileName
     * @param recordId
     * @param recordLang
     */
    addAttachmentLink = function addAttachmentLink(attachmentId, fileName, recordId, recordLang) {
        window.opener.
            $('.adminAttachments').
            append(
                '<li>' +
                '<a href="../index.php?action=attachment&id=' + attachmentId + '">' + fileName + '</a>' +
                '<a class="label label-danger" href="?action=delatt&amp;record_id=' + recordId +
                '&amp;id=' + attachmentId + '&amp;lang=' + recordLang + '">' +
                '<i aria-hidden="true" class="fa fa-trash"></i></a>' +
                '</li>'
            );
        window.close();
    };

    /**
     * Closes the current window
     *
     */
    closeWindow = function closeWindow() {
        window.close();
    };

    /**
     * Saves all content from the given form via Ajax
     *
     * @param action   Actions: savecomment, savefaq, savequestion,
     *                          saveregistration, savevoting, sendcontact,
     *                          sendtofriends
     * @param formName Name of the current form
     *
     * @return void
     */
    window.saveFormValues = function saveFormValues(action, formName) {
        var formValues = $('#formValues');

        $('#loader').show();
        $('#loader').fadeIn(400).html('<img src="assets/img/ajax-loader.gif">Saving ...');

        $.ajax({
            type:     'post',
            url:      'ajaxservice.php?action=' + action,
            data:     formValues.serialize(),
            dataType: 'json',
            cache:    false,
            success:  function (json) {
                if (json.success === undefined) {
                    $('#' + formName + 's').html(
                        '<p class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        json.error +
                        '</p>'
                    );
                    $('#loader').hide();
                } else {
                    $('#' + formName + 's').html(
                        '<p class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        json.success +
                        '</p>'
                    );
                    $('#' + formName + 's').fadeIn('slow');
                    $('#loader').hide();
                    $('#' + formName + 'Form').hide();
                    $('#formValues')[0].reset();
                    // @todo add reload of content
                }
            }
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
        $.ajax({
            type:     'post',
            url:      'ajaxservice.php?action=savevoting',
            data:     'type=' + type + '&id=' + id + '&vote=' + value + '&lang=' + lang,
            dataType: 'json',
            cache:    false,
            success:  function (json) {
                if (json.success === undefined) {
                    $('#votings').append(
                        '<p class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +                        json.error +
                        '</p>'
                    );
                    $('#loader').hide();
                } else {
                    $('#votings').append(
                        '<p class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +                        json.success +
                        '</p>');
                    $('#rating').empty().append(json.rating);
                    $('#votings').fadeIn('slow');
                    $('#loader').hide();
                }
            }
        });

        return false;
    };

    /**
     * Checks the content of a question by Ajax
     *
     */
    window.checkQuestion = function checkQuestion() {
        var formValues = $('#formValues');

        $('#loader').show();
        $('#loader').fadeIn(400).html('<img src="assets/img/ajax-loader.gif">Saving ...');

        $.ajax({
            type:     'post',
            url:      'ajaxservice.php?action=savequestion',
            data:     formValues.serialize(),
            dataType: 'json',
            cache:    false,
            success:  function (json) {
                if (json.result === undefined && json.success === undefined) {
                    $('#qerror').html(
                        '<p class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +                        json.error +
                        '</p>'
                    );
                    $('#loader').hide();
                } else if (json.success === undefined) {
                    $('#qerror').empty();
                    $('.hint-search-suggestion').show();
                    $('#questionForm').fadeOut('slow');
                    $('#answerForm').html(json.result);
                    $('#answerForm').fadeIn('slow');
                    $('#loader').hide();
                    $('#formValues').append('<input type="hidden" name="save" value="1" />');
                    $('#captcha').val('');
                } else {
                    $('#answers').html(
                        '<p class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        json.success +
                        '</p>'
                    );
                    $('#answers').fadeIn('slow');
                    $('#answerForm').fadeOut('slow');
                    $('#loader').hide();
                    $('#formValues').hide();
                }
            }
        });

        return false;
    };


    $('#captcha-button').click(function() {
        var action = $(this).data('action');
        $.ajax({
            url: 'index.php?action=' + action + '&gen=img&ck=' + new Date().getTime(),
            success: function () {
                var captcha = $('#captcha');
                $('#captchaImage').attr('src', 'index.php?action=' + action + '&gen=img&ck=' + new Date().getTime());
                captcha.val('');
                captcha.focus();
            }
        });
    });
});
