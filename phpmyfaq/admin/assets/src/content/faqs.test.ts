import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleFaqForm, handleFaqTranslate } from './faqs';
import { deleteAttachments } from '../api';
import { pushNotification } from '../../../../assets/src/utils';
import { Translator } from '../translation/translator';

vi.mock('../api');
vi.mock('../translation/translator');
vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 10));
};

describe('handleFaqForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should not throw when no elements exist', () => {
    document.body.innerHTML = '<div></div>';

    expect(() => handleFaqForm()).not.toThrow();
  });

  it('should show help element when tags input is focused', () => {
    document.body.innerHTML = `
      <input id="tags" />
      <div id="tagsHelp" class="visually-hidden">Tags help text</div>
    `;

    handleFaqForm();

    const tagsInput = document.getElementById('tags') as HTMLInputElement;
    tagsInput.dispatchEvent(new Event('focus'));

    const tagsHelp = document.getElementById('tagsHelp') as HTMLElement;
    expect(tagsHelp.classList.contains('visually-hidden')).toBe(false);
  });

  it('should show help element when keywords input is focused', () => {
    document.body.innerHTML = `
      <input id="keywords" />
      <div id="keywordsHelp" class="visually-hidden">Keywords help text</div>
    `;

    handleFaqForm();

    const keywordsInput = document.getElementById('keywords') as HTMLInputElement;
    keywordsInput.dispatchEvent(new Event('focus'));

    const keywordsHelp = document.getElementById('keywordsHelp') as HTMLElement;
    expect(keywordsHelp.classList.contains('visually-hidden')).toBe(false);
  });

  it('should call deleteAttachments and show success notification on successful delete', async () => {
    document.body.innerHTML = `
      <button class="pmf-delete-attachment-button"
              data-pmf-attachment-id="99"
              data-pmf-csrf-token="csrf-abc">Delete</button>
      <div id="attachment-id-99">Attachment item</div>
    `;

    (deleteAttachments as Mock).mockResolvedValue({ success: 'Attachment deleted' });

    handleFaqForm();

    const button = document.querySelector('.pmf-delete-attachment-button') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(deleteAttachments).toHaveBeenCalledWith('99', 'csrf-abc');
    expect(pushNotification).toHaveBeenCalledWith('Attachment deleted');
  });

  it('should show error notification when deleteAttachments returns error', async () => {
    document.body.innerHTML = `
      <button class="pmf-delete-attachment-button"
              data-pmf-attachment-id="99"
              data-pmf-csrf-token="csrf-abc">Delete</button>
    `;

    (deleteAttachments as Mock).mockResolvedValue({ error: 'Delete failed' });

    handleFaqForm();

    const button = document.querySelector('.pmf-delete-attachment-button') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(deleteAttachments).toHaveBeenCalledWith('99', 'csrf-abc');
    expect(pushNotification).toHaveBeenCalledWith('Delete failed');
  });

  it('should show warning and disable submit when question contains #', () => {
    document.body.innerHTML = `
      <input id="question" value="" />
      <div id="questionHelp" class="visually-hidden">Hash warning</div>
      <button id="faqEditorSubmit">Submit</button>
    `;

    handleFaqForm();

    const questionInput = document.getElementById('question') as HTMLInputElement;
    questionInput.value = 'What is #something?';
    questionInput.dispatchEvent(new Event('input'));

    const questionHelp = document.getElementById('questionHelp') as HTMLElement;
    const submitButton = document.getElementById('faqEditorSubmit') as HTMLButtonElement;

    expect(questionHelp.classList.contains('visually-hidden')).toBe(false);
    expect(submitButton.getAttribute('disabled')).toBe('true');
  });

  it('should hide warning and enable submit when # is removed from question', () => {
    document.body.innerHTML = `
      <input id="question" value="" />
      <div id="questionHelp" class="visually-hidden">Hash warning</div>
      <button id="faqEditorSubmit">Submit</button>
    `;

    handleFaqForm();

    const questionInput = document.getElementById('question') as HTMLInputElement;
    const questionHelp = document.getElementById('questionHelp') as HTMLElement;
    const submitButton = document.getElementById('faqEditorSubmit') as HTMLButtonElement;

    // First type a hash
    questionInput.value = 'What is #something?';
    questionInput.dispatchEvent(new Event('input'));

    expect(questionHelp.classList.contains('visually-hidden')).toBe(false);
    expect(submitButton.getAttribute('disabled')).toBe('true');

    // Now remove the hash
    questionInput.value = 'What is something?';
    questionInput.dispatchEvent(new Event('input'));

    expect(questionHelp.classList.contains('visually-hidden')).toBe(true);
    expect(submitButton.hasAttribute('disabled')).toBe(false);
  });
});

describe('handleFaqTranslate', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should not throw when elements are missing', () => {
    document.body.innerHTML = '<div></div>';

    expect(() => handleFaqTranslate()).not.toThrow();
  });

  it('should enable translate button and create Translator when different languages are selected', () => {
    document.body.innerHTML = `
      <button id="btn-translate-faq-ai">Translate</button>
      <select id="lang">
        <option value="en">English</option>
        <option value="de">German</option>
      </select>
      <input id="originalFaqLang" value="en" />
    `;

    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

    handleFaqTranslate();

    const langSelect = document.getElementById('lang') as HTMLSelectElement;
    const translateButton = document.getElementById('btn-translate-faq-ai') as HTMLButtonElement;

    // Button should be initially disabled
    expect(translateButton.disabled).toBe(true);

    // Select a different language
    langSelect.value = 'de';
    langSelect.dispatchEvent(new Event('change'));

    expect(translateButton.disabled).toBe(false);
    expect(Translator).toHaveBeenCalledWith(
      expect.objectContaining({
        buttonSelector: '#btn-translate-faq-ai',
        contentType: 'faq',
        sourceLang: 'en',
        targetLang: 'de',
      })
    );

    consoleErrorSpy.mockRestore();
  });

  it('should disable translate button when same language is selected', () => {
    document.body.innerHTML = `
      <button id="btn-translate-faq-ai">Translate</button>
      <select id="lang">
        <option value="en">English</option>
        <option value="de">German</option>
      </select>
      <input id="originalFaqLang" value="en" />
    `;

    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

    handleFaqTranslate();

    const langSelect = document.getElementById('lang') as HTMLSelectElement;
    const translateButton = document.getElementById('btn-translate-faq-ai') as HTMLButtonElement;

    // First select a different language to enable
    langSelect.value = 'de';
    langSelect.dispatchEvent(new Event('change'));
    expect(translateButton.disabled).toBe(false);

    // Now select the same language as source
    langSelect.value = 'en';
    langSelect.dispatchEvent(new Event('change'));
    expect(translateButton.disabled).toBe(true);

    consoleErrorSpy.mockRestore();
  });
});
