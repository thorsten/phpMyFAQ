/**
 * phpMyFAQ session notification
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-06-16
 */
import { Modal } from 'bootstrap';

export const handleSessionTimeout = () => {
  const showWarning = top.document.getElementById('pmf-show-session-warning');
  const config = { attributes: true };
  if (showWarning) {
    const observer = new MutationObserver(onAttributeChange);
    observer.observe(showWarning, config);
    reloadCurrentPage();
  }
};

const onAttributeChange = (mutationsList) => {
  for (let mutation of mutationsList) {
    if (mutation.type === 'attributes' && mutation.attributeName === 'data-value') {
      const value = mutation.target.getAttribute('data-value');
      toggleSessionWarnungModal(value);
    }
  }
};
const toggleSessionWarnungModal = (toggle) => {
  const sessionWarnungModal = new Modal(top.document.getElementById('sessionWarningModal'));
  if (toggle === 'show') {
    sessionWarnungModal.show();
  } else {
    sessionWarnungModal.hide();
  }
};

const reloadCurrentPage = () => {
  const reloadButton = document.getElementById('pmf-button-reload-page');
  if (reloadButton) {
    reloadButton.addEventListener('click', () => {
      location.reload();
    });
  }
};
