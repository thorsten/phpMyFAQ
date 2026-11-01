import { describe, it, expect, beforeEach, afterEach, vi, type Mock } from 'vitest';
import { handleSaveFaqData } from './faqs.editor';
import * as api from '../api';
import * as utils from '../../../../assets/src/utils';

vi.mock('../api');
vi.mock('../../../../assets/src/utils');
vi.mock('./editor', () => ({ getJoditEditor: () => null }));

const mockCreate = api.create as Mock;
const mockUpdate = api.update as Mock;
const mockSerialize = utils.serialize as Mock;

const flush = async (): Promise<void> => {
  for (let i = 0; i < 10; i++) {
    await Promise.resolve();
  }
};

describe('handleSaveFaqData', () => {
  let submitButton: HTMLButtonElement;
  const originalLocation = window.location;

  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();

    document.body.innerHTML = `
      <form id="faqEditor">
        <input type="hidden" name="revisionId" id="revisionId" value="0">
        <input type="hidden" name="faqId" id="faqId" value="0">
        <input type="text" name="question" id="question" value="Question">
        <button type="submit" id="faqEditorSubmit">Save</button>
      </form>
    `;

    submitButton = document.getElementById('faqEditorSubmit') as HTMLButtonElement;

    Object.defineProperty(window, 'location', {
      configurable: true,
      writable: true,
      value: { href: 'http://localhost/admin/faq/add' },
    });
  });

  afterEach(() => {
    vi.useRealTimers();
    Object.defineProperty(window, 'location', { configurable: true, writable: true, value: originalLocation });
  });

  it('should not throw when the submit button is missing', () => {
    document.body.innerHTML = '';
    expect(() => handleSaveFaqData()).not.toThrow();
  });

  it('should create a new FAQ and redirect to its edit page so a second save does not overwrite it', async () => {
    mockSerialize.mockReturnValue({ faqId: '0', question: 'Question' });
    mockCreate.mockResolvedValue({
      success: 'saved',
      data: JSON.stringify({ id: 42, language: 'en', revisionId: 0 }),
    });

    handleSaveFaqData();
    submitButton.dispatchEvent(new Event('click'));
    await flush();

    expect(mockCreate).toHaveBeenCalledTimes(1);
    expect(mockUpdate).not.toHaveBeenCalled();
    expect(utils.pushNotification).toHaveBeenCalledWith('saved');
    expect((document.getElementById('faqId') as HTMLInputElement).value).toBe('42');

    vi.runAllTimers();

    expect(window.location.href).toBe('./faq/edit/42/en');
  });

  it('should update an existing FAQ without redirecting', async () => {
    (document.getElementById('faqId') as HTMLInputElement).value = '7';
    mockSerialize.mockReturnValue({ faqId: '7', question: 'Question' });
    mockUpdate.mockResolvedValue({
      success: 'saved',
      data: JSON.stringify({ id: 7, language: 'en', revisionId: 1 }),
    });

    handleSaveFaqData();
    submitButton.dispatchEvent(new Event('click'));
    await flush();
    vi.runAllTimers();

    expect(mockUpdate).toHaveBeenCalledTimes(1);
    expect(mockCreate).not.toHaveBeenCalled();
    expect((document.getElementById('revisionId') as HTMLInputElement).value).toBe('1');
    expect(window.location.href).toBe('http://localhost/admin/faq/add');
  });

  it('should show the error and stay on the page when the create request fails', async () => {
    mockSerialize.mockReturnValue({ faqId: '0', question: 'Question' });
    mockCreate.mockResolvedValue({ error: 'nope' });

    handleSaveFaqData();
    submitButton.dispatchEvent(new Event('click'));
    await flush();
    vi.runAllTimers();

    expect(utils.pushErrorNotification).toHaveBeenCalledWith('nope');
    expect((document.getElementById('faqId') as HTMLInputElement).value).toBe('0');
    expect(window.location.href).toBe('http://localhost/admin/faq/add');
  });
});
