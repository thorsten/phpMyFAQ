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
 * @copyright 2014-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-11-23
 */

/*global $: false, Bloodhound: false, Handlebars: false */

$(window).load(function () {
    'use strict';

    // instantiate the bloodhound suggestion engine
    var questions = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.whitespace,
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        limit: Number.MAX_VALUE,
        remote: {
            url: 'ajaxresponse.php?search=%QUERY',
            wildcard: '%QUERY',
            transform: function (questions) {
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
    // instantiate the typeahead UI
    $('.typeahead').typeahead(null, {
        display: 'suggestion',
        source: questions.ttAdapter(),
        templates: {
            notFound: [
                '<div class="empty-message">',
                'Nothing found... :-(',
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