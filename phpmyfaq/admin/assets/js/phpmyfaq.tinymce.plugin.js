/**
 * TinyMCE v5 plugin to fetch and insert internal links via Ajax call
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2023 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-10-08
 */

/*global tinymce:false, $:false */

tinymce.PluginManager.add('phpmyfaq', function (editor, url) {
  'use strict';

  const openDialog = function (csrfToken) {
    return editor.windowManager.open({
      title: 'phpMyFAQ TinyMCE plugin',
      body: {
        type: 'panel',
        items: [
          { type: 'input', name: 'search', label: 'Search', id: 'pmf-internal-links' },
          { type: 'htmlpanel', html: '<div id="pmf-faq-list"></div>' },
        ],
      },
      buttons: [
        {
          type: 'cancel',
          text: 'Close',
        },
        {
          type: 'submit',
          text: 'Save',
          primary: true,
        },
      ],
      onChange: (api) => {
        const data = api.getData();
        const url = location.protocol + '//' + location.host + location.pathname;
        const list = document.getElementById('pmf-faq-list');

        if (data.search.length > 0) {
          $.ajax({
            type: 'POST',
            url: url + '?action=ajax&ajax=records&ajaxaction=search_records',
            data: 'search=' + data.search + '&csrf=' + csrfToken,
            success: (searchResults) => {
              list.empty();
              if (searchResults.length > 0) {
                list.append(searchResults);
              }
            },
          });
        }
      },
      onSubmit: (api) => {
        const selected = $('input:radio[name=faqURL]:checked');
        const url = selected.val();
        const title = selected.parent().text();
        const anchor = '<a class="pmf-internal-link" href="' + url + '">' + title + '</a>';
        editor.insertContent(anchor);
        api.close();
      },
    });
  };

  // Add button to editor
  editor.ui.registry.addButton('phpmyfaq', {
    text: 'phpMyFAQ',
    //image: 'images/phpmyfaq.gif',
    onAction: () => {
      const csrfToken = document.getElementById('csrf').value;
      openDialog(csrfToken);
    },
  });

  return {
    getMetadata: function () {
      return {
        name: 'phpMyFAQ TinyMCE plugin',
        url: 'https://www.phpmyfaq.de/documentation',
      };
    },
  };
});
