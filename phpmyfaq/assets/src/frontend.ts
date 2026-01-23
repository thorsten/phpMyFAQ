/**
 * phpMyFAQ frontend code
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2019-2026 phpMyFAQ Team
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
  renderFaqEditor,
} from './faq';
import { handleAutoComplete, handleCategorySelection, handleQuestion } from './search';
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
import { handleChat } from './chat';

document.addEventListener('DOMContentLoaded', (): void => {
  // Reload Captchas
  const reloadButton: HTMLButtonElement | null = document.querySelector('#captcha-button');
  if (reloadButton !== null) {
    handleReloadCaptcha(reloadButton);
  }

  // Password helpers
  handlePasswordToggle();
  handlePasswordStrength();

  // Calculate reading time
  const faqBody: HTMLElement | null = document.querySelector('.pmf-faq-body');
  if (faqBody !== null) {
    calculateReadingTime();
  }

  // Handle votings
  handleUserVoting();

  // Handle comments
  handleSaveComment();
  handleComments();

  // Handle Adds a FAQ
  handleAddFaq();

  // Initialize Jodit editor for FAQ add form if WYSIWYG is enabled
  const addFaqForm: HTMLFormElement | null = document.querySelector('#pmf-add-faq-form');
  if (addFaqForm && addFaqForm.dataset.wysiwygEnabled === 'true') {
    renderFaqEditor();
  }

  // Handle show FAQ
  handleShowFaq();
  handleShareLinkButton();

  // Handle Adds a Question
  handleQuestion();

  // Handle Bookmarks
  handleDeleteBookmarks();
  handleRemoveAllBookmarks();

  // Handle the user control panel
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

  // Masonry on the start page
  const masonryElement: HTMLElement | null = document.querySelector('.masonry-grid');
  if (masonryElement) {
    new Masonry(masonryElement, { columnWidth: 0 });
  }

  // AutoComplete
  handleAutoComplete();
  handleCategorySelection();

  // Handle Chat
  handleChat();
});
