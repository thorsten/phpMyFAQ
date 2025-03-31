/**
 * phpMyFAQ frontend code
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2019-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-12-20
 */

import Masonry from 'masonry-layout';
import { handleContactForm } from './contact';
import {
  handleAddFaq,
  handleComments,
  handleSaveComment,
  handleShareLinkButton,
  handleShowFaq,
  handleUserVoting,
} from './faq';
import { handleAutoComplete, handleQuestion, handleExpandaSearch } from './search';
import {
  handleDeleteBookmarks,
  handleRegister,
  handleRemoveAllBookmarks,
  handleRequestRemoval,
  handleUserControlPanel,
  handleUserPassword,
} from './user';
import { calculateReadingTime, handlePasswordStrength, handlePasswordToggle, handleReloadCaptcha } from './utils';
import './utils/tooltip';
import { handleWebAuthn } from './webauthn/webauthn';

document.addEventListener('DOMContentLoaded', () => {
  // Reload Captchas
  const reloadButton = document.querySelector('#captcha-button');
  if (reloadButton !== null) {
    handleReloadCaptcha(reloadButton);
  }

  // Password helpers
  handlePasswordToggle();
  handlePasswordStrength();

  // Calculate reading time
  const faqBody = document.querySelector('.pmf-faq-body');
  if (faqBody !== null) {
    calculateReadingTime();
  }

  // Handle votings
  handleUserVoting();

  // Handle comments
  handleSaveComment();
  handleComments();

  // Handle Add a FAQ
  handleAddFaq();

  // Handle show FAQ
  handleShowFaq();
  handleShareLinkButton();

  // Handle Add a Question
  handleQuestion();

  // Handle Bookmarks
  handleDeleteBookmarks();
  handleRemoveAllBookmarks();

  // Handle user control panel
  handleUserControlPanel();

  // Handle user password
  handleUserPassword();

  // Handle request removal
  handleRequestRemoval();

  // Handle the contact form
  handleContactForm();

  // Handle the registration form
  handleRegister();
  handleWebAuthn();

  // Masonry on startpage
  const masonryElement = document.querySelector('.masonry-grid');
  if (masonryElement) {
    new Masonry(masonryElement, { columnWidth: 0 });
  }

  // AutoComplete
  handleAutoComplete();

  // set up expandaSearch
  handleExpandaSearch();
});
