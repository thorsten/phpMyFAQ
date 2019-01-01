/**
 * TinyMCE v4 plugin to fetch and insert internal links via Ajax call
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-10-08
 */

/*global tinymce:false, $:false */

tinymce.PluginManager.add('phpmyfaq', function(editor) {
    'use strict';

    editor.addButton('phpmyfaq', {
        image: 'images/phpmyfaq.gif',
        onclick: function() {
            // Open window
            editor.windowManager.open({
                title: 'Internal links',
                width: 480,
                height: 320,
                body: [
                    { type: 'textbox', name: 'search', label: 'Search', id: 'pmf-internal-links' },
                    { type: 'container', name: 'pmf-faq-list', id: 'pmf-faq-list', minHeight: 240 }
                ],
                onkeyup: function () {
                    var search = $('#pmf-internal-links').val();
                    var url    = location.protocol + '//' + location.host + location.pathname;
                    var args   = top.tinymce.activeEditor.windowManager.getParams();
                    var list   = $('#pmf-faq-list');
                    if (search.length > 0) {
                        $.ajax({
                            type: 'POST',
                            url:  url + 'index.php?action=ajax&ajax=records&ajaxaction=search_records',
                            data: 'search=' + search + '&csrf=' + args.csrf,
                            success: function(searchresults) {
                                list.empty();
                                if (searchresults.length > 0)  {
                                    list.append(searchresults);
                                }
                            }
                        });
                    }
                },
                onsubmit: function () {
                    var selected = $('input:radio[name=faqURL]:checked');
                    var url    = selected.val();
                    var title  = selected.parent().text();
                    var anchor = '<a class="pmf-internal-link" href="' + url + '">' + title + '</a>';
                    editor.insertContent(anchor);
                }
            }, {
                csrf: $('#csrf').val()
            }
            );
        }
    });
});