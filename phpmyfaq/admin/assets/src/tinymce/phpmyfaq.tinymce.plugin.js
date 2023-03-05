/**
 * TinyMCE v6 plugin to fetch and insert internal links via Ajax call
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-10-08
 */

tinymce.PluginManager.add('phpmyfaq', function (editor, url) {
  'use strict';

  const openDialog = function (csrfToken) {
    return editor.windowManager.open({
      title: 'phpMyFAQ TinyMCE plugin',
      body: {
        type: 'panel',
        items: [
          { type: 'input', name: 'search', label: 'Search', id: 'pmf-internal-links' },
          { type: 'htmlpanel', html: '<div id="pmf-faq-list" class="p-1"></div>' },
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
          fetch(url + '?action=ajax&ajax=records&ajaxaction=search_records', {
            method: 'POST',
            headers: {
              Accept: 'application/json, text/plain, */*',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              search: data.search,
              csrf: csrfToken,
            }),
          })
            .then(async (response) => {
              if (response.ok) {
                return response.json();
              }
              throw new Error('Network response was not ok: ', { cause: { response } });
            })
            .then((response) => {
              if (response.success) {
                list.innerHTML = '';
                const searchResults = response.success;
                if (searchResults.length > 0) {
                  searchResults.forEach((result) => {
                    list.innerHTML += `<label><input type="radio" name="faqURL" value="${result.url}">${result.question}</label><br>`;
                  });
                }
              } else {
                console.error(response.error);
              }
            })
            .catch(async (error) => {
              const errorMessage = await error.cause.response.json();
              console.error(errorMessage);
            });
        }
      },
      onSubmit: (api) => {
        const selected = document.querySelector('input[name=faqURL]:checked');
        const url = selected.value;
        const title = selected.parentNode.textContent;
        const anchor = `<a class="pmf-internal-link" href="${url}">${title}</a>`;

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
      const csrfToken = document.getElementById('pmf-csrf-token').value;
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
