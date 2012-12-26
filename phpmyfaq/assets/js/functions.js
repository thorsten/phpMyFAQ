/**
 * Some JavaScript functions used in the admin backend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Periklis Tsirakidis <tsirakidis@phpdevel.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-11-13
 */

/*global clearInterval: false, clearTimeout: false, document: false, event: false, frames: false, history: false, Image: false, location: false, name: false, navigator: false, Option: false, parent: false, screen: false, setInterval: false, setTimeout: false, window: false, XMLHttpRequest: false */

var toggleConfig,
    toggleFieldset,
    showhideCategory,
    addAttachment,
    addEngine,
    infoBox,
    selectSelectAll,
    selectUnselectAll,
    formCheckAll,
    formUncheckAll,
    checkAll,
    closeWindow,
    addAttachmentLink,
    refreshCaptcha,
    showLongComment,
    saveFormValues,
    autoSuggest,
    saveVoting,
    checkQuestion;

$(document).ready(function () {
    "use strict";
    /**
     * Open a small popup to upload an attachment
     * 
     * @param pic
     * @param title
     */
    addAttachment = function addAttachment(pic, title) {
        var popup = window.open(
            pic,
            title,
            "width=550, height=130, toolbar=no, directories=no, status=no, scrollbars=no, resizable=yes, menubar=no"
        );
        popup.focus();
    };

    /**
     * Checks all checkboxes
     * 
     * @param checkBox
     */
    checkAll = function checkAll(checkBox) {
        var v = checkBox.checked,
            f = checkBox.form,
            i = 0;
        for (i = 0; i < f.elements.length; i += 1) {
            if (f.elements[i].type === "checkbox") {
                f.elements[i].checked = v;
            }
        }
    };

    /**
     *
     * @param uri
     * @param name
     * @param ext
     * @param cat
     */
    addEngine = function addEngine(uri, name, ext, cat) {
        if ((typeof window.sidebar === "object") && (typeof window.sidebar.addSearchEngine === "function")) {
            window.sidebar.addSearchEngine(uri + "/" + name + ".src", uri + "/images/" + name + "." + ext, name, cat);
        } else {
            window.alert("Mozilla Firefox, Mozilla or Netscape 6 or later is needed to install the search plugin!");
        }
    };

    /**
     * Displays or hides a div block
     *
     * @param id Id of the block
     * @return void
     */
    showhideCategory = function showhideCategory(id) {
        var domId = $("#" + id);
        if (domId.css("display") === "none") {
            domId.fadeIn("slow");
        } else {
            domId.fadeOut("slow");
        }
    };

    /**
     * Displays or hides a configuration block
     *
     * @param container
     * @return void
     */
    toggleConfig = function toggleConfig(container) {
        var configContainer = $("#config" + container);
        if (configContainer.css("display") === "none") {
            $.get("index.php", {
                action: "ajax",
                ajax: "config_list",
                conf: container.toLowerCase()
            }, function (data) {
                configContainer.append(data);
            });
            configContainer.fadeIn("slow");
        } else {
            configContainer.fadeOut("slow");
        }
    };

    /**
     * selects all list options in the select with the given ID.
     *
     * @param select_id
     * @return void
     */
    selectSelectAll = function selectSelectAll(select_id) {
        var selectOptions = $("#" + select_id + " option"),
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
        var selectOptions = $("#" + select_id + " option"),
            i = 0;
        for (i = 0; i < selectOptions.length; i += 1) {
            selectOptions[i].selected = false;
        }
    };

    /**
     * checks all checkboxes in form with the given ID.
     *
     * @param   form_id
     * @return  void
     */
    formCheckAll = function formCheckAll(form_id) {
        var inputElements = $("#" + form_id + " input"),
            i,
            ele;
        for (i = 0; ele = inputElements[i]; i += 1) {
            if (ele.type === "checkbox") {
                ele.checked = true;
            }
        }
    };

    /**
     * unchecks all checkboxes in form with the given ID.
     *
     * @param   form_id
     * @return  void
     */
    formUncheckAll = function formUncheckAll(form_id) {
        var inputElements = $("#" + form_id + ' input'),
            i,
            ele;
        for (i = 0; ele = inputElements[i]; i += 1) {
            if (ele.type === "checkbox") {
                ele.checked = false;
            }
        }
    };

    /**
     * Displays or hides the info boxes
     *
     * @return void
     */
    infoBox = function infoBox(infobox_id) {
        var domId = $("#" + infobox_id);
        if (domId.css("display") === "none") {
            $(".faqTabContent").hide();
            domId.show();
        } else {
            domId.hide();
        }
    };

    /**
     * Refreshes a captcha image
     *
     * @param action
     */
    refreshCaptcha = function refreshCaptcha(action) {
        $.ajax({
            url: 'index.php?action=' + action + '&gen=img&ck=' + new Date().getTime(),
            success: function () {
                var captcha = $("#captcha");
                $("#captchaImage").attr('src', 'index.php?action=' + action + '&gen=img&ck=' + new Date().getTime());
                captcha.val('');
                captcha.focus();
            }
        });
    };

    /**
     * Toggle fieldsets
     *
     * @param fieldset ID of the fieldset
     *
     * @return void
     */
    toggleFieldset = function toggleFieldset(fieldset) {
        var div = $('#div_' + fieldset);
        if (div.css('display') === 'none') {
            div.fadeIn('fast');
        } else {
            div.fadeOut('fast');
        }
    };

    /**
     * Adds the link to the attachment in the main FAQ window
     * @param attachmentId
     * @param fileName
     */
    addAttachmentLink = function addAttachmentLink(attachmentId, fileName) {
        window.opener.
            $('.adminAttachments').
            append('<li><a href="../index.php?action=attachment&id=' + attachmentId + '">' + fileName + '</a></li>');
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
     * Show long comment
     */
    showLongComment = function showLongComment(id) {
        $('.comment-more-' + id).removeClass('hide');
        $('.comment-dots-' + id).addClass('hide');
        $('.comment-show-more-' + id).addClass('hide');
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
    saveFormValues = function saveFormValues(action, formName) {
        var formValues = $('#formValues');

        $('#loader').show();
        $('#loader').fadeIn(400).html('<img src="assets/img/ajax-loader.gif" />Saving ...');

        $.ajax({
            type:     'post',
            url:      'ajaxservice.php?action=' + action,
            data:     formValues.serialize(),
            dataType: 'json',
            cache:    false,
            success:  function (json) {
                if (json.success === undefined) {
                    $("#" + formName + 's').html('<p class="alert alert-error">' + json.error + '</p>');
                    $('#loader').hide();
                } else {
                    $("#" + formName + 's').html('<p class="alert alert-success">' + json.success + '</p>');
                    $("#" + formName + 's').fadeIn("slow");
                    $('#loader').hide();
                    $("#" + formName + 'Form').hide();
                    $('#formValues')[0].reset();
                    // @todo add reload of content
                }
            }
        });

        return false;
    };

    /**
     * Auto-suggest function for instant response
     *
     * @return void
     */
    autoSuggest = function autoSuggest() {
        $('input#instantfield').keyup(function () {
            var search   = $('#instantfield').val(),
                language = $('#ajaxlanguage').val(),
                category = $('#searchcategory').val();

            if (search.length > 0) {
                $.ajax({
                    type:    "POST",
                    url:     "ajaxresponse.php",
                    data:    "search=" + search + "&ajaxlanguage=" + language + "&searchcategory=" + category,
                    success: function (searchresults) {
                        $("#instantresponse").empty();
                        if (searchresults.length > 0) {
                            $("#instantresponse").append(searchresults);
                        }
                    }
                });
            }
        });

        $('#instantform').submit(function () {
            return false;
        });
    };

    /**
     * Saves the voting by Ajax
     *
     * @param type
     * @param id
     * @param value
     */
    saveVoting = function saveVoting(type, id, value) {
        $.ajax({
            type:     'post',
            url:      'ajaxservice.php?action=savevoting',
            data:     'type=' + type + '&id=' + id + '&vote=' + value,
            dataType: 'json',
            cache:    false,
            success:  function (json) {
                if (json.success === undefined) {
                    $('#votings').html('<p class="alert alert-error">' + json.error + '</p>');
                    $('#loader').hide();
                } else {
                    $('#votings').html('<p class="alert alert-success">' + json.success + '</p>');
                    $('#rating').html(json.rating);
                    $('#votings').fadeIn("slow");
                    $('#loader').hide();
                    $('#votingForm').hide();
                }
            }
        });

        return false;
    };

    /**
     * Checks the content of a question by Ajax
     *
     */
    checkQuestion = function checkQuestion() {
        var formValues = $('#formValues');

        $('#loader').show();
        $('#loader').fadeIn(400).html('<img src="assets/img/ajax-loader.gif" />Saving ...');

        $.ajax({
            type:     'post',
            url:      'ajaxservice.php?action=savequestion',
            data:     formValues.serialize(),
            dataType: 'json',
            cache:    false,
            success:  function (json) {
                if (json.result === undefined && json.success === undefined) {
                    $('#qerror').html('<p class="alert alert-error">' + json.error + '</p>');
                    $('#loader').hide();
                } else if (json.success === undefined) {
                    $('#qerror').empty();
                    $('#questionForm').fadeOut('slow');
                    $('#answerForm').html(json.result);
                    $('#answerForm').fadeIn("slow");
                    $('#loader').hide();
                    $('#formValues').append('<input type="hidden" name="save" value="1" />');
                    $('#captcha').val('');
                    refreshCaptcha('ask');
                } else {
                    $('#answers').html('<p class="alert alert-success">' + json.success + '</p>');
                    $('#answers').fadeIn("slow");
                    $('#answerForm').fadeOut('slow');
                    $('#loader').hide();
                    $('#formValues').hide();
                }
            }
        });

        return false;
    };

});