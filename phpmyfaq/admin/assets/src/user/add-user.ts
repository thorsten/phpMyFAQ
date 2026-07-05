/**
 * Shared wiring for the "add user" modal (used on the user and user-list pages)
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-07-05
 */

import { Modal } from 'bootstrap';
import { addUser } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

export const wireAddUserModal = (onUserAdded: (userName: string) => Promise<void>): void => {
  const actionButton = document.getElementById('pmf-add-user-action') as HTMLButtonElement | null;
  const form = document.getElementById('pmf-add-user-form') as HTMLFormElement | null;
  if (!actionButton || !form) {
    return;
  }

  const passwordToggle = document.getElementById('add_user_automatic_password') as HTMLInputElement | null;
  const passwordInputs = document.getElementById('add_user_show_password_inputs') as HTMLElement | null;
  if (passwordToggle && passwordInputs) {
    passwordToggle.addEventListener('click', (): void => {
      passwordInputs.classList.toggle('d-none');
    });
  }

  actionButton.addEventListener('click', async (event: Event): Promise<void> => {
    event.preventDefault();
    form.classList.add('was-validated');

    const userName = (document.getElementById('add_user_name') as HTMLInputElement).value;
    const payload = {
      csrf: (document.getElementById('add_user_csrf') as HTMLInputElement).value,
      userName,
      realName: (document.getElementById('add_user_realname') as HTMLInputElement).value,
      email: (document.getElementById('add_user_email') as HTMLInputElement).value,
      automaticPassword: (document.getElementById('add_user_automatic_password') as HTMLInputElement).checked,
      password: (document.getElementById('add_user_password') as HTMLInputElement | null)?.value ?? '',
      passwordConfirm: (document.getElementById('add_user_password_confirm') as HTMLInputElement | null)?.value ?? '',
      isSuperAdmin: (document.getElementById('add_user_is_superadmin') as HTMLInputElement | null)?.checked ?? false,
    };

    try {
      const response = await addUser(payload);
      if (!Array.isArray(response) && response.success) {
        pushNotification(response.success);
        Modal.getOrCreateInstance(document.getElementById('addUserModal') as HTMLElement).hide();
        form.reset();
        form.classList.remove('was-validated');
        await onUserAdded(userName);
      } else {
        pushErrorNotification(
          Array.isArray(response)
            ? response.join('\n')
            : (response.error ?? form.dataset.msgError ?? 'An error occurred.')
        );
      }
    } catch {
      pushErrorNotification(form.dataset.msgError ?? 'An error occurred.');
    }
  });
};
