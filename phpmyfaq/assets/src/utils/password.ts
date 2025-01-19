/**
 * Simple Toggle Password Visibility
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-26
 */

export const handlePasswordToggle = (): void => {
  const passwordInputs: NodeListOf<HTMLInputElement> = document.querySelectorAll('input[type="password"]');
  passwordInputs.forEach((field: HTMLInputElement) => {
    const toggleId: string | null = field.getAttribute('data-pmf-toggle');
    if (toggleId) {
      const toggle: HTMLElement | null = document.getElementById(toggleId);
      if (toggle) {
        toggle.addEventListener('click', () => {
          const type: string = field.getAttribute('type') === 'password' ? 'text' : 'password';
          field.setAttribute('type', type);
          const icon: HTMLElement | null = document.getElementById(toggleId + '_icon');
          if (icon) {
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
          }
        });
      }
    }
  });
};

export const handlePasswordStrength = (): void => {
  const password: HTMLInputElement | null = document.querySelector('#faqpassword');
  const strength: HTMLElement | null = document.getElementById('strength');

  if (password && strength) {
    password.addEventListener('keyup', () => {
      const strengthValue: number = passwordStrength(password.value) * 25;
      strength.style.width = `${strengthValue}%`;
      strength.classList.remove('bg-danger', 'bg-warning', 'bg-success');

      if (strengthValue < 75) {
        strength.classList.add('bg-danger');
      } else if (strengthValue < 90) {
        strength.classList.add('bg-warning');
      } else {
        strength.classList.add('bg-success');
      }
    });
  }
};

/**
 * Rules for the password strength calculation:
 * - at least eight characters
 * - bonus if longer
 * - a lower letter
 * - an upper letter
 * - a digit
 * - a special character
 *
 * @param password
 * @returns {number}
 */
export const passwordStrength = (password: string): number => {
  return (
    +/.{8,}/.test(password) * // at least 8 characters
    (+/.{12,}/.test(password) + // bonus if longer
      +/[a-z]/.test(password) + // a lower letter
      +/[A-Z]/.test(password) + // an upper letter
      +/\d/.test(password) + // a digit
      +/[^A-Za-z0-9]/.test(password)) // a special character
  );
};
