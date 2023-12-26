/**
 * Simple Toggle Password Visibility
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-26
 */

export const handlePasswordToggle = () => {
  const passwordInputs = document.querySelectorAll('input[type="password"]');
  passwordInputs.forEach(function(field) {
      const toggleId = field.getAttribute('data-pmf-toggle');
      const toggle = document.getElementById(toggleId);
      if (toggle) {
        toggle.addEventListener('click', () => {
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
            const icon = document.getElementById(toggleId + '_icon');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
      }
  });
};

export const handlePasswordStrength = () => {
  const password = document.querySelector('#faqpassword');
  const strength = document.getElementById('strength');

  if (password && strength) {
    password.addEventListener('keyup', () => {
      strength.style.width = (passwordStrength(password.value) * 25).toString() + '%';
    });
  }
};

/**
 * Rules for the password strength calculation:
 *  - at least 8 characters
 *  - bonus if longer
 *  - a lower letter
 *  - an upper letter
 *  - a digit
 *  -a special character
 *
 * @param password
 * @returns {number}
 */
export const passwordStrength = (password) => {
  return (
    /.{8,}/.test(password) * // at least 8 characters
    (/.{12,}/.test(password) + // bonus if longer
      /[a-z]/.test(password) + // a lower letter
      /[A-Z]/.test(password) + // an upper letter
      /\d/.test(password) + // a digit
      /[^A-Za-z0-9]/.test(password)) // a special character
  );
};
