/**
 * TinyMCE for phpMyFAQ
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-01-31
 */

// TinyMCE global which is used to init the editor
import tinymce from 'tinymce/tinymce';
import 'tinymce/models/dom';

// Theme
import 'tinymce/themes/silver';
// Toolbar icons
import 'tinymce/icons/default';
// Editor styles
import 'tinymce/skins/ui/oxide/skin.min.css';

// importing the plugin js.
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/code';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/media';
import 'tinymce/plugins/nonbreaking';
import 'tinymce/plugins/table';
import 'tinymce/plugins/help';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/pagebreak';
import 'tinymce/plugins/visualchars';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/save';
import 'tinymce/plugins/directionality';
import 'tinymce/plugins/emoticons';
import 'tinymce/plugins/emoticons/js/emojis';

import contentCss from '!!raw-loader!tinymce/skins/content/default/content.min.css';
import contentUiCss from '!!raw-loader!tinymce/skins/ui/oxide/content.min.css';

export const renderEditor = () => {
  const form = document.getElementById('faqEditor');
  if (form) {
    const editorEnabled = form.getAttribute('data-pmf-enable-editor');
    const editorLanguage = form.getAttribute('data-pmf-editor-language');
    const defaultLanguage = form.getAttribute('data-pmf-default-url');

    if ('1' !== editorEnabled) {
      return;
    }

    tinymce.init({
      // General options
      language: editorLanguage,
      document_base_url: defaultLanguage,
      skin: false,
      content_css: false,
      content_style: [contentCss, contentUiCss].join('\n'),
      selector: 'textarea#editor',
      relative_urls: false,
      convert_urls: false,
      remove_linebreaks: false,
      use_native_selects: true,
      paste_remove_spans: true,
      entities: '10',
      entity_encoding: 'raw',
      height: '50vh',
      paste_data_images: true,
      visualblocks_default_state: true,
      end_container_on_empty_block: true,
      extended_valid_elements: 'code[class],video[*],audio[*],source[*]',
      removeformat: [{ selector: '*', attributes: ['style'], split: false, expand: false, deep: true }],
      importcss_append: true,

      // Font handling
      fontsize_formats: '6pt 8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 24pt 36pt 48pt',
      font_formats:
        'Arial=arial,helvetica,sans-serif;' +
        'Arial Black=arial black,avant garde;' +
        'Calibri=calibri;' +
        'Comic Sans MS=comic sans ms,sans-serif;' +
        'Courier New=courier new,courier;' +
        'Georgia=georgia,palatino;' +
        'Helvetica=helvetica;' +
        'Impact=impact,chicago;' +
        'Symbol=symbol;' +
        'Tahoma=tahoma,arial,helvetica,sans-serif;' +
        'Terminal=terminal,monaco;' +
        'Times New Roman=times new roman,times;' +
        'Verdana=verdana,geneva;' +
        'Webdings=webdings;' +
        'Wingdings=wingdings,zapf dingbats',

      // Plugins
      plugins:
        'advlist autolink link image lists charmap preview anchor pagebreak ' +
        'searchreplace wordcount visualblocks visualchars code insertdatetime media nonbreaking ' +
        'save table directionality help emoticons phpmyfaq',

      emoticons_database: 'emojis',

      // Toolbar
      menubar: false,
      toolbar1:
        'newdocument | undo redo | bold italic underline subscript superscript ' +
        'strikethrough | styleselect | blocks | fontfamily | fontsize |' +
        'outdent indent | alignleft aligncenter alignright alignjustify | removeformat |' +
        'insertfile | cut copy codesample | bullist numlist |' +
        'link unlink anchor image media | charmap | insertdatetime | table |' +
        'forecolor backcolor emoticons | searchreplace | ' +
        'pagebreak | code | phpmyfaq | preview',

      // Formatting
      style_formats: [
        {
          title: 'Headers',
          items: [
            { title: 'h1', block: 'h1' },
            { title: 'h2', block: 'h2' },
            { title: 'h3', block: 'h3' },
            { title: 'h4', block: 'h4' },
            { title: 'h5', block: 'h5' },
            { title: 'h6', block: 'h6' },
          ],
        },

        {
          title: 'Blocks',
          items: [
            { title: 'p', block: 'p' },
            { title: 'div', block: 'div' },
            { title: 'pre', block: 'pre' },
            { title: 'code', block: 'code' },
          ],
        },

        {
          title: 'Containers',
          items: [
            { title: 'blockquote', block: 'blockquote', wrapper: true },
            { title: 'figure', block: 'figure', wrapper: true },
          ],
        },
      ],

      // File browser
      file_picker_types: 'image media',
      file_picker_callback: (callback, value, meta) => {
        const type = meta.filetype;
        const w = window,
          d = document,
          e = d.documentElement,
          g = d.getElementsByTagName('body')[0],
          x = w.innerWidth || e.clientWidth || g.clientWidth,
          y = w.innerHeight || e.clientHeight || g.clientHeight;

        let mediaBrowser = 'media.browser.php';
        mediaBrowser += mediaBrowser.indexOf('?') < 0 ? '?type=' + type : '&type=' + type;

        tinymce.activeEditor.windowManager.openUrl({
          url: mediaBrowser,
          title: 'Select media file',
          width: x * 0.8,
          height: y * 0.8,
          resizable: 'yes',
          close_previous: 'no',
          onMessage: function (api, data) {
            if (data.mceAction === 'phpMyFAQMediaBrowserAction') {
              callback(data.url);
              api.close();
            }
          },
        });
      },

      // override default upload handler to simulate successful upload
      images_upload_handler: (blobInfo) =>
        new Promise((resolve, reject) => {
          const csrf = document.getElementById('pmf-csrf-token').value;
          const formData = new FormData();
          formData.append('file', blobInfo.blob(), blobInfo.filename());

          fetch(`index.php?action=ajax&ajax=image&ajaxaction=upload&csrf=${csrf}`, {
            method: 'POST',
            body: formData,
            //credentials: 'omit'
          })
            .then((response) => {
              if (!response.ok) {
                throw new Error('HTTP Error: ' + response.status);
              }
              return response.json();
            })
            .then((json) => {
              if (!json || typeof json.location != 'string') {
                throw new Error('Invalid JSON: ' + JSON.stringify(json));
              }
              resolve(json.location);
            })
            .catch((error) => {
              if (error instanceof TypeError) {
                reject('Image upload failed due to a Fetch error: ' + error.message);
              } else {
                reject(error.message);
              }
            });
        }),

      // Custom params
      csrf: document.getElementById('pmf-csrf-token').value,

      // Image handling
      image_advtab: true,
      image_class_list: [
        { title: 'None', value: '' },
        { title: 'Responsive', value: 'img-fluid' },
      ],
      image_dimensions: true,
      images_upload_url: 'index.php?action=ajax&ajax=image&ajaxaction=upload',
    });
  }
};
