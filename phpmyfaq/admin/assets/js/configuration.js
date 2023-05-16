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
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-02-19
 */

const generateUUID = () => {
  let date = new Date().getTime();

  if (window.performance && typeof window.performance.now === 'function') {
    date += performance.now();
  }

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
    const random = (date + Math.random() * 16) % 16 | 0;
    date = Math.floor(date / 16);
    return (char === 'x' ? random : (random & 0x3) | 0x8).toString(16);
  });
};

const handleSendTestMail = () => {
  const button = document.getElementById('btn-phpmyfaq-mail-sendTestEmail');
  if (button) {
    const csrf = document.querySelector('#pmf-csrf-token').value;

    fetch('index.php?action=ajax&ajax=config&ajaxaction=send-test-mail', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
      }),
    })
      .then(async (response) => {
        if (response.ok) {
          return response.json();
        }
        throw new Error('Network response was not ok: ', { cause: { response } });
      })
      .then((response) => {
        if (response.success === 1) {
          const element = document.createElement('span');
          element.textContent = ' ✅';
          button.append(element);
        } else {
          const element = document.createElement('span');
          element.textContent = '👎 ' + response.error;
          button.append(element);
        }
      })
      .catch(async (error) => {
        const element = document.createElement('span');
        const errorMessage = await error.cause.response.json();
        element.textContent = '👎 ' + errorMessage.error;
        button.append(element);
      });
  }
};

function generateApiToken() {
  const buttonGenerateApiToken = document.getElementById('pmf-generate-api-token');
  const inputConfigurationApiToken = document.getElementById('edit[api.apiClientToken]');

  if (buttonGenerateApiToken && inputConfigurationApiToken.value === '') {
    inputConfigurationApiToken.value = generateUUID();
  }
}
