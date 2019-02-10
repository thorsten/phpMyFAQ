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
 * @copyright 2013-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-11-17
 */

/*global $:false, Bloodhound: false, Handlebars: false */

$(document).ready(function () {
    'use strict';

    var addAttachmentButton = $('.pmf-add-attachment'),
        addAttachment = function (pic, title) {
        var popup = window.open(
            pic,
            title,
            'width=550, height=130, titlebar=no, toolbar=no, directories=no, status=no, scrollbars=no, resizable=yes, menubar=no'
        );
        popup.focus();
    };

    addAttachmentButton.on('click', function () {
        var faqId = $(this).data('faq-id'),
            faqLanguage = $(this).data('faq-language');

        addAttachment(
            'attachment.php?record_id=' + faqId + '&record_lang=' + faqLanguage,
            'Attachment'
        );
    });

    $('#submitDeleteQuestions').on('click', function () {
        var questions = $('#questionSelection').serialize(),
            indicator = $('#saving_data_indicator');

        $('#returnMessage').empty();
        $.ajax({
            type: 'POST',
            url: 'index.php?action=ajax&ajax=records&ajaxaction=delete_question',
            data: questions,
            success: function (msg) {
                indicator.html('<i aria-hidden="true" class="fa fa-spinner fa-spin"></i> Deleting ...');
                $('tr td input:checked').parent().parent().parent().fadeOut('slow');
                indicator.fadeOut('slow');
                $('#returnMessage').
                    html('<p class="alert alert-success">' + msg + '</p>');
            }
        });
        return false;
    });

    $(function () {
        // set the textarea to its previous height
        var answerHeight = localStorage.getItem('textarea.answer.height'),
            answer = $('#answer');

        if (answerHeight !== 'undefined') {
            answer.height(answerHeight);
        }

        // when reszied, store the textarea's height
        answer.on('mouseup', function () {
            localStorage.setItem('textarea.answer.height', $(this).height());
        });

        // on clicking the Preview tab, refresh the preview
        $('.markdown-tabs').find('a').on('click', function () {
            if ($(this).attr('data-markdown-tab') === 'preview') {
                $('.markdown-preview')
                    .height(answer.height());
                $.post('index.php?action=ajax&ajax=markdown', { text: answer.val() }, function (result) {
                    $('.markdown-preview').html(result);
                });
            }
        });
    });

    // Instantiate the bloodhound suggestion engine
    var tags = new Bloodhound({
        datumTokenizer: function (d) {
            return Bloodhound.tokenizers.whitespace(d.value);
        },
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: 'index.php?action=ajax&ajax=tags&ajaxaction=list&q=%QUERY',
            wildcard: '%QUERY',
            filter: function (tags) {
                return $.map(tags.results, function (tags) {
                    return {
                        tagName: tags.tagName
                    };
                });
            }
        }
    });

    // Initialize the bBloodhound suggestion engine
    tags.initialize();

    // Instantiate the Typeahead UI
    $('.pmf-tags-autocomplete').typeahead(null, {
        source: tags.ttAdapter(),
        displayKey: 'tags',
        name: 'tags',
        minLength: 1,
        templates: {
            empty: [
                '<div class="empty-message">',
                'unable to find any Best Picture winners that match the current query',
                '</div>'
            ].join('\n'),
            suggestion: Handlebars.compile('<div data-tagName="{{tagName}}">{{tagName}}</div>')
        }
    }).on('typeahead:selected typeahead:autocompleted', function (event, tag) {
        var tags = $('#tags'),
            currentTags = tags.data('tagList');

        if (typeof currentTags === 'undefined') {
            currentTags = tag.tagName;
        } else {
            currentTags = currentTags + ', ' + tag.tagName;
        }

        tags.data('tagList', currentTags);
        $('.pmf-tags-autocomplete').typeahead('val', currentTags);
    });
});
