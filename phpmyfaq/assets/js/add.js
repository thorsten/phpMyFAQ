/**
 * Add FAQ functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @package   JavaScript
 * @author Thorsten Rinne
 * @copyright 2017-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2017-08-02
 */

/*global document: false, $: false, tinyMCE: false, saveFormValues: false */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';
  $('#submitfaq').on('click', () => {
    if (typeof tinyMCE !== 'undefined' && undefined !== tinyMCE) {
      tinyMCE.get('answer').setContent(tinyMCE.activeEditor.getContent());
      document.getElementById('answer').value = tinyMCE.activeEditor.getContent();
    }
    saveFormValues('savefaq', 'faq');
  });
  $('form#formValues').submit(function() { return false; });
});
