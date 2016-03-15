/**
 * Typeahead functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2014-11-23
 */

/*global $: false, Bloodhound: false, Handlebars: false */

$(window).load(function () {
    'use strict';

    // instantiate the bloodhound suggestion engine
    var questions = new Bloodhound({
        datumTokenizer: function (d) {
            return Bloodhound.tokenizers.whitespace(d.value);
        },
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: 'ajaxresponse.php?search=%QUERY',
            wildcard: '%QUERY',
            filter: function (questions) {

                return $.map(questions.results, function (question) {
                    return {
                        category: question.categoryName,
                        question: question.faqQuestion,
                        url: question.faqLink
                    };
                });
            }
        }
    });
    // initialize the bloodhound suggestion engine
    questions.initialize();
    // instantiate the typeahead UI
    $('.typeahead').typeahead(null, {
        display: 'suggestion',
        source: questions.ttAdapter(),
        templates: {
            empty: [
                '<div class="empty-message">',
                'unable to find any Best Picture winners that match the current query',
                '</div>'
            ].join('\n'),
            suggestion: Handlebars.compile(
                '<div><strong>{{category}}</strong>: <a href="{{url}}">{{question}}</a></div>'
            )
        }
    }).on('typeahead:selected typeahead:autocompleted', function () {
        $('#searchfield').submit();
    });
});