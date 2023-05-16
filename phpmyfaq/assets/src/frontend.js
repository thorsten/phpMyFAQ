/**
 * phpMyFAQ frontend code
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2019-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-12-20
 */

import 'bootstrap';
import Masonry from 'masonry-layout';

import { saveFormData } from './api';
import { handleComments, handleSaveComment, handleUserVoting } from './faq';
import { handleAutoComplete, handleQuestion } from './search';
import { calculateReadingTime, handlePasswordStrength, handlePasswordToggle, handleReloadCaptcha } from './utils';
import './utils/cookie-consent';
import './utils/tooltip';

//
// Reload Captchas
//
const reloadButton = document.querySelector('#captcha-button');
if (reloadButton !== null) {
  handleReloadCaptcha(reloadButton);
}

//
// Password helpers
//
handlePasswordToggle();
handlePasswordStrength();

//
// Calculate reading time
//
const faqBody = document.querySelector('.pmf-faq-body');
if (faqBody !== null) {
  calculateReadingTime();
}

//
// Handle votings
//
handleUserVoting();

//
// Handle comments
//
handleSaveComment();
handleComments();

//
// Masonry on startpage
//
window.onload = () => {
  handleAutoComplete();
  const masonryElement = document.querySelector('.masonry-grid');
  if (masonryElement) {
    new Masonry(masonryElement, { columnWidth: 0 });
  }
};

//
// Forms
//
const formHandler = document.getElementById('pmf-submit-values');
if (formHandler) {
  formHandler.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    const form = document.querySelector('.needs-validation');
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
    } else {
      const action = formHandler.getAttribute('data-pmf-form');
      if (action === 'ask-question') {
        // Ask questions
        handleQuestion();
      } else {
        // Everything else
        saveFormData(formHandler.getAttribute('data-pmf-form'));
        document.getElementById('formValues').reset();
      }
    }
  });
}
