/**
 * Configuration related code, needs to be loaded before as we fetch the
 * configuration data from the server:
 *
 * - Code to generate the API Token
 * - Code to send test mail to the admin
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-02-19
 */

/* global window, document, fetch, performance */

/**
 * Generates a UUID Version 4 compatible universally unique identifier.
 * @returns {string} The generated UUID.
 */
const generateUUID = () => {
  let date = new Date().getTime();

  if (window.performance && typeof window.performance.now === 'function') {
    date += performance.now();
  }

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
    const random = ((date + Math.random() * 16) % 16) | 0;
    date = Math.floor(date / 16);
    return (char === 'x' ? random : (random & 0x3) | 0x8).toString(16);
  });
};

/**
 * Sends a test email to the admin.
 */
window.handleSendTestMail = async () => {
  const button = document.getElementById('btn-phpmyfaq-mail-sendTestEmail');
  if (button) {
    const csrf = document.querySelector('#pmf-csrf-token').value;

    try {
      const response = await fetch('./api/configuration/send-test-mail', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ csrf }),
      });

      if (!response.ok) {
        const result = await response.json();
        displayResult(button, '👎 ' + (result.error || 'Network response was not ok'));
        return;
      }

      const result = await response.json();
      displayResult(button, result.success === 1 ? ' ✅' : '👎 ' + result.error);
    } catch (error) {
      displayResult(button, '👎 ' + error.message);
    }
  }
};

/**
 * Displays the result of the test email operation.
 * @param {HTMLElement} button - The button element to append the result to.
 * @param {string} message - The message to display.
 */
const displayResult = (button, message) => {
  const element = document.createElement('span');
  element.textContent = message;
  button.append(element);
};

/**
 * Generates an API token if the input field is empty.
 */
window.generateApiToken = () => {
  const buttonGenerateApiToken = document.getElementById('pmf-generate-api-token');
  const inputConfigurationApiToken = document.getElementById('edit[api.apiClientToken]');

  if (buttonGenerateApiToken && inputConfigurationApiToken.value === '') {
    inputConfigurationApiToken.value = generateUUID();
  }
};
