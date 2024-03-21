/**
 * phpMyFAQ frontend code
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2019-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-12-20
 */

import 'bootstrap';
import Masonry from 'masonry-layout';

import { handleBookmarks } from './api';
import { handleContactForm } from './contact';
import { handleAddFaq, handleComments, handleSaveComment, handleShare, handleUserVoting } from './faq';
import { handleAutoComplete, handleQuestion } from './search';
import { handleRegister, handleRequestRemoval, handleUserControlPanel, handleUserPassword } from './user';
import { calculateReadingTime, handlePasswordStrength, handlePasswordToggle, handleReloadCaptcha } from './utils';
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
// Handle sharing
//
handleShare();

//
// Handle Add a FAQ
//
handleAddFaq();

//
// Handle Add a Question
//
handleQuestion();

//
// Handle Bookmarks
//
handleBookmarks();

//
// Handle user control panel
//
handleUserControlPanel();

//
// Handle user password
//
handleUserPassword();

//
// Handle request removal
//
handleRequestRemoval();

//
// Handle the contact form
//
handleContactForm();

//
// Handle the registration form
//
handleRegister();

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
