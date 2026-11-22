/**
 * OAuth 2.0 client and scope management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-22
 */

import { Modal } from 'bootstrap';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { createOAuth2Client, deleteOAuth2Client, saveOAuth2Scopes, updateOAuth2Client } from '../api';
import { OAuth2ClientPayload, OAuth2ClientResponse, OAuth2Scope } from '../interfaces';

export const handleOAuth2 = (): void => {
  const container = document.getElementById('pmf-oauth2');
  const form = document.getElementById('pmf-oauth2-client-form') as HTMLFormElement | null;
  const formModalElement = document.getElementById('pmf-modal-oauth2-client');
  const createdModalElement = document.getElementById('pmf-modal-oauth2-client-created');

  if (!container || !form || !formModalElement || !createdModalElement) {
    return;
  }

  const messages = container.dataset;
  const formModal = new Modal(formModalElement);
  const createdModal = new Modal(createdModalElement);

  const title = document.getElementById('pmf-modal-oauth2-client-title') as HTMLElement;
  const idInput = document.getElementById('pmf-oauth2-client-id') as HTMLInputElement;
  const nameInput = document.getElementById('pmf-oauth2-client-name') as HTMLInputElement;
  const confidentialInput = document.getElementById('pmf-oauth2-client-confidential') as HTMLInputElement;
  const redirectUriInput = document.getElementById('pmf-oauth2-client-redirect-uri') as HTMLInputElement;
  const grantCheckboxes = (): HTMLInputElement[] => [
    ...form.querySelectorAll<HTMLInputElement>('.pmf-oauth2-client-grant'),
  ];

  const showError = (response?: OAuth2ClientResponse): void => {
    pushErrorNotification(response?.error || messages.msgError || 'The request failed.');
  };

  // The lists are rendered by the server
  const reload = (): void => window.location.reload();

  const openForm = (client?: DOMStringMap): void => {
    const grants = (client?.clientGrants ?? '').split(',');

    title.textContent = (client ? messages.msgEdit : messages.msgAdd) ?? '';
    idInput.value = client?.clientId ?? '';
    nameInput.value = client?.clientName ?? '';
    redirectUriInput.value = client?.clientRedirectUri ?? '';
    // Whether a client is confidential is decided when it is registered
    confidentialInput.checked = client ? client.clientConfidential === '1' : true;
    confidentialInput.disabled = client !== undefined;
    grantCheckboxes().forEach((checkbox: HTMLInputElement): void => {
      checkbox.checked = grants.includes(checkbox.value);
    });
    formModal.show();
  };

  document.getElementById('pmf-oauth2-client-add')?.addEventListener('click', (): void => openForm());

  container.querySelectorAll<HTMLElement>('.pmf-oauth2-client-edit').forEach((button: HTMLElement): void => {
    button.addEventListener('click', (): void => openForm(button.dataset));
  });

  container.querySelectorAll<HTMLElement>('.pmf-oauth2-client-delete').forEach((button: HTMLElement): void => {
    button.addEventListener('click', async (): Promise<void> => {
      if (!window.confirm(messages.msgConfirmDelete ?? '')) {
        return;
      }

      try {
        const response = await deleteOAuth2Client(button.dataset.clientId ?? '', messages.csrfDeleteClient ?? '');
        if (!response.success) {
          showError(response);
          return;
        }

        reload();
      } catch {
        showError();
      }
    });
  });

  form.addEventListener('submit', async (event: Event): Promise<void> => {
    event.preventDefault();

    const payload: OAuth2ClientPayload = {
      name: nameInput.value.trim(),
      redirectUri: redirectUriInput.value.trim(),
      grants: grantCheckboxes()
        .filter((checkbox: HTMLInputElement): boolean => checkbox.checked)
        .map((checkbox: HTMLInputElement): string => checkbox.value),
      confidential: confidentialInput.checked,
    };
    const csrf = messages.csrfSaveClient ?? '';

    try {
      if (idInput.value !== '') {
        const response = await updateOAuth2Client(idInput.value, payload, csrf);
        if (!response.success) {
          showError(response);
          return;
        }

        reload();
        return;
      }

      const response = await createOAuth2Client(payload, csrf);
      if (!response.clientId) {
        showError(response);
        return;
      }

      // The secret is only part of the response that registers the client
      (document.getElementById('pmf-oauth2-created-client-id') as HTMLInputElement).value = response.clientId;
      (document.getElementById('pmf-oauth2-created-client-secret') as HTMLInputElement).value =
        response.clientSecret ?? '';
      document.getElementById('pmf-oauth2-created-secret')?.classList.toggle('d-none', !response.clientSecret);
      formModal.hide();
      createdModal.show();
    } catch {
      showError();
    }
  });

  // The new client shows up in the list once the administrator has copied the secret
  createdModalElement.addEventListener('hidden.bs.modal', reload);

  document.getElementById('pmf-oauth2-scopes-form')?.addEventListener('submit', async (event: Event): Promise<void> => {
    event.preventDefault();

    const scopes: OAuth2Scope[] = [...container.querySelectorAll<HTMLElement>('.pmf-oauth2-scope')]
      .filter((row: HTMLElement): boolean => row.querySelector<HTMLInputElement>('.pmf-oauth2-scope-enabled')!.checked)
      .map((row: HTMLElement): OAuth2Scope => ({
        scope: row.dataset.scope ?? '',
        description: row.querySelector<HTMLInputElement>('.pmf-oauth2-scope-description')!.value.trim(),
      }));

    try {
      const response = await saveOAuth2Scopes(scopes, messages.csrfSaveScopes ?? '');
      if (!response.success) {
        showError(response);
        return;
      }

      pushNotification(response.success);
    } catch {
      showError();
    }
  });
};
