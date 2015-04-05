/**
 * JavaScript functions for all FAQ record administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-11-17
 */

/*global $:false */

$(document).ready(function() {
    'use strict';

    $('.showhideCategory').click(function(event) {
        event.preventDefault();
        $('#category_' + $(this).data('category-id')).toggle(1000);
    });

    $('#submitDeleteQuestions').click(function() {
        var questions = $('#questionSelection').serialize();
        $('#returnMessage').empty();
        $.ajax({
            type: 'POST',
            url:  'index.php?action=ajax&ajax=records&ajaxaction=delete_question',
            data: questions,
            success: function(msg) {
                $('#saving_data_indicator').html('<i class="fa fa-spinner fa-spin"></i> Deleting ...');
                $('tr td input:checked').parent().parent().fadeOut('slow');
                $('#saving_data_indicator').fadeOut('slow');
                $('#returnMessage').
                    html('<p class="alert alert-success">' + msg + '</p>');
            }
        });
        return false;
    });

    $(function() {
        // set the textarea to its previous height
        var answerHeight = localStorage.getItem('textarea.answer.height');

        if (answerHeight !== 'undefined') {
            $('#answer').height(answerHeight);
        }

        // when reszied, store the textarea's height
        $('#answer').on('mouseup', function() {
            localStorage.setItem('textarea.answer.height', $(this).height());
        });

        // on clicking the Preview tab, refresh the preview
        $('.markdown-tabs').find('a').on('click', function() {
            if ($(this).attr('data-markdown-tab') === 'preview') {
                $('.markdown-preview')
                    .height($('#answer').height());
                $.post('index.php?action=ajax&ajax=markdown', { text: $('#answer').val() }, function(result) {
                    $('.markdown-preview').html(result);
                });
            }
        });
    });
});