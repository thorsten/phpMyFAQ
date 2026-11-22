/**
 * API key management of the logged-in user
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
import { addElement, pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { createApiKey, deleteApiKey, fetchApiKeys, updateApiKey } from '../api';
import { ApiKey, ApiKeyPayload, ApiKeyResponse } from '../interfaces';

// The server stores 'YYYY-MM-DD HH:MM:SS'; the date input works with the day only.
const toDay = (timestamp: string | null): string => (timestamp ? timestamp.slice(0, 10) : '');

// A key that expires on a day is valid until the end of that day.
const toExpiry = (day: string): string => (day === '' ? '' : `${day} 23:59:59`);

export const handleApiKeys = async (): Promise<void> => {
  const container = document.getElementById('pmf-api-keys');
  const list = document.getElementById('pmf-api-keys-list');
  const emptyHint = document.getElementById('pmf-api-keys-empty');
  const form = document.getElementById('pmf-api-key-form') as HTMLFormElement | null;
  const formModalElement = document.getElementById('pmf-modal-api-key');
  const createdModalElement = document.getElementById('pmf-modal-api-key-created');

  if (!container || !list || !emptyHint || !form || !formModalElement || !createdModalElement) {
    return;
  }

  const messages = container.dataset;
  const formModal = new Modal(formModalElement);
  const createdModal = new Modal(createdModalElement);

  const title = document.getElementById('pmf-modal-api-key-title') as HTMLElement;
  const idInput = document.getElementById('pmf-api-key-id') as HTMLInputElement;
  const nameInput = document.getElementById('pmf-api-key-name') as HTMLInputElement;
  const expiresInput = document.getElementById('pmf-api-key-expires') as HTMLInputElement;
  const createdValue = document.getElementById('pmf-api-key-value') as HTMLInputElement;
  const scopeCheckboxes = (): HTMLInputElement[] => [...form.querySelectorAll<HTMLInputElement>('.pmf-api-key-scope')];

  const showError = (response?: ApiKeyResponse): void => {
    pushErrorNotification(response?.error || messages.msgError || 'The request failed.');
  };

  const openForm = (apiKey?: ApiKey): void => {
    title.textContent = (apiKey ? messages.msgEdit : messages.msgAdd) ?? '';
    idInput.value = apiKey ? String(apiKey.id) : '';
    nameInput.value = apiKey?.name ?? '';
    expiresInput.value = toDay(apiKey?.expires_at ?? null);
    scopeCheckboxes().forEach((checkbox: HTMLInputElement): void => {
      checkbox.checked = apiKey?.scopes.includes(checkbox.value) ?? false;
    });
    formModal.show();
  };

  const removeApiKey = async (apiKey: ApiKey): Promise<void> => {
    if (!window.confirm(messages.msgConfirmDelete ?? '')) {
      return;
    }

    try {
      const response = await deleteApiKey(apiKey.id, messages.csrfDelete ?? '');
      if (!response.success) {
        showError(response);
        return;
      }

      pushNotification(messages.msgDeleted ?? '');
      await loadList();
    } catch {
      showError();
    }
  };

  const renderRow = (apiKey: ApiKey): HTMLElement => {
    const editButton = addElement(
      'button',
      {
        type: 'button',
        classList: 'btn btn-sm btn-info me-1',
        title: messages.msgEdit ?? '',
        'aria-label': `${messages.msgEdit ?? ''}: ${apiKey.name}`,
      },
      [addElement('i', { classList: 'bi bi-pencil', 'aria-hidden': 'true' })]
    );
    editButton.addEventListener('click', (): void => openForm(apiKey));

    const deleteButton = addElement(
      'button',
      {
        type: 'button',
        classList: 'btn btn-sm btn-danger',
        title: messages.msgDelete ?? '',
        'aria-label': `${messages.msgDelete ?? ''}: ${apiKey.name}`,
      },
      [addElement('i', { classList: 'bi bi-trash', 'aria-hidden': 'true' })]
    );
    deleteButton.addEventListener('click', (): Promise<void> => removeApiKey(apiKey));

    return addElement('tr', {}, [
      addElement('td', { textContent: apiKey.name }),
      addElement(
        'td',
        {},
        apiKey.scopes.map((scope: string): HTMLElement =>
          addElement('span', { classList: 'badge text-bg-secondary me-1', textContent: scope })
        )
      ),
      addElement('td', { textContent: toDay(apiKey.expires_at) || (messages.msgNever ?? '') }),
      addElement('td', { textContent: apiKey.last_used_at || (messages.msgNever ?? '') }),
      addElement('td', { classList: 'text-end text-nowrap' }, [editButton, deleteButton]),
    ]);
  };

  const loadList = async (): Promise<void> => {
    try {
      const apiKeys = await fetchApiKeys();
      if (!Array.isArray(apiKeys)) {
        showError(apiKeys as ApiKeyResponse);
        return;
      }

      list.replaceChildren(...apiKeys.map(renderRow));
      emptyHint.classList.toggle('d-none', apiKeys.length > 0);
    } catch {
      showError();
    }
  };

  document.getElementById('pmf-api-key-add')?.addEventListener('click', (): void => openForm());

  form.addEventListener('submit', async (event: Event): Promise<void> => {
    event.preventDefault();

    const payload: ApiKeyPayload = {
      name: nameInput.value.trim(),
      scopes: scopeCheckboxes()
        .filter((checkbox: HTMLInputElement): boolean => checkbox.checked)
        .map((checkbox: HTMLInputElement): string => checkbox.value),
      expiresAt: toExpiry(expiresInput.value),
    };

    try {
      const response =
        idInput.value === ''
          ? await createApiKey(payload, messages.csrfCreate ?? '')
          : await updateApiKey(idInput.value, payload, messages.csrfUpdate ?? '');

      if (response.error || response.id === undefined) {
        showError(response);
        return;
      }

      formModal.hide();
      await loadList();

      // The key itself is only part of the response that creates it
      if (response.apiKey) {
        createdValue.value = response.apiKey;
        createdModal.show();
        return;
      }

      pushNotification(messages.msgSaved ?? '');
    } catch {
      showError();
    }
  });

  // The key must not stay in the page once the administrator has copied it
  createdModalElement.addEventListener('hidden.bs.modal', (): void => {
    createdValue.value = '';
  });

  await loadList();
};
