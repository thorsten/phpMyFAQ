import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleApiKeys } from './api-keys';
import { createApiKey, deleteApiKey, fetchApiKeys, updateApiKey } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api', () => ({
  createApiKey: vi.fn(),
  deleteApiKey: vi.fn(),
  fetchApiKeys: vi.fn(),
  updateApiKey: vi.fn(),
}));

vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});

interface MockModal {
  show: Mock;
  hide: Mock;
}

const modals: Record<string, MockModal> = {};

vi.mock('bootstrap', () => ({
  Modal: vi.fn(function (this: MockModal, element: HTMLElement) {
    this.show = vi.fn();
    this.hide = vi.fn();
    modals[element.id] = this;
  }),
}));

const storedKey = {
  id: 7,
  name: 'CI pipeline',
  scopes: ['faq.read'],
  last_used_at: null,
  expires_at: '2027-01-31 23:59:59',
  created: '2026-11-22 10:00:00',
};

const setupDom = (): void => {
  document.body.innerHTML = `
    <button type="button" id="pmf-api-key-add"></button>
    <div id="pmf-api-keys" data-csrf-create="csrf-create" data-csrf-update="csrf-update"
         data-csrf-delete="csrf-delete" data-msg-add="Add API key" data-msg-edit="Edit API key"
         data-msg-delete="Delete API key" data-msg-confirm-delete="Delete?" data-msg-saved="API key saved."
         data-msg-deleted="API key deleted." data-msg-never="Never" data-msg-error="The request failed.">
      <table><tbody id="pmf-api-keys-list"></tbody></table>
      <p id="pmf-api-keys-empty" class="d-none"></p>
    </div>
    <div id="pmf-modal-api-key">
      <form id="pmf-api-key-form">
        <h2 id="pmf-modal-api-key-title"></h2>
        <input type="hidden" id="pmf-api-key-id" value="">
        <input type="text" id="pmf-api-key-name">
        <input type="checkbox" class="pmf-api-key-scope" value="faq.read">
        <input type="checkbox" class="pmf-api-key-scope" value="faq.write">
        <input type="date" id="pmf-api-key-expires">
        <button type="submit"></button>
      </form>
    </div>
    <div id="pmf-modal-api-key-created">
      <input type="text" id="pmf-api-key-value" readonly>
    </div>
  `;
};

const flush = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 0));
};

const submitForm = async (): Promise<void> => {
  (document.getElementById('pmf-api-key-form') as HTMLFormElement).dispatchEvent(
    new Event('submit', { cancelable: true })
  );
  await flush();
};

const scopeCheckbox = (scope: string): HTMLInputElement =>
  document.querySelector(`.pmf-api-key-scope[value="${scope}"]`) as HTMLInputElement;

describe('handleApiKeys', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    setupDom();
    (fetchApiKeys as Mock).mockResolvedValue([storedKey]);
  });

  it('should do nothing on a page without the API key list', async () => {
    document.body.innerHTML = '<div></div>';

    await handleApiKeys();

    expect(fetchApiKeys).not.toHaveBeenCalled();
  });

  it('should render the keys with their scopes and expiry day', async () => {
    await handleApiKeys();

    const cells = [...document.querySelectorAll('#pmf-api-keys-list td')].map((cell) => cell.textContent);
    expect(cells.slice(0, 4)).toEqual(['CI pipeline', 'faq.read', '2027-01-31', 'Never']);
    expect(document.getElementById('pmf-api-keys-empty')?.classList.contains('d-none')).toBe(true);
  });

  it('should show the empty hint when there are no keys', async () => {
    (fetchApiKeys as Mock).mockResolvedValue([]);

    await handleApiKeys();

    expect(document.getElementById('pmf-api-keys-empty')?.classList.contains('d-none')).toBe(false);
  });

  it('should create a key and show its value once', async () => {
    (createApiKey as Mock).mockResolvedValue({ id: 8, apiKey: 'pmf_secret' });
    await handleApiKeys();

    (document.getElementById('pmf-api-key-add') as HTMLButtonElement).click();
    (document.getElementById('pmf-api-key-name') as HTMLInputElement).value = ' Reporting ';
    scopeCheckbox('faq.write').checked = true;
    (document.getElementById('pmf-api-key-expires') as HTMLInputElement).value = '2027-03-01';
    await submitForm();

    expect(createApiKey).toHaveBeenCalledWith(
      { name: 'Reporting', scopes: ['faq.write'], expiresAt: '2027-03-01 23:59:59' },
      'csrf-create'
    );
    expect((document.getElementById('pmf-api-key-value') as HTMLInputElement).value).toBe('pmf_secret');
    expect(modals['pmf-modal-api-key-created'].show).toHaveBeenCalled();
    expect(fetchApiKeys).toHaveBeenCalledTimes(2);

    document.getElementById('pmf-modal-api-key-created')?.dispatchEvent(new Event('hidden.bs.modal'));
    expect((document.getElementById('pmf-api-key-value') as HTMLInputElement).value).toBe('');
  });

  it('should create a key without expiry when no day is chosen', async () => {
    (createApiKey as Mock).mockResolvedValue({ id: 8, apiKey: 'pmf_secret' });
    await handleApiKeys();

    (document.getElementById('pmf-api-key-add') as HTMLButtonElement).click();
    (document.getElementById('pmf-api-key-name') as HTMLInputElement).value = 'Reporting';
    await submitForm();

    expect(createApiKey).toHaveBeenCalledWith({ name: 'Reporting', scopes: [], expiresAt: '' }, 'csrf-create');
  });

  it('should prefill the form for a stored key and update it', async () => {
    (updateApiKey as Mock).mockResolvedValue({ id: 7 });
    await handleApiKeys();

    (document.querySelector('#pmf-api-keys-list .btn-info') as HTMLButtonElement).click();

    expect((document.getElementById('pmf-api-key-name') as HTMLInputElement).value).toBe('CI pipeline');
    expect((document.getElementById('pmf-api-key-expires') as HTMLInputElement).value).toBe('2027-01-31');
    expect(scopeCheckbox('faq.read').checked).toBe(true);
    expect(scopeCheckbox('faq.write').checked).toBe(false);
    expect(document.getElementById('pmf-modal-api-key-title')?.textContent).toBe('Edit API key');

    await submitForm();

    expect(updateApiKey).toHaveBeenCalledWith(
      '7',
      { name: 'CI pipeline', scopes: ['faq.read'], expiresAt: '2027-01-31 23:59:59' },
      'csrf-update'
    );
    expect(pushNotification).toHaveBeenCalledWith('API key saved.');
    expect(modals['pmf-modal-api-key-created'].show).not.toHaveBeenCalled();
  });

  it('should show the reason the server gives for a refused key', async () => {
    (createApiKey as Mock).mockResolvedValue({ error: 'Unknown API key scope: x' });
    await handleApiKeys();

    (document.getElementById('pmf-api-key-add') as HTMLButtonElement).click();
    await submitForm();

    expect(pushErrorNotification).toHaveBeenCalledWith('Unknown API key scope: x');
    expect(modals['pmf-modal-api-key'].hide).not.toHaveBeenCalled();
  });

  it('should delete a key only after confirmation', async () => {
    (deleteApiKey as Mock).mockResolvedValue({ success: true });
    const confirm = vi.spyOn(window, 'confirm').mockReturnValueOnce(false).mockReturnValueOnce(true);
    await handleApiKeys();

    const deleteButton = document.querySelector('#pmf-api-keys-list .btn-danger') as HTMLButtonElement;
    deleteButton.click();
    await flush();
    expect(deleteApiKey).not.toHaveBeenCalled();

    deleteButton.click();
    await flush();
    expect(deleteApiKey).toHaveBeenCalledWith(7, 'csrf-delete');
    expect(pushNotification).toHaveBeenCalledWith('API key deleted.');

    confirm.mockRestore();
  });

  it('should report a list that cannot be loaded', async () => {
    (fetchApiKeys as Mock).mockRejectedValue(new Error('offline'));

    await handleApiKeys();

    expect(pushErrorNotification).toHaveBeenCalledWith('The request failed.');
  });
});
