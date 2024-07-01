/**
 * Captcha related functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-02
 */
import { fetchCaptchaImage } from '../api/captcha';

export const handleReloadCaptcha = (reloadButton) => {
  reloadButton.addEventListener('click', async (event) => {
    event.preventDefault();

    const action = event.target.getAttribute('data-action');
    const timestamp = Math.floor(new Date().getTime() / 1000);

    try {
      await fetchCaptchaImage(action, timestamp);
      const captcha = document.getElementById('captcha');
      const captchaImage = document.getElementById('captchaImage');
      captchaImage.setAttribute('src', './api/captcha?' + timestamp);
      captcha.value = '';
      captcha.focus();
    } catch (error) {
      console.error(error);
    }
  });
};
