import { describe, it, expect, vi, beforeEach, afterEach, Mock } from 'vitest';
import { handleOAuth2 } from './oauth2';
import { createOAuth2Client, deleteOAuth2Client, saveOAuth2Scopes, updateOAuth2Client } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api', () => ({
  createOAuth2Client: vi.fn(),
  deleteOAuth2Client: vi.fn(),
  saveOAuth2Scopes: vi.fn(),
  updateOAuth2Client: vi.fn(),
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

const setupDom = (): void => {
  document.body.innerHTML = `
    <button type="button" id="pmf-oauth2-client-add"></button>
    <div id="pmf-oauth2" data-csrf-save-client="csrf-save" data-csrf-delete-client="csrf-delete"
         data-csrf-save-scopes="csrf-scopes" data-msg-add="Add client" data-msg-edit="Edit client"
         data-msg-confirm-delete="Delete?" data-msg-error="The request failed.">
      <button type="button" class="pmf-oauth2-client-edit" data-client-id="pmf_1" data-client-name="Intranet"
              data-client-redirect-uri="https://intranet.example.org/callback"
              data-client-grants="authorization_code" data-client-confidential="0"></button>
      <button type="button" class="pmf-oauth2-client-delete" data-client-id="pmf_1"></button>
      <form id="pmf-oauth2-scopes-form">
        <table><tbody>
          <tr class="pmf-oauth2-scope" data-scope="faq.read">
            <td><input type="checkbox" class="pmf-oauth2-scope-enabled" checked></td>
            <td><input type="text" class="pmf-oauth2-scope-description" value=" Read FAQs "></td>
          </tr>
          <tr class="pmf-oauth2-scope" data-scope="faq.write">
            <td><input type="checkbox" class="pmf-oauth2-scope-enabled"></td>
            <td><input type="text" class="pmf-oauth2-scope-description" value="Write FAQs"></td>
          </tr>
        </tbody></table>
      </form>
    </div>
    <div id="pmf-modal-oauth2-client">
      <form id="pmf-oauth2-client-form">
        <h2 id="pmf-modal-oauth2-client-title"></h2>
        <input type="hidden" id="pmf-oauth2-client-id" value="">
        <input type="text" id="pmf-oauth2-client-name">
        <input type="checkbox" id="pmf-oauth2-client-confidential" checked>
        <input type="checkbox" class="pmf-oauth2-client-grant" value="authorization_code">
        <input type="checkbox" class="pmf-oauth2-client-grant" value="client_credentials">
        <input type="url" id="pmf-oauth2-client-redirect-uri">
      </form>
    </div>
    <div id="pmf-modal-oauth2-client-created">
      <input type="text" id="pmf-oauth2-created-client-id" readonly>
      <div id="pmf-oauth2-created-secret">
        <input type="text" id="pmf-oauth2-created-client-secret" readonly>
      </div>
    </div>
  `;
};

const flush = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 0));
};

const submit = async (formId: string): Promise<void> => {
  (document.getElementById(formId) as HTMLFormElement).dispatchEvent(new Event('submit', { cancelable: true }));
  await flush();
};

const input = (id: string): HTMLInputElement => document.getElementById(id) as HTMLInputElement;

const grantCheckbox = (grant: string): HTMLInputElement =>
  document.querySelector(`.pmf-oauth2-client-grant[value="${grant}"]`) as HTMLInputElement;

describe('handleOAuth2', () => {
  const reload = vi.fn();
  const originalLocation = window.location;

  beforeEach(() => {
    vi.clearAllMocks();
    setupDom();
    Object.defineProperty(window, 'location', { configurable: true, value: { reload } });
  });

  afterEach(() => {
    Object.defineProperty(window, 'location', { configurable: true, value: originalLocation });
  });

  it('should do nothing on a page without the OAuth 2.0 management', () => {
    document.body.innerHTML = '<div></div>';

    handleOAuth2();

    expect(Object.keys(document.body.dataset)).toEqual([]);
  });

  it('should register a confidential client and show its secret once', async () => {
    (createOAuth2Client as Mock).mockResolvedValue({ clientId: 'pmf_2', clientSecret: 'top-secret' });
    handleOAuth2();

    (document.getElementById('pmf-oauth2-client-add') as HTMLButtonElement).click();
    expect(input('pmf-oauth2-client-confidential').disabled).toBe(false);
    input('pmf-oauth2-client-name').value = ' Reporting ';
    grantCheckbox('client_credentials').checked = true;
    await submit('pmf-oauth2-client-form');

    expect(createOAuth2Client).toHaveBeenCalledWith(
      { name: 'Reporting', redirectUri: '', grants: ['client_credentials'], confidential: true },
      'csrf-save'
    );
    expect(input('pmf-oauth2-created-client-id').value).toBe('pmf_2');
    expect(input('pmf-oauth2-created-client-secret').value).toBe('top-secret');
    expect(document.getElementById('pmf-oauth2-created-secret')?.classList.contains('d-none')).toBe(false);
    expect(modals['pmf-modal-oauth2-client-created'].show).toHaveBeenCalled();
    expect(reload).not.toHaveBeenCalled();

    document.getElementById('pmf-modal-oauth2-client-created')?.dispatchEvent(new Event('hidden.bs.modal'));
    expect(reload).toHaveBeenCalledTimes(1);
  });

  it('should hide the secret of a public client, which has none', async () => {
    (createOAuth2Client as Mock).mockResolvedValue({ clientId: 'pmf_3', clientSecret: null });
    handleOAuth2();

    (document.getElementById('pmf-oauth2-client-add') as HTMLButtonElement).click();
    input('pmf-oauth2-client-confidential').checked = false;
    await submit('pmf-oauth2-client-form');

    expect(document.getElementById('pmf-oauth2-created-secret')?.classList.contains('d-none')).toBe(true);
  });

  it('should prefill the form for a stored client and update it', async () => {
    (updateOAuth2Client as Mock).mockResolvedValue({ success: 'Client saved.' });
    handleOAuth2();

    (document.querySelector('.pmf-oauth2-client-edit') as HTMLButtonElement).click();

    expect(input('pmf-oauth2-client-name').value).toBe('Intranet');
    expect(input('pmf-oauth2-client-redirect-uri').value).toBe('https://intranet.example.org/callback');
    expect(input('pmf-oauth2-client-confidential').checked).toBe(false);
    expect(input('pmf-oauth2-client-confidential').disabled).toBe(true);
    expect(grantCheckbox('authorization_code').checked).toBe(true);
    expect(grantCheckbox('client_credentials').checked).toBe(false);

    await submit('pmf-oauth2-client-form');

    expect(updateOAuth2Client).toHaveBeenCalledWith(
      'pmf_1',
      {
        name: 'Intranet',
        redirectUri: 'https://intranet.example.org/callback',
        grants: ['authorization_code'],
        confidential: false,
      },
      'csrf-save'
    );
    expect(reload).toHaveBeenCalledTimes(1);
  });

  it('should show the reason the server gives for a refused client', async () => {
    (createOAuth2Client as Mock).mockResolvedValue({ error: 'Select at least one grant type.' });
    handleOAuth2();

    (document.getElementById('pmf-oauth2-client-add') as HTMLButtonElement).click();
    await submit('pmf-oauth2-client-form');

    expect(pushErrorNotification).toHaveBeenCalledWith('Select at least one grant type.');
    expect(modals['pmf-modal-oauth2-client-created'].show).not.toHaveBeenCalled();
  });

  it('should delete a client only after confirmation', async () => {
    (deleteOAuth2Client as Mock).mockResolvedValue({ success: 'Client deleted.' });
    const confirm = vi.spyOn(window, 'confirm').mockReturnValueOnce(false).mockReturnValueOnce(true);
    handleOAuth2();

    const deleteButton = document.querySelector('.pmf-oauth2-client-delete') as HTMLButtonElement;
    deleteButton.click();
    await flush();
    expect(deleteOAuth2Client).not.toHaveBeenCalled();

    deleteButton.click();
    await flush();
    expect(deleteOAuth2Client).toHaveBeenCalledWith('pmf_1', 'csrf-delete');
    expect(reload).toHaveBeenCalledTimes(1);

    confirm.mockRestore();
  });

  it('should save only the enabled scopes', async () => {
    (saveOAuth2Scopes as Mock).mockResolvedValue({ success: 'Scopes saved.' });
    handleOAuth2();

    await submit('pmf-oauth2-scopes-form');

    expect(saveOAuth2Scopes).toHaveBeenCalledWith([{ scope: 'faq.read', description: 'Read FAQs' }], 'csrf-scopes');
    expect(pushNotification).toHaveBeenCalledWith('Scopes saved.');
  });

  it('should report scopes that cannot be saved', async () => {
    (saveOAuth2Scopes as Mock).mockRejectedValue(new Error('offline'));
    handleOAuth2();

    await submit('pmf-oauth2-scopes-form');

    expect(pushErrorNotification).toHaveBeenCalledWith('The request failed.');
  });
});
