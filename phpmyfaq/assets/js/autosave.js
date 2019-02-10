/**
 * Autosave functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-07-07
 */

/*global document: false, window: false, $: false */

$(document).ready(function () {
    "use script";
    $(window).unload(function () {
        if (typeof window.tinyMCE !== "undefined" && window.tinyMCE.activeEditor !== null) {
            if (window.tinyMCE.activeEditor.isDirty()) {
                var chk = window.confirm('Do you want to save the article before navigating away?');

                if (chk) {
                    pmfAutosave();
                }
            }
        }
    });

    if (typeof window.tinyMCE !== "undefined" && typeof pmfAutosaveInterval !== "undefined") {
        setInterval(function () {
            "use strict";
            pmfAutosave();
        },
        window.pmfAutosaveInterval * 1000);
    }

    /**
     * Post autosave data via AJAX.
     *
     * @return void
     */
    function pmfAutosave() {
        var ed = window.tinyMCE.activeEditor;
        if (ed.isDirty()) {
            var formData = {};
            formData.revision_id = $('#revision_id').attr('value');
            formData.record_id = $('#record_id').attr('value');
            formData.csrf = $('[name="csrf"]').attr('value');
            formData.openQuestionId = $('#openQuestionId').attr('value');
            formData.question = $('#question').attr('value');
            formData.answer = ed.getContent();
            formData.keywords = $('#keywords').attr('value');
            formData.tags = $('#tags').attr('value');
            formData.author = $('#author').attr('value');
            formData.email = $('#email').attr('value');
            formData.lang = $('#lang').attr('value');
            formData.solution_id = $('#solution_id').attr('value');
            formData.active = $('input:checked:[name="active"]').attr('value');
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
                url: pmfAutosaveAction(),
                type: 'POST',
                data: formData,
                success: function (r) {
                    var resp = $.parseJSON(r);

                    $('#saving_data_indicator').html(resp.msg);

                    ed.isNotDirty = true;

                    $('#record_id').attr('value', resp.record_id);
                    $('#revision_id').attr('value', resp.revision_id);
                    /* XXX update more places on the page according to the new saved data */
                }
            });
        }
    }

    /**
     * Produce AJAX autosave action.
     *
     * @return string
     */
    function pmfAutosaveAction() {
        var act,
            fa = $("#faqEditor").attr("action");

        act = "?action=ajax&ajax=autosave&" + fa.substr(1).replace(/action=/, "do=");

        return act;
    }
});