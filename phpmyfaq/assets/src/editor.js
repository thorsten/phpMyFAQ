/**
 * TinyMCE code to add FAQ functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne
 * @copyright 2017-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2017-08-02
 */

/*global document: false, $: false, tinyMCE: false */

$(document).ready(function () {
  'use strict';
  if (typeof tinyMCE !== 'undefined' && undefined !== tinyMCE) {
    tinyMCE.init({
      // General options
      mode: 'exact',
      language: 'en',
      selector: 'textarea#answer',
      theme: 'modern',
      plugins: [
        'advlist anchor autolink lists link image imagetools charmap print preview hr anchor pagebreak',
        'searchreplace wordcount visualblocks visualchars code codesample fullscreen colorpicker help',
        'insertdatetime media nonbreaking save table contextmenu directionality textpattern',
        'emoticons template paste textcolor toc',
      ],
      relative_urls: false,
      convert_urls: false,
      remove_linebreaks: false,
      use_native_selects: true,
      paste_remove_spans: true,
      entities: '10',
      entity_encoding: 'raw',
      toolbar1:
        'formatselect | styleselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | removeformat',
      toolbar2: 'insertfile | paste codesample | link image preview media | forecolor backcolor emoticons | phpmyfaq',
      image_advtab: true,
      image_class_list: [
        { title: 'None', value: '' },
        { title: 'Responsive', value: 'img-fluid' },
      ],
      image_dimensions: true,

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

      paste_word_valid_elements: 'b,strong,i,em,h1,h2,h3,h4,h5,h6',
      paste_data_images: true,
      visualblocks_default_state: true,
      end_container_on_empty_block: true,
      extended_valid_elements: 'code[class],video[*],audio[*],source[*],iframe[*]',
      removeformat: [{ selector: '*', attributes: ['style'], split: false, expand: false, deep: true }],
      importcss_append: true,
    });
  }
});
