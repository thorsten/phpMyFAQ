/**
 * TinyMCE code to add FAQ functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Thorsten Rinne
 * @copyright 2017 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2017-08-02
 */

/*global document: false, $: false, tinyMCE: false */

$(document).ready(function() {
  'use strict';
  if (typeof tinyMCE !== 'undefined' && undefined !== tinyMCE) {
    tinyMCE.init({
      // General options
      mode     : 'exact',
      language : 'en',
      elements : 'answer',
      theme    : 'modern',
      plugins: [
        'advlist autolink lists link image charmap print preview hr anchor pagebreak',
        'searchreplace wordcount visualblocks visualchars code fullscreen',
        'insertdatetime media nonbreaking save table contextmenu directionality',
        'emoticons template paste textcolor'
      ],
      relative_urls: false,
      convert_urls: false,
      remove_linebreaks: false,
      use_native_selects: true,
      paste_remove_spans: true,
      entities : '10',
      entity_encoding: 'raw',

      toolbar1: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent",
      toolbar2: "link | forecolor backcolor emoticons | print",
      image_advtab: true,

      // Formatting
      style_formats: [
        {  title: 'Headers', items: [
          {  title: 'h1', block: 'h1'  },
          {  title: 'h2', block: 'h2'  },
          {  title: 'h3', block: 'h3'  },
          {  title: 'h4', block: 'h4'  },
          {  title: 'h5', block: 'h5'  },
          {  title: 'h6', block: 'h6'  }
        ] },

        {  title: 'Blocks', items: [
          {  title: 'p', block: 'p'  },
          {  title: 'div', block: 'div'  },
          {  title: 'pre', block: 'pre'  },
          {  title: 'code', block: 'code'  }
        ] },

        {  title: 'Containers', items: [
          {  title: 'blockquote', block: 'blockquote', wrapper: true  },
          {  title: 'figure', block: 'figure', wrapper: true  }
        ] }
      ],

      visualblocks_default_state: true,
      end_container_on_empty_block: true,
      extended_valid_elements : "code[class],video[*],audio[*],source[*]",
      removeformat : [
        {  selector : '*', attributes : ['style'], split : false, expand : false, deep : true  }
      ],
      importcss_append: true,
    });
  }
});